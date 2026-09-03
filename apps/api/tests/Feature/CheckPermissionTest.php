<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckPermissionTest extends TestCase
{
    use RefreshDatabase;

    private function authenticate(
        User $user,
        Organization $organization
    ): void {
        $this->actingAs($user);

        $this->app['request']->attributes->set(
            'currentOrganization',
            $organization
        );
    }

    private function createPermission(string $slug): Permission
    {
        return Permission::create([
            'module' => explode('.', $slug)[0],
            'name' => $slug,
            'slug' => $slug,
            'is_active' => true,
        ]);
    }

    private function createRole(
        Organization $organization,
        string $slug = 'manager'
    ): Role {
        return Role::create([
            'organization_id' => $organization->id,
            'name' => ucfirst($slug),
            'slug' => $slug,
            'is_system' => false,
            'is_active' => true,
        ]);
    }

    private function assignRole(
        User $user,
        Organization $organization,
        Role $role
    ): void {
        $user->rolesForOrganization($organization)->attach(
            $role->id,
            ['organization_id' => $organization->id]
        );
    }

    public function test_unauthenticated_user_is_rejected(): void
    {
        $response = $this->getJson('/api/v1/organizations');

        $response->assertUnauthorized();
    }

    public function test_user_without_current_organization_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->app['request']->attributes->set(
            'currentOrganization',
            null
        );

        $response = $this->getJson('/api/v1/organizations');

        $response->assertForbidden();
    }

    public function test_user_without_permission_is_rejected(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();

        $this->authenticate($user, $organization);

        $permission = $this->createPermission('sales.finalize');

        $this->assertFalse(
            app(\App\Services\Rbac\PermissionService::class)
                ->hasPermission($user, $organization, $permission->slug)
        );
    }

    public function test_permission_can_be_granted_by_role(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();

        $permission = $this->createPermission('sales.finalize');
        $role = $this->createRole($organization);

        $role->permissions()->attach($permission->id);

        $this->assignRole($user, $organization, $role);

        $this->assertTrue(
            app(\App\Services\Rbac\PermissionService::class)
                ->hasPermission($user, $organization, 'sales.finalize')
        );
    }

    public function test_explicit_denial_overrides_role_permission(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();

        $permission = $this->createPermission('sales.finalize');
        $role = $this->createRole($organization);

        $role->permissions()->attach($permission->id);

        $this->assignRole($user, $organization, $role);

       $user->permissionsForOrganization($organization)->syncWithoutDetaching([
			$permission->id => [
				'organization_id' => $organization->id,
				'granted' => false,
			],
		]);

        $this->assertFalse(
            app(\App\Services\Rbac\PermissionService::class)
                ->hasPermission($user, $organization, 'sales.finalize')
        );
    }

    public function test_explicit_grant_overrides_missing_role_permission(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();

        $permission = $this->createPermission('sales.finalize');

        $user->permissionsForOrganization($organization)->syncWithoutDetaching([
			$permission->id => [
				'organization_id' => $organization->id,
				'granted' => true,
			],
		]);

        $this->assertTrue(
            app(\App\Services\Rbac\PermissionService::class)
                ->hasPermission($user, $organization, 'sales.finalize')
        );
    }
}