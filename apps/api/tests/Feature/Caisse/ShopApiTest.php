<?php

namespace Tests\Feature\Caisse;

use App\Models\Caisse\Shop;
use App\Models\Organization;
use App\Models\OrganizationUserRole;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    private function createOrganizationWithSubscription(): array
    {
        $user = User::factory()->create();

        $organization = Organization::factory()->create();

        $organization->users()->attach($user->id, [
            'role' => 'owner',
        ]);

        // Le rôle "owner" de organization_user est distinct
        // du système RBAC basé sur organization_user_roles.
        $adminRole = Role::whereNull('organization_id')
            ->where('slug', 'admin')
            ->firstOrFail();

        OrganizationUserRole::create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role_id' => $adminRole->id,
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

        return [$user, $organization, $plan];
    }

    public function test_authenticated_user_can_list_organization_shops(): void
    {
        [$user, $organization] = $this->createOrganizationWithSubscription();

        Shop::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Boutique Dakar',
            'code' => 'DAKAR',
        ]);

        Shop::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Boutique Thiès',
            'code' => 'THIES',
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeader('X-Organization-Id', $organization->id)
            ->getJson('/api/v1/caisse/shops');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'shops');
    }

    public function test_shop_creation_is_refused_when_quota_is_reached(): void
    {
        [$user, $organization, $plan] = $this->createOrganizationWithSubscription();
        $plan->update(['max_shops' => 1]);
        Shop::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($user, 'sanctum')
            ->withHeader('X-Organization-Id', $organization->id)
            ->postJson('/api/v1/caisse/shops', ['name' => 'Trop', 'code' => 'TROP'])
            ->assertUnprocessable();
    }

    public function test_shop_from_another_organization_is_rejected(): void
    {
        [$user, $organization] = $this->createOrganizationWithSubscription();

        $otherOrganization = Organization::factory()->create();

        $otherShop = Shop::factory()->create([
            'organization_id' => $otherOrganization->id,
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeader('X-Organization-Id', $organization->id)
            ->getJson("/api/v1/caisse/shops/{$otherShop->id}");

        $response->assertForbidden();
    }

    public function test_shop_creation_uses_current_organization(): void
    {
        [$user, $organization] = $this->createOrganizationWithSubscription();

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeader('X-Organization-Id', $organization->id)
            ->postJson('/api/v1/caisse/shops', [
                'name' => 'Nouvelle Boutique',
                'code' => 'NEW-SHOP',
                'address' => 'Dakar',
                'phone' => '770000000',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('shop.organization_id', $organization->id);

        $this->assertDatabaseHas('shops', [
            'organization_id' => $organization->id,
            'code' => 'NEW-SHOP',
        ]);
    }

    public function test_shop_code_must_be_unique_within_organization(): void
    {
        [$user, $organization] = $this->createOrganizationWithSubscription();

        Shop::factory()->create([
            'organization_id' => $organization->id,
            'code' => 'CAISSE-01',
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeader('X-Organization-Id', $organization->id)
            ->postJson('/api/v1/caisse/shops', [
                'name' => 'Deuxième boutique',
                'code' => 'CAISSE-01',
            ]);

        $response->assertUnprocessable();
    }

    public function test_same_shop_code_is_allowed_in_another_organization(): void
    {
        [$user, $organization] = $this->createOrganizationWithSubscription();

        $otherOrganization = Organization::factory()->create();

        Shop::factory()->create([
            'organization_id' => $otherOrganization->id,
            'code' => 'CAISSE-01',
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeader('X-Organization-Id', $organization->id)
            ->postJson('/api/v1/caisse/shops', [
                'name' => 'Ma boutique',
                'code' => 'CAISSE-01',
            ]);

        $response->assertCreated();
    }

    public function test_shop_can_be_updated_within_current_organization(): void
    {
        [$user, $organization] = $this->createOrganizationWithSubscription();

        $shop = Shop::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Ancien nom',
            'code' => 'SHOP-01',
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeader('X-Organization-Id', $organization->id)
            ->patchJson("/api/v1/caisse/shops/{$shop->id}", [
                'name' => 'Nouveau nom',
                'status' => 'inactive',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('shop.name', 'Nouveau nom')
            ->assertJsonPath('shop.status', 'inactive');
    }

    public function test_shop_update_from_another_organization_is_rejected(): void
    {
        [$user, $organization] = $this->createOrganizationWithSubscription();

        $otherOrganization = Organization::factory()->create();

        $shop = Shop::factory()->create([
            'organization_id' => $otherOrganization->id,
            'code' => 'OTHER',
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeader('X-Organization-Id', $organization->id)
            ->patchJson("/api/v1/caisse/shops/{$shop->id}", [
                'name' => 'Tentative',
            ]);

        $response->assertForbidden();
    }
}
