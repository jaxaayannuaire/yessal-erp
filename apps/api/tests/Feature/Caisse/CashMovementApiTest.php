<?php

namespace Tests\Feature\Caisse;

use App\Models\Caisse\CashMovement;
use App\Models\Caisse\CashSession;
use App\Models\Caisse\Shop;
use App\Models\Caisse\Terminal;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashMovementApiTest extends TestCase
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

    private function userWithRole(Organization $organization, string $role): User
    {
        $user = User::factory()->create();
        $organization->users()->attach($user->id, ['role' => 'member']);
        $user->organizationRoleAssignments()->create([
            'organization_id' => $organization->id,
            'role_id' => Role::query()->where('slug', $role)->firstOrFail()->id,
        ]);

        return $user;
    }

    private function headers(Organization $organization): array
    {
        return ['X-Organization-Id' => (string) $organization->id];
    }

    private function cashSession(
        Organization $organization,
        User $user,
        string $status = 'open',
        int $openingAmount = 100
    ): CashSession {
        $shop = Shop::factory()->create([
            'organization_id' => $organization->id,
        ]);
        $terminal = Terminal::factory()->create(['shop_id' => $shop->id]);

        return CashSession::factory()->create([
            'organization_id' => $organization->id,
            'shop_id' => $shop->id,
            'terminal_id' => $terminal->id,
            'opened_by' => $user->id,
            'status' => $status,
            'opening_amount' => $openingAmount,
            'opened_at' => now(),
        ]);
    }

    public function test_security_chain_and_cash_movement_permissions_are_enforced(): void
    {
        $this->getJson('/api/v1/caisse/cash-sessions/1/movements')
            ->assertUnauthorized();

        $organization = $this->organizationWithAccess();
        $owner = $this->owner($organization);
        $session = $this->cashSession($organization, $owner);

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson("/api/v1/caisse/cash-sessions/{$session->id}/movements")
            ->assertForbidden();

        $member = User::factory()->create();
        $organization->users()->attach($member->id, ['role' => 'member']);

        $this->actingAs($member, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->getJson("/api/v1/caisse/cash-sessions/{$session->id}/movements")
            ->assertForbidden();

        $cashier = $this->userWithRole($organization, 'cashier');

        $this->actingAs($cashier, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->getJson("/api/v1/caisse/cash-sessions/{$session->id}/movements")
            ->assertOk();

        $this->actingAs($cashier, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson("/api/v1/caisse/cash-sessions/{$session->id}/movements", [
                'type' => 'cash_in',
                'amount' => 100,
                'reason' => 'Fond de caisse',
            ])
            ->assertForbidden();

        $manager = $this->userWithRole($organization, 'manager');

        $this->actingAs($manager, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson("/api/v1/caisse/cash-sessions/{$session->id}/movements", [
                'type' => 'cash_in',
                'amount' => 100,
                'reason' => 'Fond de caisse',
            ])
            ->assertCreated();
    }

    public function test_subscription_and_entitlement_are_required_for_manual_movements(): void
    {
        $organization = Organization::factory()->create();
        $owner = $this->owner($organization);
        $session = $this->cashSession($organization, $owner);
        $payload = [
            'type' => 'cash_in',
            'amount' => 100,
            'reason' => 'Fond de caisse',
        ];

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson(
                "/api/v1/caisse/cash-sessions/{$session->id}/movements",
                $payload
            )
            ->assertForbidden();

        $plan = Plan::factory()->create();
        Subscription::factory()->create([
            'organization_id' => $organization->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addMonth(),
        ]);

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson(
                "/api/v1/caisse/cash-sessions/{$session->id}/movements",
                $payload
            )
            ->assertForbidden();
    }

    public function test_owner_can_create_list_and_close_with_manual_movements(): void
    {
        $organization = $this->organizationWithAccess();
        $owner = $this->owner($organization);
        $session = $this->cashSession($organization, $owner, openingAmount: 100);
        $otherUser = User::factory()->create();

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson("/api/v1/caisse/cash-sessions/{$session->id}/movements", [
                'type' => 'cash_in',
                'amount' => 50,
                'reason' => 'Apport documenté',
                'organization_id' => 999999,
                'created_by' => $otherUser->id,
            ])
            ->assertCreated()
            ->assertJsonPath('movement.organization_id', $organization->id)
            ->assertJsonPath('movement.created_by', $owner->id)
            ->assertJsonPath('movement.type', 'cash_in');

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson("/api/v1/caisse/cash-sessions/{$session->id}/movements", [
                'type' => 'cash_out',
                'amount' => 20,
                'reason' => 'Petite dépense',
            ])
            ->assertCreated();

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->getJson("/api/v1/caisse/cash-sessions/{$session->id}")
            ->assertOk()
            ->assertJsonPath('theoretical_amount', 130);

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->getJson("/api/v1/caisse/cash-sessions/{$session->id}/movements")
            ->assertOk()
            ->assertJsonCount(2, 'movements.data');

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson("/api/v1/caisse/cash-sessions/{$session->id}/close", [
                'counted_amount' => 130,
            ])
            ->assertOk()
            ->assertJsonPath('theoretical_amount', 130)
            ->assertJsonPath('cash_session.status', 'closed');
    }

    public function test_manual_movement_requires_valid_data_and_an_open_session(): void
    {
        $organization = $this->organizationWithAccess();
        $owner = $this->owner($organization);
        $openSession = $this->cashSession($organization, $owner);
        $closedSession = $this->cashSession($organization, $owner, 'closed');

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson("/api/v1/caisse/cash-sessions/{$openSession->id}/movements", [
                'type' => 'cash_in',
                'amount' => 0,
                'reason' => '',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['amount', 'reason']);

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson("/api/v1/caisse/cash-sessions/{$closedSession->id}/movements", [
                'type' => 'cash_out',
                'amount' => 20,
                'reason' => 'Retrait après clôture',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cash_session');
    }

    public function test_sessions_and_movements_from_another_organization_are_inaccessible(): void
    {
        $organization = $this->organizationWithAccess();
        $owner = $this->owner($organization);
        $otherOrganization = $this->organizationWithAccess();
        $otherOwner = $this->owner($otherOrganization);
        $otherSession = $this->cashSession($otherOrganization, $otherOwner);

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->getJson("/api/v1/caisse/cash-sessions/{$otherSession->id}/movements")
            ->assertForbidden();

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson("/api/v1/caisse/cash-sessions/{$otherSession->id}/movements", [
                'type' => 'cash_in',
                'amount' => 20,
                'reason' => 'Tentative interdite',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('cash_movements', 0);
    }

    public function test_reporting_counts_only_manual_cash_movements_with_filters_and_tenant_isolation(): void
    {
        $organization = $this->organizationWithAccess();
        $owner = $this->owner($organization);
        $session = $this->cashSession($organization, $owner);

        CashMovement::create([
            'organization_id' => $organization->id,
            'cash_session_id' => $session->id,
            'type' => 'sale',
            'amount' => 200,
            'created_by' => $owner->id,
            'created_at' => now(),
        ]);

        foreach ([
            ['type' => 'cash_in', 'amount' => 50, 'reason' => 'Apport'],
            ['type' => 'cash_out', 'amount' => 20, 'reason' => 'Dépense'],
        ] as $movement) {
            $this->actingAs($owner, 'sanctum')
                ->withHeaders($this->headers($organization))
                ->postJson(
                    "/api/v1/caisse/cash-sessions/{$session->id}/movements",
                    $movement
                )
                ->assertCreated();
        }

        $otherOrganization = $this->organizationWithAccess();
        $otherOwner = $this->owner($otherOrganization);
        $otherSession = $this->cashSession($otherOrganization, $otherOwner);
        CashMovement::create([
            'organization_id' => $otherOrganization->id,
            'cash_session_id' => $otherSession->id,
            'type' => 'cash_in',
            'amount' => 999,
            'created_by' => $otherOwner->id,
            'created_at' => now(),
        ]);

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->getJson('/api/v1/caisse/reports/overview?from='.now()->toDateString().'&to='.now()->toDateString().'&shop_id='.$session->shop_id)
            ->assertOk()
            ->assertJsonPath('cash_movements.cash_in_amount', 50)
            ->assertJsonPath('cash_movements.cash_out_amount', 20)
            ->assertJsonPath('cash_movements.count', 2)
            ->assertJsonPath('cash_movements.net_amount', 30);
    }
}
