<?php

namespace Tests\Feature\Caisse;

use App\Models\Caisse\Device;
use App\Models\Caisse\Shop;
use App\Models\Caisse\SyncChange;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SyncPullApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    private function organizationWithAccess(): Organization
    {
        $organization = Organization::factory()->create();
        $plan = Plan::factory()->withCaisseEntitlement()->create();

        Subscription::factory()->create([
            'organization_id' => $organization->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addMonth(),
        ]);

        return $organization;
    }

    private function owner(Organization $organization): User
    {
        $user = User::factory()->create();
        $organization->users()->attach($user->id, ['role' => 'owner']);

        return $user;
    }

    private function member(Organization $organization): User
    {
        $user = User::factory()->create();
        $organization->users()->attach($user->id, ['role' => 'member']);

        return $user;
    }

    private function headers(Organization $organization): array
    {
        return ['X-Organization-Id' => (string) $organization->id];
    }

    private function shop(Organization $organization): Shop
    {
        return Shop::factory()->create(['organization_id' => $organization->id]);
    }

    public function test_pull_requires_the_caisse_security_chain_and_sync_permission(): void
    {
        $this->getJson('/api/v1/caisse/sync/pull')->assertUnauthorized();

        $organization = $this->organizationWithAccess();
        $owner = $this->owner($organization);

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/v1/caisse/sync/pull')
            ->assertForbidden();

        $member = $this->member($organization);
        $this->actingAs($member, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->getJson('/api/v1/caisse/sync/pull')
            ->assertForbidden();

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->getJson('/api/v1/caisse/sync/pull')
            ->assertOk()
            ->assertJsonPath('changes', [])
            ->assertJsonPath('next_cursor', 0)
            ->assertJsonPath('has_more', false);
    }

    public function test_pull_returns_category_product_and_customer_changes_from_real_api_writes(): void
    {
        $organization = $this->organizationWithAccess();
        $owner = $this->owner($organization);
        $shop = $this->shop($organization);

        $category = $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson('/api/v1/caisse/categories', [
                'shop_id' => $shop->id,
                'name' => 'Boissons',
                'slug' => 'boissons',
            ])
            ->assertCreated()
            ->json('category');

        $product = $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson('/api/v1/caisse/products', [
                'shop_id' => $shop->id,
                'category_id' => $category['id'],
                'name' => 'Jus',
                'sku' => 'JUS-001',
                'sale_price' => 500,
            ])
            ->assertCreated()
            ->json('product');

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson('/api/v1/caisse/customers', [
                'shop_id' => $shop->id,
                'name' => 'Awa Ndiaye',
            ])
            ->assertCreated();

        $response = $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->getJson('/api/v1/caisse/sync/pull?cursor=0')
            ->assertOk()
            ->assertJsonCount(3, 'changes')
            ->assertJsonPath('changes.0.entity_type', 'category')
            ->assertJsonPath('changes.1.entity_type', 'product')
            ->assertJsonPath('changes.1.payload.id', $product['id'])
            ->assertJsonPath('changes.2.entity_type', 'customer')
            ->assertJsonPath('changes.0.operation', 'upsert');

        $firstCursor = $response->json('changes.0.cursor');
        $lastCursor = $response->json('next_cursor');
        $this->assertGreaterThan($firstCursor, $lastCursor);
    }

    public function test_product_update_creates_a_new_cursor_and_propagates_inactive_status(): void
    {
        $organization = $this->organizationWithAccess();
        $owner = $this->owner($organization);
        $shop = $this->shop($organization);
        $product = $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson('/api/v1/caisse/products', [
                'shop_id' => $shop->id,
                'name' => 'Produit',
                'sale_price' => 100,
            ])
            ->assertCreated()
            ->json('product');
        $cursor = $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->getJson('/api/v1/caisse/sync/pull')
            ->json('next_cursor');

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->patchJson("/api/v1/caisse/products/{$product['id']}", [
                'status' => 'inactive',
            ])
            ->assertOk();

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->getJson('/api/v1/caisse/sync/pull?cursor='.$cursor)
            ->assertOk()
            ->assertJsonCount(1, 'changes')
            ->assertJsonPath('changes.0.entity_type', 'product')
            ->assertJsonPath('changes.0.payload.status', 'inactive');
    }

    public function test_pull_cursor_pagination_has_no_duplicates_or_skips(): void
    {
        $organization = $this->organizationWithAccess();
        $owner = $this->owner($organization);

        foreach (range(1, 4) as $index) {
            SyncChange::create([
                'organization_id' => $organization->id,
                'entity_type' => 'product',
                'entity_id' => (string) $index,
                'operation' => 'upsert',
                'payload' => ['id' => $index],
                'occurred_at' => now(),
            ]);
        }

        $first = $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->getJson('/api/v1/caisse/sync/pull?cursor=0&limit=2')
            ->assertOk()
            ->assertJsonCount(2, 'changes')
            ->assertJsonPath('has_more', true);
        $cursor = $first->json('next_cursor');

        $second = $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->getJson('/api/v1/caisse/sync/pull?cursor='.$cursor.'&limit=2')
            ->assertOk()
            ->assertJsonCount(2, 'changes')
            ->assertJsonPath('has_more', false);

        $ids = array_merge(
            array_column($first->json('changes'), 'cursor'),
            array_column($second->json('changes'), 'cursor')
        );

        $this->assertCount(4, array_unique($ids));
        $this->assertSame($cursor, $first->json('changes.1.cursor'));
    }

    public function test_pull_is_tenant_scoped_and_unknown_push_event_is_rejected(): void
    {
        $organization = $this->organizationWithAccess();
        $owner = $this->owner($organization);
        $otherOrganization = $this->organizationWithAccess();
        $otherOwner = $this->owner($otherOrganization);

        SyncChange::create([
            'organization_id' => $otherOrganization->id,
            'entity_type' => 'customer',
            'entity_id' => '1',
            'operation' => 'upsert',
            'payload' => ['name' => 'Autre organisation'],
            'occurred_at' => now(),
        ]);

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->getJson('/api/v1/caisse/sync/pull?cursor=0')
            ->assertOk()
            ->assertJsonPath('changes', []);

        $device = Device::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);
        $eventUuid = (string) Str::uuid();

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson('/api/v1/caisse/sync/push', [
                'device_id' => $device->id,
                'events' => [[
                    'event_uuid' => $eventUuid,
                    'entity_type' => 'unknown',
                    'entity_id' => '1',
                    'action' => 'create',
                    'payload' => ['value' => 'unsupported'],
                ]],
            ])
            ->assertOk()
            ->assertJsonCount(0, 'accepted')
            ->assertJsonCount(1, 'rejected');

        $this->assertDatabaseMissing('sync_events', [
            'organization_id' => $organization->id,
            'event_uuid' => $eventUuid,
        ]);
    }
}
