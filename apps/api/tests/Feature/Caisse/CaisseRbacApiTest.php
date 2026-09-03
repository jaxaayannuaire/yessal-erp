<?php

namespace Tests\Feature\Caisse;

use App\Models\Caisse\Device;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CaisseRbacApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    private function userWithRole(Organization $organization, string $roleSlug): User
    {
        $this->ensureCaisseAccess($organization);

        $user = User::factory()->create();
        $organization->users()->attach($user->id, ['role' => 'member']);

        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user->organizationRoleAssignments()->create([
            'organization_id' => $organization->id,
            'role_id' => $role->id,
        ]);

        return $user;
    }

    private function member(Organization $organization): User
    {
        $this->ensureCaisseAccess($organization);

        $user = User::factory()->create();
        $organization->users()->attach($user->id, ['role' => 'member']);

        return $user;
    }

    private function headers(Organization $organization): array
    {
        return ['X-Organization-Id' => (string) $organization->id];
    }

    private function ensureCaisseAccess(Organization $organization): void
    {
        if ($organization->subscriptions()->exists()) {
            return;
        }

        $plan = Plan::factory()->withCaisseEntitlement()->create();

        Subscription::factory()->create([
            'organization_id' => $organization->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addMonth(),
        ]);
    }

    private function syncPayload(Device $device): array
    {
        return [
            'device_id' => $device->id,
            'events' => [[
                'event_uuid' => (string) Str::uuid(),
                'entity_type' => 'sale',
                'entity_id' => '1',
                'action' => 'create',
                'payload' => ['total_amount' => 1000],
            ]],
        ];
    }

    public function test_admin_can_list_devices_through_rbac(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->userWithRole($organization, 'admin');

        $this->actingAs($admin, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->getJson('/api/v1/caisse/devices')
            ->assertOk();
    }

    public function test_cashier_with_devices_view_can_list_devices(): void
    {
        $organization = Organization::factory()->create();
        $cashier = $this->userWithRole($organization, 'cashier');

        $this->actingAs($cashier, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->getJson('/api/v1/caisse/devices')
            ->assertOk();
    }

    public function test_member_without_devices_view_is_forbidden_from_listing_devices(): void
    {
        $organization = Organization::factory()->create();
        $member = $this->member($organization);

        $this->actingAs($member, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->getJson('/api/v1/caisse/devices')
            ->assertForbidden();
    }

    public function test_cashier_with_only_devices_view_is_forbidden_from_managing_devices(): void
    {
        $organization = Organization::factory()->create();
        $cashier = $this->userWithRole($organization, 'cashier');

        $this->actingAs($cashier, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson('/api/v1/caisse/devices', [
                'device_uuid' => (string) Str::uuid(),
                'status' => 'inactive',
            ])
            ->assertForbidden();
    }

    public function test_cashier_with_cash_view_can_list_cash_sessions(): void
    {
        $organization = Organization::factory()->create();
        $cashier = $this->userWithRole($organization, 'cashier');

        $this->actingAs($cashier, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->getJson('/api/v1/caisse/cash-sessions')
            ->assertOk();
    }

    public function test_member_without_cash_view_is_forbidden_from_listing_cash_sessions(): void
    {
        $organization = Organization::factory()->create();
        $member = $this->member($organization);

        $this->actingAs($member, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->getJson('/api/v1/caisse/cash-sessions')
            ->assertForbidden();
    }

    public function test_cashier_with_sync_push_can_push_an_event(): void
    {
        $organization = Organization::factory()->create();
        $cashier = $this->userWithRole($organization, 'cashier');
        $device = Device::factory()->create([
            'organization_id' => $organization->id,
            'shop_id' => null,
        ]);

        $this->actingAs($cashier, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson('/api/v1/caisse/sync/push', $this->syncPayload($device))
            ->assertOk();
    }

    public function test_member_without_sync_push_is_forbidden_from_pushing_an_event(): void
    {
        $organization = Organization::factory()->create();
        $member = $this->member($organization);
        $device = Device::factory()->create([
            'organization_id' => $organization->id,
            'shop_id' => null,
        ]);

        $this->actingAs($member, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson('/api/v1/caisse/sync/push', $this->syncPayload($device))
            ->assertForbidden();
    }

    public function test_legacy_owner_bypass_remains_available(): void
    {
        $organization = Organization::factory()->create();
        $this->ensureCaisseAccess($organization);
        $owner = User::factory()->create();
        $organization->users()->attach($owner->id, ['role' => 'owner']);

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->getJson('/api/v1/caisse/devices')
            ->assertOk();
    }
}
