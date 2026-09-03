<?php

namespace Tests\Feature\Caisse;

use App\Models\Caisse\Device;
use App\Models\Caisse\Shop;
use App\Models\Caisse\SyncEvent;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Caisse\SyncService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PDOException;
use Tests\TestCase;

class SyncApiTest extends TestCase
{
    use RefreshDatabase;

    private function context(): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $organization->users()->attach($user->id, [
            'role' => 'owner',
        ]);

        $plan = Plan::factory()->withCaisseEntitlement()->create([
            'is_active' => true,
        ]);

        Subscription::factory()->create([
            'organization_id' => $organization->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addMonth(),
        ]);

        return [$user, $organization];
    }

    private function headers(int $organizationId, array $extra = []): array
    {
        return array_merge([
            'X-Organization-Id' => (string) $organizationId,
            'Accept' => 'application/json',
        ], $extra);
    }

    private function device(
        Organization $organization,
        ?Shop $shop = null,
        string $status = 'active'
    ): Device {
        return Device::factory()->create([
            'organization_id' => $organization->id,
            'shop_id' => $shop?->id,
            'status' => $status,
        ]);
    }

    private function eventPayload(
        ?int $shopId = null,
        ?string $uuid = null
    ): array {
        return [
            'event_uuid' => $uuid ?? (string) Str::uuid(),
            'shop_id' => $shopId,
            'entity_type' => 'sale',
            'entity_id' => (string) fake()->numberBetween(1, 999999),
            'action' => 'create',
            'payload' => [
                'total_amount' => 1000,
                'currency' => 'XOF',
            ],
            'occurred_at' => now()->toISOString(),
        ];
    }

    public function test_user_can_push_sync_event(): void
    {
        [$user, $organization] = $this->context();

        $device = $this->device($organization);
        $event = $this->eventPayload();

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers(
                $organization->id,
                [
                    'X-App-Version' => '0.1.0',
                    'X-Platform' => 'android',
                ]
            ))
            ->postJson('/api/v1/caisse/sync/push', [
                'device_id' => $device->id,
                'events' => [$event],
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'accepted')
            ->assertJsonCount(0, 'rejected')
            ->assertJsonCount(0, 'conflicts');

        $this->assertDatabaseHas('sync_events', [
            'organization_id' => $organization->id,
            'device_id' => $device->id,
            'event_uuid' => $event['event_uuid'],
            'entity_type' => 'sale',
            'entity_id' => $event['entity_id'],
            'action' => 'create',
            'status' => 'pending',
        ]);
    }

    public function test_same_event_uuid_is_idempotent(): void
    {
        [$user, $organization] = $this->context();

        $device = $this->device($organization);
        $uuid = (string) Str::uuid();

        $payload = [
            'device_id' => $device->id,
            'events' => [
                $this->eventPayload(null, $uuid),
            ],
        ];

        $first = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson('/api/v1/caisse/sync/push', $payload);

        $second = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson('/api/v1/caisse/sync/push', $payload);

        $first
            ->assertOk()
            ->assertJsonPath('accepted.0.duplicate', false);

        $second
            ->assertOk()
            ->assertJsonPath('accepted.0.duplicate', true);

        $this->assertDatabaseCount('sync_events', 1);
    }

    public function test_same_event_uuid_in_another_organization_is_isolated(): void
    {
        [$user, $organization] = $this->context();
        $otherOrganization = Organization::factory()->create();
        $otherOrganization->users()->attach($user->id, ['role' => 'owner']);

        $plan = Plan::factory()->withCaisseEntitlement()->create(['is_active' => true]);
        Subscription::factory()->create([
            'organization_id' => $otherOrganization->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addMonth(),
        ]);

        $device = $this->device($organization);
        $otherDevice = $this->device($otherOrganization);
        $uuid = (string) Str::uuid();

        $first = $this->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson('/api/v1/caisse/sync/push', [
                'device_id' => $device->id,
                'events' => [$this->eventPayload(null, $uuid)],
            ]);

        $second = $this->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($otherOrganization->id))
            ->postJson('/api/v1/caisse/sync/push', [
                'device_id' => $otherDevice->id,
                'events' => [$this->eventPayload(null, $uuid)],
            ]);

        $first->assertOk()->assertJsonPath('accepted.0.duplicate', false);
        $second->assertOk()->assertJsonPath('accepted.0.duplicate', false);

        $this->assertDatabaseCount('sync_events', 2);
        $this->assertDatabaseHas('sync_events', [
            'organization_id' => $organization->id,
            'device_id' => $device->id,
            'event_uuid' => $uuid,
        ]);
        $this->assertDatabaseHas('sync_events', [
            'organization_id' => $otherOrganization->id,
            'device_id' => $otherDevice->id,
            'event_uuid' => $uuid,
        ]);
    }

    public function test_unique_constraint_collision_returns_the_existing_event_as_duplicate(): void
    {
        [$user, $organization] = $this->context();
        $device = $this->device($organization);
        $event = $this->eventPayload();

        $existing = SyncEvent::create([
            'organization_id' => $organization->id,
            'shop_id' => null,
            'device_id' => $device->id,
            'event_uuid' => $event['event_uuid'],
            'entity_type' => $event['entity_type'],
            'entity_id' => $event['entity_id'],
            'action' => $event['action'],
            'payload' => $event['payload'],
            'status' => 'pending',
            'created_at' => $event['occurred_at'],
        ]);

        $service = new class extends SyncService {
            private int $findAttempts = 0;

            protected function findSyncEvent(
                int $organizationId,
                string $eventUuid
            ): ?SyncEvent {
                $this->findAttempts++;

                if ($this->findAttempts === 1) {
                    return null;
                }

                return parent::findSyncEvent($organizationId, $eventUuid);
            }

            protected function createSyncEvent(array $attributes): SyncEvent
            {
                throw new QueryException(
                    'testing',
                    'insert into sync_events',
                    [],
                    new PDOException('duplicate key', '23505')
                );
            }
        };

        $result = $service->push(
            $organization->id,
            $device->id,
            [$event]
        );

        $this->assertCount(1, $result['accepted']);
        $this->assertTrue($result['accepted'][0]['duplicate']);
        $this->assertSame($existing->id, $result['accepted'][0]['id']);
        $this->assertDatabaseCount('sync_events', 1);
        $this->assertDatabaseHas('sync_events', [
            'organization_id' => $organization->id,
            'device_id' => $device->id,
            'event_uuid' => $event['event_uuid'],
        ]);
    }

    public function test_multiple_events_can_be_pushed(): void
    {
        [$user, $organization] = $this->context();

        $device = $this->device($organization);

        $events = [
            $this->eventPayload(),
            $this->eventPayload(),
            $this->eventPayload(),
        ];

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson('/api/v1/caisse/sync/push', [
                'device_id' => $device->id,
                'events' => $events,
            ]);

        $response
            ->assertOk()
            ->assertJsonCount(3, 'accepted')
            ->assertJsonCount(0, 'rejected');

        $this->assertDatabaseCount('sync_events', 3);
    }

    public function test_device_from_another_organization_is_rejected(): void
    {
        [$user, $organization] = $this->context();

        $otherOrganization = Organization::factory()->create();
        $otherDevice = $this->device($otherOrganization);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson('/api/v1/caisse/sync/push', [
                'device_id' => $otherDevice->id,
                'events' => [$this->eventPayload()],
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('device_id');
    }

    public function test_inactive_device_is_rejected(): void
    {
        [$user, $organization] = $this->context();

        $device = $this->device(
            $organization,
            null,
            'blocked'
        );

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson('/api/v1/caisse/sync/push', [
                'device_id' => $device->id,
                'events' => [$this->eventPayload()],
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('device_id');
    }

    public function test_shop_from_another_organization_is_rejected(): void
    {
        [$user, $organization] = $this->context();

        $device = $this->device($organization);

        $otherOrganization = Organization::factory()->create();

        $otherShop = Shop::factory()->create([
            'organization_id' => $otherOrganization->id,
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson('/api/v1/caisse/sync/push', [
                'device_id' => $device->id,
                'events' => [
                    $this->eventPayload($otherShop->id),
                ],
            ]);

        $response
            ->assertOk()
            ->assertJsonCount(0, 'accepted')
            ->assertJsonCount(1, 'rejected');

        $this->assertDatabaseCount('sync_events', 0);
    }

    public function test_shop_incompatible_with_device_is_rejected(): void
    {
        [$user, $organization] = $this->context();

        $shopA = Shop::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $shopB = Shop::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $device = $this->device(
            $organization,
            $shopA
        );

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson('/api/v1/caisse/sync/push', [
                'device_id' => $device->id,
                'events' => [
                    $this->eventPayload($shopB->id),
                ],
            ]);

        $response
            ->assertOk()
            ->assertJsonCount(0, 'accepted')
            ->assertJsonCount(1, 'rejected');

        $this->assertDatabaseCount('sync_events', 0);
    }

    public function test_event_with_invalid_uuid_is_rejected_by_validation(): void
    {
        [$user, $organization] = $this->context();

        $device = $this->device($organization);

        $event = $this->eventPayload();
        $event['event_uuid'] = 'invalid-uuid';

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson('/api/v1/caisse/sync/push', [
                'device_id' => $device->id,
                'events' => [$event],
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('events.0.event_uuid');
    }

    public function test_payload_is_required(): void
    {
        [$user, $organization] = $this->context();

        $device = $this->device($organization);

        $event = $this->eventPayload();
        unset($event['payload']);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson('/api/v1/caisse/sync/push', [
                'device_id' => $device->id,
                'events' => [$event],
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('events.0.payload');
    }

    public function test_sync_updates_device_activity_timestamps(): void
    {
        [$user, $organization] = $this->context();

        $device = $this->device($organization);

        $initialLastSeenAt = $device->last_seen_at;

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers(
                $organization->id,
                [
                    'X-App-Version' => '0.1.0',
                    'X-Platform' => 'android',
                ]
            ))
            ->postJson('/api/v1/caisse/sync/push', [
                'device_id' => $device->id,
                'events' => [$this->eventPayload()],
            ]);

        $response->assertOk();

        $device->refresh();

        $this->assertNotNull($device->last_seen_at);
        $this->assertNotNull($device->last_sync_at);

        $this->assertTrue(
            $device->last_seen_at->greaterThanOrEqualTo($initialLastSeenAt)
        );
    }

    public function test_sync_creates_activity_logs(): void
    {
        [$user, $organization] = $this->context();

        $device = $this->device($organization);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers(
                $organization->id,
                [
                    'X-App-Version' => '0.1.0',
                    'X-Platform' => 'android',
                ]
            ))
            ->postJson('/api/v1/caisse/sync/push', [
                'device_id' => $device->id,
                'events' => [$this->eventPayload()],
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('device_activity_logs', [
            'organization_id' => $organization->id,
            'device_id' => $device->id,
            'event_type' => 'connected',
            'app_version' => '0.1.0',
        ]);

        $this->assertDatabaseHas('device_activity_logs', [
            'organization_id' => $organization->id,
            'device_id' => $device->id,
            'event_type' => 'sync_push',
            'app_version' => '0.1.0',
        ]);
    }

    public function test_rejected_event_creates_activity_log(): void
    {
        [$user, $organization] = $this->context();

        $device = $this->device($organization);

        $otherOrganization = Organization::factory()->create();

        $otherShop = Shop::factory()->create([
            'organization_id' => $otherOrganization->id,
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson('/api/v1/caisse/sync/push', [
                'device_id' => $device->id,
                'events' => [
                    $this->eventPayload($otherShop->id),
                ],
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('device_activity_logs', [
            'organization_id' => $organization->id,
            'device_id' => $device->id,
            'event_type' => 'sync_rejected',
        ]);
    }

    public function test_unknown_organization_context_is_rejected(): void
    {
        [$user, $organization] = $this->context();

        $device = $this->device($organization);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers(999999))
            ->postJson('/api/v1/caisse/sync/push', [
                'device_id' => $device->id,
                'events' => [$this->eventPayload()],
            ]);

        $response->assertForbidden();
    }
}
