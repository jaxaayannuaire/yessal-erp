<?php

namespace Tests\Feature\Caisse;

use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    private function userWithRole(string $roleSlug): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $role = Role::where('slug', $roleSlug)
            ->whereNull('organization_id')
            ->firstOrFail();

        $user->organizationRoleAssignments()->create([
            'organization_id' => $organization->id,
            'role_id' => $role->id,
        ]);

        return [$user, $organization];
    }

    public function test_manager_has_shop_view_permission(): void
    {
        [$user, $organization] = $this->userWithRole('manager');

        $this->assertTrue(
            app(\App\Services\Rbac\PermissionService::class)
                ->hasPermission(
                    $user,
                    $organization,
                    'shops.view'
                )
        );
    }

    public function test_manager_has_not_shop_manage_permission(): void
    {
        [$user, $organization] = $this->userWithRole('manager');

        $this->assertFalse(
            app(\App\Services\Rbac\PermissionService::class)
                ->hasPermission(
                    $user,
                    $organization,
                    'shops.manage'
                )
        );
    }

    public function test_admin_has_shop_manage_permission(): void
    {
        [$user, $organization] = $this->userWithRole('admin');

        $this->assertTrue(
            app(\App\Services\Rbac\PermissionService::class)
                ->hasPermission(
                    $user,
                    $organization,
                    'shops.manage'
                )
        );
    }

    public function test_cashier_has_no_shop_view_permission(): void
    {
        [$user, $organization] = $this->userWithRole('cashier');

        $this->assertFalse(
            app(\App\Services\Rbac\PermissionService::class)
                ->hasPermission(
                    $user,
                    $organization,
                    'shops.view'
                )
        );
    }
}