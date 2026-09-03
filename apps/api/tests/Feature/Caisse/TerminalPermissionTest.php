<?php

namespace Tests\Feature\Caisse;

use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Services\Rbac\PermissionService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TerminalPermissionTest extends TestCase
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

    public function test_manager_has_terminal_view_permission(): void
    {
        [$user, $organization] = $this->userWithRole('manager');

        $this->assertTrue(
            app(PermissionService::class)->hasPermission(
                $user,
                $organization,
                'terminals.view'
            )
        );
    }

    public function test_manager_has_not_terminal_manage_permission(): void
    {
        [$user, $organization] = $this->userWithRole('manager');

        $this->assertFalse(
            app(PermissionService::class)->hasPermission(
                $user,
                $organization,
                'terminals.manage'
            )
        );
    }

    public function test_admin_has_terminal_manage_permission(): void
    {
        [$user, $organization] = $this->userWithRole('admin');

        $this->assertTrue(
            app(PermissionService::class)->hasPermission(
                $user,
                $organization,
                'terminals.manage'
            )
        );
    }

    public function test_cashier_has_terminal_view_permission(): void
    {
        [$user, $organization] = $this->userWithRole('cashier');

        $this->assertTrue(
            app(PermissionService::class)->hasPermission(
                $user,
                $organization,
                'terminals.view'
            )
        );
    }

    public function test_cashier_has_not_terminal_manage_permission(): void
    {
        [$user, $organization] = $this->userWithRole('cashier');

        $this->assertFalse(
            app(PermissionService::class)->hasPermission(
                $user,
                $organization,
                'terminals.manage'
            )
        );
    }
}