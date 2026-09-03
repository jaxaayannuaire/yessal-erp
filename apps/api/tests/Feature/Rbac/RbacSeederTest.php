<?php

namespace Tests\Feature\Rbac;

use App\Models\Permission;
use App\Models\Role;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RbacSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_route_permission_middlewares_are_seeded(): void
    {
        $this->seed(RbacSeeder::class);

        $routePermissions = collect(Route::getRoutes()->getRoutes())
            ->flatMap(fn ($route) => $route->gatherMiddleware())
            ->filter(fn (string $middleware) => str_starts_with($middleware, 'permission:'))
            ->map(fn (string $middleware) => substr($middleware, strlen('permission:')))
            ->unique()
            ->sort()
            ->values();

        $seededPermissions = Permission::query()
            ->whereIn('slug', $routePermissions)
            ->pluck('slug')
            ->sort()
            ->values();

        $this->assertSame(
            $routePermissions->all(),
            $seededPermissions->all()
        );
    }

    public function test_system_roles_receive_the_expected_caisse_permissions(): void
    {
        $this->seed(RbacSeeder::class);

        $permissionsByRole = Role::query()
            ->whereIn('slug', ['admin', 'manager', 'cashier'])
            ->with('permissions:permissions.id,slug')
            ->get()
            ->mapWithKeys(fn (Role $role) => [
                $role->slug => $role->permissions->pluck('slug')->all(),
            ]);

        foreach (['devices.view', 'devices.manage', 'cash.view', 'sync.push'] as $permission) {
            $this->assertContains($permission, $permissionsByRole['admin']);
        }

        $this->assertContains('devices.view', $permissionsByRole['manager']);
        $this->assertContains('devices.manage', $permissionsByRole['manager']);
        $this->assertContains('cash.view', $permissionsByRole['manager']);
        $this->assertContains('sync.push', $permissionsByRole['manager']);

        $this->assertContains('devices.view', $permissionsByRole['cashier']);
        $this->assertNotContains('devices.manage', $permissionsByRole['cashier']);
        $this->assertContains('cash.view', $permissionsByRole['cashier']);
        $this->assertContains('sync.push', $permissionsByRole['cashier']);
    }
}
