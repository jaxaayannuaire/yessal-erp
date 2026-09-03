<?php

namespace Tests\Feature\Caisse;

use App\Models\Organization;
use App\Models\Caisse\Device;
use App\Models\Caisse\Product;
use App\Models\Caisse\Shop;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaisseSubscriptionEntitlementApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    private function organizationWithSubscription(
        ?string $status = 'active',
        bool $withCaisseEntitlement = true,
        ?\DateTimeInterface $gracePeriodEndsAt = null
    ): Organization {
        $organization = Organization::factory()->create();

        if ($status === null) {
            return $organization;
        }

        $plan = $withCaisseEntitlement
            ? Plan::factory()->withCaisseEntitlement()->create()
            : Plan::factory()->create();

        Subscription::factory()->create([
            'organization_id' => $organization->id,
            'plan_id' => $plan->id,
            'status' => $status,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'grace_period_ends_at' => $gracePeriodEndsAt,
        ]);

        return $organization;
    }

    private function userWithRole(Organization $organization, string $roleSlug): User
    {
        $user = User::factory()->create();
        $organization->users()->attach($user->id, ['role' => 'member']);

        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user->organizationRoleAssignments()->create([
            'organization_id' => $organization->id,
            'role_id' => $role->id,
        ]);

        return $user;
    }

    private function member(Organization $organization, string $role = 'member'): User
    {
        $user = User::factory()->create();
        $organization->users()->attach($user->id, ['role' => $role]);

        return $user;
    }

    private function listDevices(User $user, Organization $organization)
    {
        return $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => (string) $organization->id])
            ->getJson('/api/v1/caisse/devices');
    }

    public function test_organization_without_subscription_is_denied_from_caisse(): void
    {
        $organization = $this->organizationWithSubscription(null);
        $cashier = $this->userWithRole($organization, 'cashier');

        $this->listDevices($cashier, $organization)->assertForbidden();
    }

    public function test_pending_subscription_is_denied_from_caisse(): void
    {
        $organization = $this->organizationWithSubscription('pending');
        $cashier = $this->userWithRole($organization, 'cashier');

        $this->listDevices($cashier, $organization)->assertForbidden();
    }

    public function test_expired_subscription_is_denied_from_caisse(): void
    {
        $organization = $this->organizationWithSubscription('expired');
        $cashier = $this->userWithRole($organization, 'cashier');

        $this->listDevices($cashier, $organization)->assertForbidden();
    }

    public function test_cancelled_subscription_is_denied_from_caisse(): void
    {
        $organization = $this->organizationWithSubscription('cancelled');
        $cashier = $this->userWithRole($organization, 'cashier');

        $this->listDevices($cashier, $organization)->assertForbidden();
    }

    public function test_past_due_subscription_after_grace_is_denied_from_caisse(): void
    {
        $organization = $this->organizationWithSubscription(
            'past_due',
            true,
            now()->subMinute()
        );
        $cashier = $this->userWithRole($organization, 'cashier');

        $this->listDevices($cashier, $organization)->assertForbidden();
    }

    public function test_past_due_subscription_during_grace_reaches_caisse_controller(): void
    {
        $organization = $this->organizationWithSubscription(
            'past_due',
            true,
            now()->addMinute()
        );
        $cashier = $this->userWithRole($organization, 'cashier');

        $this->listDevices($cashier, $organization)->assertOk();
    }

    public function test_active_subscription_without_caisse_entitlement_is_denied(): void
    {
        $organization = $this->organizationWithSubscription('active', false);
        $cashier = $this->userWithRole($organization, 'cashier');

        $this->listDevices($cashier, $organization)->assertForbidden();
    }

    public function test_active_subscription_with_entitlement_but_without_rbac_is_denied(): void
    {
        $organization = $this->organizationWithSubscription();
        $member = $this->member($organization);

        $this->listDevices($member, $organization)->assertForbidden();
    }

    public function test_active_subscription_with_caisse_entitlement_and_rbac_is_authorized(): void
    {
        $organization = $this->organizationWithSubscription();
        $cashier = $this->userWithRole($organization, 'cashier');

        $this->listDevices($cashier, $organization)->assertOk();
    }

    public function test_legacy_owner_without_subscription_is_denied_from_caisse(): void
    {
        $organization = $this->organizationWithSubscription(null);
        $owner = $this->member($organization, 'owner');

        $this->listDevices($owner, $organization)->assertForbidden();
    }

    public function test_legacy_owner_without_caisse_entitlement_is_denied(): void
    {
        $organization = $this->organizationWithSubscription('active', false);
        $owner = $this->member($organization, 'owner');

        $this->listDevices($owner, $organization)->assertForbidden();
    }

    public function test_legacy_owner_with_subscription_and_caisse_entitlement_is_authorized(): void
    {
        $organization = $this->organizationWithSubscription();
        $owner = $this->member($organization, 'owner');

        $this->listDevices($owner, $organization)->assertOk();
    }

    public function test_selected_organization_entitlement_is_used_for_caisse_access(): void
    {
        $organizationA = $this->organizationWithSubscription('active', true);
        $organizationB = $this->organizationWithSubscription('active', false);
        $user = User::factory()->create();

        $organizationA->users()->attach($user->id, ['role' => 'member']);
        $organizationB->users()->attach($user->id, ['role' => 'member']);

        $cashier = Role::query()->where('slug', 'cashier')->firstOrFail();
        $user->organizationRoleAssignments()->createMany([
            ['organization_id' => $organizationA->id, 'role_id' => $cashier->id],
            ['organization_id' => $organizationB->id, 'role_id' => $cashier->id],
        ]);

        $this->listDevices($user, $organizationB)->assertForbidden();
    }

    public function test_entitlement_endpoint_uses_selected_organization(): void
    {
        $organizationA = $this->organizationWithSubscription('active', true);
        $organizationB = $this->organizationWithSubscription('active', false);
        $user = User::factory()->create();

        $organizationA->users()->attach($user->id, ['role' => 'member']);
        $organizationB->users()->attach($user->id, ['role' => 'member']);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => (string) $organizationB->id])
            ->getJson('/api/v1/organization/entitlements');

        $response->assertOk()
            ->assertJsonPath('organization.id', $organizationB->id)
            ->assertJsonMissing(['slug' => 'pos.sell']);
    }

    public function test_entitlement_endpoint_exposes_tenant_scoped_quota_usage(): void
    {
        $organization = $this->organizationWithSubscription();
        $user = $this->member($organization);
        $organization->users()->attach(User::factory()->create()->id, ['role' => 'member']);
        $shop = Shop::factory()->create(['organization_id' => $organization->id]);
        Product::factory()->create(['shop_id' => $shop->id]);
        Device::factory()->create(['organization_id' => $organization->id, 'status' => 'active']);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => (string) $organization->id])
            ->getJson('/api/v1/organization/entitlements');

        $response->assertOk()
            ->assertJsonPath('usage.users', 2)
            ->assertJsonPath('usage.shops', 1)
            ->assertJsonPath('usage.products', 1)
            ->assertJsonPath('usage.devices', 1)
            ->assertJsonStructure(['limits' => ['max_users', 'max_products', 'max_devices', 'max_shops']]);
    }
}
