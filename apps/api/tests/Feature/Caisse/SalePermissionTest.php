<?php

namespace Tests\Feature\Caisse;

use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    private function setupUserWithRole(string $roleSlug): array
    {
        $organization = Organization::factory()->create();

        $user = User::factory()->create();

        $role = Role::where('slug', $roleSlug)
            ->where('is_system', true)
            ->firstOrFail();

        $organization->users()->attach($user->id, [
            'role' => $roleSlug,
        ]);

        $user->rolesForOrganization($organization)->attach(
            $role->id,
            ['organization_id' => $organization->id]
        );

        return [$user, $organization];
    }

    private function permission(string $slug): Permission
    {
        return Permission::where('slug', $slug)->firstOrFail();
    }

    public function test_cashier_can_view_sales(): void
    {
        [$user, $organization] = $this->setupUserWithRole('cashier');

        $this->actingAs($user);

        $organization->refresh();

        $this->assertTrue(
            app(\App\Services\Rbac\PermissionService::class)
                ->hasPermission(
                    $user,
                    $organization,
                    'sales.view'
                )
        );
    }

    public function test_cashier_can_create_sales(): void
    {
        [$user, $organization] = $this->setupUserWithRole('cashier');

        $this->assertTrue(
            app(\App\Services\Rbac\PermissionService::class)
                ->hasPermission(
                    $user,
                    $organization,
                    'sales.create'
                )
        );
    }

    public function test_cashier_cannot_finalize_sales(): void
    {
        [$user, $organization] = $this->setupUserWithRole('cashier');

        $this->assertFalse(
            app(\App\Services\Rbac\PermissionService::class)
                ->hasPermission(
                    $user,
                    $organization,
                    'sales.finalize'
                )
        );
    }

    public function test_manager_can_finalize_sales_permission(): void
    {
        [$user, $organization] = $this->setupUserWithRole('manager');

        $this->assertTrue(
            app(\App\Services\Rbac\PermissionService::class)
                ->hasPermission(
                    $user,
                    $organization,
                    'sales.finalize'
                )
        );
    }

    public function test_admin_can_finalize_sales_permission(): void
    {
        [$user, $organization] = $this->setupUserWithRole('admin');

        $this->assertTrue(
            app(\App\Services\Rbac\PermissionService::class)
                ->hasPermission(
                    $user,
                    $organization,
                    'sales.finalize'
                )
        );
    }
}