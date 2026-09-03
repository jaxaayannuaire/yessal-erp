<?php

namespace Tests\Feature\Rbac;

use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Rbac\PermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionServiceTest extends TestCase
{
    use RefreshDatabase;

    private PermissionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(PermissionService::class);
    }

    public function test_user_has_permission_from_role(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();

        $role = Role::create([
            'organization_id' => null,
            'name' => 'Manager',
            'slug' => 'manager',
            'description' => null,
            'is_system' => true,
            'is_active' => true,
        ]);

        $permission = Permission::create([
            'module' => 'sales',
            'name' => 'Voir les ventes',
            'slug' => 'sales.view',
            'description' => null,
            'is_active' => true,
        ]);

        $role->permissions()->attach($permission);

        $organization->users()->attach($user, ['role' => 'member']);

        $user->organizationRoleAssignments()->create([
            'organization_id' => $organization->id,
            'role_id' => $role->id,
        ]);

        $this->assertTrue(
            $this->service->hasPermission(
                $user,
                $organization,
                'sales.view'
            )
        );
    }

    public function test_user_without_permission_is_denied(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();

        $organization->users()->attach($user, ['role' => 'member']);

        Permission::create([
            'module' => 'sales',
            'name' => 'Voir les ventes',
            'slug' => 'sales.view',
            'description' => null,
            'is_active' => true,
        ]);

        $this->assertFalse(
            $this->service->hasPermission(
                $user,
                $organization,
                'sales.view'
            )
        );
    }

    public function test_explicit_user_grant_overrides_missing_role_permission(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();

        $organization->users()->attach($user, ['role' => 'member']);

        $permission = Permission::create([
            'module' => 'sales',
            'name' => 'Finaliser les ventes',
            'slug' => 'sales.finalize',
            'description' => null,
            'is_active' => true,
        ]);

        $user->permissionsForOrganization($organization)->attach(
            $permission->id,
            [
                'organization_id' => $organization->id,
                'granted' => true,
            ]
        );

        $this->assertTrue(
            $this->service->hasPermission(
                $user,
                $organization,
                'sales.finalize'
            )
        );
    }

    public function test_explicit_user_denial_overrides_role_permission(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();

        $role = Role::create([
            'organization_id' => null,
            'name' => 'Manager',
            'slug' => 'manager',
            'description' => null,
            'is_system' => true,
            'is_active' => true,
        ]);

        $permission = Permission::create([
            'module' => 'sales',
            'name' => 'Annuler les ventes',
            'slug' => 'sales.cancel',
            'description' => null,
            'is_active' => true,
        ]);

        $role->permissions()->attach($permission);

        $organization->users()->attach($user, ['role' => 'member']);

        $user->organizationRoleAssignments()->create([
            'organization_id' => $organization->id,
            'role_id' => $role->id,
        ]);

        $user->permissionsForOrganization($organization)->attach(
            $permission->id,
            [
                'organization_id' => $organization->id,
                'granted' => false,
            ]
        );

        $this->assertFalse(
            $this->service->hasPermission(
                $user,
                $organization,
                'sales.cancel'
            )
        );
    }

    public function test_effective_permissions_combine_role_and_user_permissions(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();

        $role = Role::create([
            'organization_id' => null,
            'name' => 'Manager',
            'slug' => 'manager',
            'description' => null,
            'is_system' => true,
            'is_active' => true,
        ]);

        $view = Permission::create([
            'module' => 'sales',
            'name' => 'Voir les ventes',
            'slug' => 'sales.view',
            'description' => null,
            'is_active' => true,
        ]);

        $create = Permission::create([
            'module' => 'sales',
            'name' => 'Créer une vente',
            'slug' => 'sales.create',
            'description' => null,
            'is_active' => true,
        ]);

        $finalize = Permission::create([
            'module' => 'sales',
            'name' => 'Finaliser une vente',
            'slug' => 'sales.finalize',
            'description' => null,
            'is_active' => true,
        ]);

        $role->permissions()->attach([$view->id, $create->id]);

        $organization->users()->attach($user, ['role' => 'member']);

        $user->organizationRoleAssignments()->create([
            'organization_id' => $organization->id,
            'role_id' => $role->id,
        ]);

        $user->permissionsForOrganization($organization)->attach(
            $finalize->id,
            [
                'organization_id' => $organization->id,
                'granted' => true,
            ]
        );

        $permissions = $this->service->effectivePermissions(
            $user,
            $organization
        );

        $this->assertEqualsCanonicalizing(
            [
                'sales.view',
                'sales.create',
                'sales.finalize',
            ],
            $permissions
        );
    }

    public function test_effective_permissions_remove_explicitly_denied_role_permission(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();

        $role = Role::create([
            'organization_id' => null,
            'name' => 'Manager',
            'slug' => 'manager',
            'description' => null,
            'is_system' => true,
            'is_active' => true,
        ]);

        $view = Permission::create([
            'module' => 'sales',
            'name' => 'Voir les ventes',
            'slug' => 'sales.view',
            'description' => null,
            'is_active' => true,
        ]);

        $create = Permission::create([
            'module' => 'sales',
            'name' => 'Créer une vente',
            'slug' => 'sales.create',
            'description' => null,
            'is_active' => true,
        ]);

        $role->permissions()->attach([$view->id, $create->id]);

        $organization->users()->attach($user, ['role' => 'member']);

        $user->organizationRoleAssignments()->create([
            'organization_id' => $organization->id,
            'role_id' => $role->id,
        ]);

        $user->permissionsForOrganization($organization)->attach(
            $view->id,
            [
                'organization_id' => $organization->id,
                'granted' => false,
            ]
        );

        $permissions = $this->service->effectivePermissions(
            $user,
            $organization
        );

        $this->assertEqualsCanonicalizing(
            ['sales.create'],
            $permissions
        );
    }
}