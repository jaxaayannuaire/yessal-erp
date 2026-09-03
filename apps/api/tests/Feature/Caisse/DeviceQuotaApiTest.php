<?php

namespace Tests\Feature\Caisse;

use App\Models\Caisse\Device;
use App\Models\Caisse\Shop;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceQuotaApiTest extends TestCase
{
    use RefreshDatabase;

    private function context(?int $maxDevices = null): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $organization->users()->attach($user->id, [
            'role' => 'owner',
        ]);

        $plan = Plan::factory()->withCaisseEntitlement()->create([
            'max_devices' => $maxDevices,
            'is_active' => true,
        ]);

        Subscription::factory()->create([
            'organization_id' => $organization->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addMonth(),
        ]);

        return [$user, $organization, $plan];
    }

    private function headers(int $organizationId): array
    {
        return [
            'X-Organization-Id' => (string) $organizationId,
            'Accept' => 'application/json',
        ];
    }

    private function shop(Organization $organization): Shop
    {
        return Shop::factory()->create([
            'organization_id' => $organization->id,
        ]);
    }

    private function createDevice(
        Organization $organization,
        string $status = 'active'
    ): Device {
        return Device::factory()->create([
            'organization_id' => $organization->id,
            'status' => $status,
        ]);
    }

    private function devicePayload(
        Organization $organization,
        string $uuid,
        string $status = 'active'
    ): array {
        return [
            'device_uuid' => $uuid,
            'name' => 'Test Device',
            'platform' => 'android',
            'app_version' => '1.0.0',
            'status' => $status,
        ];
    }

    public function test_second_active_device_is_rejected_when_limit_is_one(): void
    {
        [$user, $organization] = $this->context(1);

        $this->createDevice($organization);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson(
                '/api/v1/caisse/devices',
                $this->devicePayload(
                    $organization,
                    fake()->uuid()
                )
            );

        $response
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'device_quota_exceeded')
            ->assertJsonPath('quota.resource', 'devices')
            ->assertJsonPath('quota.usage', 1)
            ->assertJsonPath('quota.limit', 1)
            ->assertJsonPath('quota.allowed', false);

        $this->assertSame(
            1,
            Device::where('organization_id', $organization->id)->count()
        );
    }

    public function test_active_devices_can_be_created_up_to_limit(): void
    {
        [$user, $organization] = $this->context(2);

        $response1 = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson(
                '/api/v1/caisse/devices',
                $this->devicePayload(
                    $organization,
                    fake()->uuid()
                )
            );

        $response1->assertCreated();

        $response2 = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson(
                '/api/v1/caisse/devices',
                $this->devicePayload(
                    $organization,
                    fake()->uuid()
                )
            );

        $response2->assertCreated();

        $this->assertSame(
            2,
            Device::where('organization_id', $organization->id)
                ->where('status', 'active')
                ->count()
        );
    }

    public function test_inactive_devices_do_not_count_against_quota(): void
    {
        [$user, $organization] = $this->context(1);

        $this->createDevice(
            $organization,
            'inactive'
        );

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson(
                '/api/v1/caisse/devices',
                $this->devicePayload(
                    $organization,
                    fake()->uuid()
                )
            );

        $response->assertCreated();

        $this->assertSame(
            1,
            Device::where('organization_id', $organization->id)
                ->where('status', 'active')
                ->count()
        );
    }

    public function test_revoking_device_frees_a_quota_slot(): void
    {
        [$user, $organization] = $this->context(1);

        $device = $this->createDevice($organization);

        $createResponse = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson(
                '/api/v1/caisse/devices',
                $this->devicePayload(
                    $organization,
                    fake()->uuid()
                )
            );

        $createResponse->assertStatus(422);

        $revokeResponse = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson(
                "/api/v1/caisse/devices/{$device->id}/revoke"
            );

        $revokeResponse->assertOk();

        $createResponse = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson(
                '/api/v1/caisse/devices',
                $this->devicePayload(
                    $organization,
                    fake()->uuid()
                )
            );

        $createResponse->assertCreated();

        $this->assertSame(
            1,
            Device::where('organization_id', $organization->id)
                ->where('status', 'active')
                ->count()
        );
    }

    public function test_reactivation_is_rejected_when_quota_is_full(): void
    {
        [$user, $organization] = $this->context(1);

        $revokedDevice = $this->createDevice(
            $organization,
            'inactive'
        );

        $activeDevice = $this->createDevice(
            $organization,
            'active'
        );

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson(
                "/api/v1/caisse/devices/{$revokedDevice->id}/activate"
            );

        $response
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'device_quota_exceeded')
            ->assertJsonPath('quota.usage', 1)
            ->assertJsonPath('quota.limit', 1);

        $this->assertSame(
            'inactive',
            $revokedDevice->fresh()->status
        );

        $this->assertSame(
            'active',
            $activeDevice->fresh()->status
        );
    }

    public function test_reactivation_is_allowed_after_another_device_is_revoked(): void
    {
        [$user, $organization] = $this->context(1);

        $deviceA = $this->createDevice(
            $organization,
            'active'
        );

        $deviceB = $this->createDevice(
            $organization,
            'inactive'
        );

        $revokeResponse = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson(
                "/api/v1/caisse/devices/{$deviceA->id}/revoke"
            );

        $revokeResponse->assertOk();

        $activateResponse = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson(
                "/api/v1/caisse/devices/{$deviceB->id}/activate"
            );

        $activateResponse
            ->assertOk()
            ->assertJsonPath('device.status', 'active');

        $this->assertSame(
            1,
            Device::where('organization_id', $organization->id)
                ->where('status', 'active')
                ->count()
        );
    }

    public function test_null_device_limit_allows_unlimited_devices(): void
    {
        [$user, $organization] = $this->context(null);

        foreach (range(1, 5) as $index) {
            $response = $this
                ->actingAs($user, 'sanctum')
                ->withHeaders($this->headers($organization->id))
                ->postJson(
                    '/api/v1/caisse/devices',
                    $this->devicePayload(
                        $organization,
                        fake()->uuid()
                    )
                );

            $response->assertCreated();
        }

        $this->assertSame(
            5,
            Device::where('organization_id', $organization->id)
                ->where('status', 'active')
                ->count()
        );
    }

    public function test_device_quota_is_isolated_between_organizations(): void
    {
        [$userA, $organizationA] = $this->context(1);
        [, $organizationB] = $this->context(1);

        $this->createDevice($organizationA);

        $response = $this
            ->actingAs($userA, 'sanctum')
            ->withHeaders($this->headers($organizationA->id))
            ->postJson(
                '/api/v1/caisse/devices',
                $this->devicePayload(
                    $organizationA,
                    fake()->uuid()
                )
            );

        $response
            ->assertStatus(422)
            ->assertJsonPath('code', 'device_quota_exceeded');

        $deviceB = $this->createDevice(
            $organizationB
        );

        $this->assertSame(
            $organizationB->id,
            $deviceB->organization_id
        );

        $this->assertSame(
            1,
            Device::where('organization_id', $organizationB->id)
                ->where('status', 'active')
                ->count()
        );
    }

    public function test_quota_check_reports_current_usage_and_limit(): void
    {
        [$user, $organization] = $this->context(3);

        $this->createDevice($organization);
        $this->createDevice($organization);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson(
                '/api/v1/caisse/devices',
                $this->devicePayload(
                    $organization,
                    fake()->uuid()
                )
            );

        $response->assertCreated();

        $this->assertSame(
            3,
            Device::where('organization_id', $organization->id)
                ->where('status', 'active')
                ->count()
        );
    }
}
