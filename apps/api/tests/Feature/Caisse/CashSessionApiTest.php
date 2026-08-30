<?php

namespace Tests\Feature\Caisse;

use App\Models\Caisse\CashMovement;
use App\Models\Caisse\CashSession;
use App\Models\Caisse\Device;
use App\Models\Caisse\Shop;
use App\Models\Caisse\Terminal;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CashSessionApiTest extends TestCase
{
    use RefreshDatabase;

    private function context(): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $organization->users()->attach($user->id, [
            'role' => 'owner',
        ]);

        $plan = Plan::factory()->create([
            'is_active' => true,
        ]);

        Subscription::factory()->create([
            'organization_id' => $organization->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addMonth(),
        ]);

        return [$user, $organization];
    }

    private function headers(int $organizationId): array
    {
        return [
            'X-Organization-Id' => $organizationId,
        ];
    }

    private function terminalFor(Organization $organization): Terminal
    {
        $shop = Shop::factory()->create([
            'organization_id' => $organization->id,
        ]);

        return Terminal::factory()->create([
            'shop_id' => $shop->id,
        ]);
    }

    public function test_user_can_open_cash_session(): void
    {
        [$user, $organization] = $this->context();

        $terminal = $this->terminalFor($organization);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson('/api/v1/caisse/cash-sessions/open', [
                'terminal_id' => $terminal->id,
                'opening_amount' => 10000,
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'cash_session.organization_id',
                $organization->id
            )
            ->assertJsonPath(
                'cash_session.terminal_id',
                $terminal->id
            )
            ->assertJsonPath(
                'cash_session.opening_amount',
                10000
            )
            ->assertJsonPath(
                'cash_session.status',
                'open'
            )
            ->assertJsonPath(
                'cash_session.opener.id',
                $user->id
            );

        $this->assertDatabaseHas('cash_sessions', [
            'organization_id' => $organization->id,
            'terminal_id' => $terminal->id,
            'opened_by' => $user->id,
            'opening_amount' => 10000,
            'status' => 'open',
        ]);
    }

    public function test_opened_by_cannot_be_supplied_by_client(): void
    {
        [$user, $organization] = $this->context();

        $attacker = User::factory()->create();

        $terminal = $this->terminalFor($organization);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson('/api/v1/caisse/cash-sessions/open', [
                'terminal_id' => $terminal->id,
                'opening_amount' => 5000,
                'opened_by' => $attacker->id,
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath(
                'cash_session.opener.id',
                $user->id
            );

        $this->assertDatabaseMissing('cash_sessions', [
            'terminal_id' => $terminal->id,
            'opened_by' => $attacker->id,
        ]);
    }

    public function test_terminal_from_another_organization_is_rejected(): void
    {
        [$user, $organization] = $this->context();

        $otherOrganization = Organization::factory()->create();
        $terminal = $this->terminalFor($otherOrganization);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson('/api/v1/caisse/cash-sessions/open', [
                'terminal_id' => $terminal->id,
                'opening_amount' => 10000,
            ]);

        $response->assertForbidden();
    }

    public function test_device_from_another_organization_is_rejected(): void
    {
        [$user, $organization] = $this->context();

        $terminal = $this->terminalFor($organization);

        $otherOrganization = Organization::factory()->create();

        $device = Device::factory()->create([
            'organization_id' => $otherOrganization->id,
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson('/api/v1/caisse/cash-sessions/open', [
                'terminal_id' => $terminal->id,
                'device_id' => $device->id,
                'opening_amount' => 10000,
            ]);

        $response->assertForbidden();
    }

    public function test_device_from_another_shop_is_rejected(): void
    {
        [$user, $organization] = $this->context();

        $shopA = Shop::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $shopB = Shop::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $terminal = Terminal::factory()->create([
            'shop_id' => $shopA->id,
        ]);

        $device = Device::factory()->create([
            'organization_id' => $organization->id,
            'shop_id' => $shopB->id,
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson('/api/v1/caisse/cash-sessions/open', [
                'terminal_id' => $terminal->id,
                'device_id' => $device->id,
                'opening_amount' => 10000,
            ]);

        $response->assertForbidden();
    }

    public function test_device_assigned_to_another_terminal_is_rejected(): void
    {
        [$user, $organization] = $this->context();

        $shop = Shop::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $terminalA = Terminal::factory()->create([
            'shop_id' => $shop->id,
        ]);

        $terminalB = Terminal::factory()->create([
            'shop_id' => $shop->id,
        ]);

        $device = Device::factory()->create([
            'organization_id' => $organization->id,
            'shop_id' => $shop->id,
            'terminal_id' => $terminalB->id,
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson('/api/v1/caisse/cash-sessions/open', [
                'terminal_id' => $terminalA->id,
                'device_id' => $device->id,
                'opening_amount' => 10000,
            ]);

        $response->assertForbidden();
    }

    public function test_terminal_cannot_have_two_open_sessions(): void
    {
        [$user, $organization] = $this->context();

        $terminal = $this->terminalFor($organization);

        $first = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson('/api/v1/caisse/cash-sessions/open', [
                'terminal_id' => $terminal->id,
                'opening_amount' => 10000,
            ]);

        $first->assertCreated();

        $second = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson('/api/v1/caisse/cash-sessions/open', [
                'terminal_id' => $terminal->id,
                'opening_amount' => 20000,
            ]);

        $second
            ->assertUnprocessable()
            ->assertJsonValidationErrors('terminal_id');
    }

    public function test_user_can_list_current_organization_sessions(): void
    {
        [$user, $organization] = $this->context();

        $terminalA = $this->terminalFor($organization);
        $terminalB = $this->terminalFor($organization);

        CashSession::factory()->create([
            'organization_id' => $organization->id,
            'shop_id' => $terminalA->shop_id,
            'terminal_id' => $terminalA->id,
            'opened_by' => $user->id,
            'status' => 'open',
        ]);

        CashSession::factory()->create([
            'organization_id' => $organization->id,
            'shop_id' => $terminalB->shop_id,
            'terminal_id' => $terminalB->id,
            'opened_by' => $user->id,
            'status' => 'closed',
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->getJson('/api/v1/caisse/cash-sessions');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'cash_sessions');
    }

    public function test_session_from_another_organization_is_rejected(): void
    {
        [$user, $organization] = $this->context();

        $otherOrganization = Organization::factory()->create();
        $otherUser = User::factory()->create();

        $otherOrganization->users()->attach($otherUser->id, [
            'role' => 'owner',
        ]);

        $terminal = $this->terminalFor($otherOrganization);

        $session = CashSession::factory()->create([
            'organization_id' => $otherOrganization->id,
            'shop_id' => $terminal->shop_id,
            'terminal_id' => $terminal->id,
            'opened_by' => $otherUser->id,
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->getJson(
                "/api/v1/caisse/cash-sessions/{$session->id}"
            );

        $response->assertForbidden();
    }

    public function test_user_can_close_session_with_no_variance(): void
    {
        [$user, $organization] = $this->context();

        $terminal = $this->terminalFor($organization);

        $session = CashSession::factory()->create([
            'organization_id' => $organization->id,
            'shop_id' => $terminal->shop_id,
            'terminal_id' => $terminal->id,
            'opened_by' => $user->id,
            'opening_amount' => 10000,
            'status' => 'open',
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson(
                "/api/v1/caisse/cash-sessions/{$session->id}/close",
                [
                    'counted_amount' => 10000,
                ]
            );

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('cash_session.status', 'closed')
            ->assertJsonPath('cash_session.expected_amount', 10000)
            ->assertJsonPath('cash_session.counted_amount', 10000)
            ->assertJsonPath('cash_session.variance_amount', 0)
            ->assertJsonPath(
                'cash_session.closer.id',
                $user->id
            );
    }

    public function test_expected_amount_includes_cash_movements(): void
    {
        [$user, $organization] = $this->context();

        $terminal = $this->terminalFor($organization);

        $session = CashSession::factory()->create([
            'organization_id' => $organization->id,
            'shop_id' => $terminal->shop_id,
            'terminal_id' => $terminal->id,
            'opened_by' => $user->id,
            'opening_amount' => 10000,
            'status' => 'open',
        ]);

        CashMovement::create([
            'organization_id' => $organization->id,
            'cash_session_id' => $session->id,
            'type' => 'cash_in',
            'amount' => 5000,
        ]);

        CashMovement::create([
            'organization_id' => $organization->id,
            'cash_session_id' => $session->id,
            'type' => 'cash_out',
            'amount' => 2000,
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson(
                "/api/v1/caisse/cash-sessions/{$session->id}/close",
                [
                    'counted_amount' => 13000,
                ]
            );

        $response
            ->assertOk()
            ->assertJsonPath('cash_session.expected_amount', 13000)
            ->assertJsonPath('cash_session.variance_amount', 0);
    }

    public function test_variance_without_reason_is_rejected(): void
    {
        [$user, $organization] = $this->context();

        $terminal = $this->terminalFor($organization);

        $session = CashSession::factory()->create([
            'organization_id' => $organization->id,
            'shop_id' => $terminal->shop_id,
            'terminal_id' => $terminal->id,
            'opened_by' => $user->id,
            'opening_amount' => 10000,
            'status' => 'open',
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson(
                "/api/v1/caisse/cash-sessions/{$session->id}/close",
                [
                    'counted_amount' => 9000,
                ]
            );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('variance_reason');

        $this->assertDatabaseHas('cash_sessions', [
            'id' => $session->id,
            'status' => 'open',
        ]);
    }

    public function test_variance_with_reason_is_accepted(): void
    {
        [$user, $organization] = $this->context();

        $terminal = $this->terminalFor($organization);

        $session = CashSession::factory()->create([
            'organization_id' => $organization->id,
            'shop_id' => $terminal->shop_id,
            'terminal_id' => $terminal->id,
            'opened_by' => $user->id,
            'opening_amount' => 10000,
            'status' => 'open',
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson(
                "/api/v1/caisse/cash-sessions/{$session->id}/close",
                [
                    'counted_amount' => 9000,
                    'variance_reason' => 'Erreur de rendu monnaie.',
                ]
            );

        $response
            ->assertOk()
            ->assertJsonPath('cash_session.status', 'closed')
            ->assertJsonPath('cash_session.expected_amount', 10000)
            ->assertJsonPath('cash_session.counted_amount', 9000)
            ->assertJsonPath('cash_session.variance_amount', -1000)
            ->assertJsonPath(
                'cash_session.variance_reason',
                'Erreur de rendu monnaie.'
            );
    }

    public function test_closed_session_cannot_be_closed_again(): void
    {
        [$user, $organization] = $this->context();

        $terminal = $this->terminalFor($organization);

        $session = CashSession::factory()->create([
            'organization_id' => $organization->id,
            'shop_id' => $terminal->shop_id,
            'terminal_id' => $terminal->id,
            'opened_by' => $user->id,
            'closed_by' => $user->id,
            'status' => 'closed',
            'opening_amount' => 10000,
            'expected_amount' => 10000,
            'counted_amount' => 10000,
            'variance_amount' => 0,
            'opened_at' => now()->subHour(),
            'closed_at' => now()->subMinute(),
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson(
                "/api/v1/caisse/cash-sessions/{$session->id}/close",
                [
                    'counted_amount' => 10000,
                ]
            );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('session');
    }

    public function test_closed_by_cannot_be_supplied_by_client(): void
    {
        [$user, $organization] = $this->context();

        $attacker = User::factory()->create();
        $terminal = $this->terminalFor($organization);

        $session = CashSession::factory()->create([
            'organization_id' => $organization->id,
            'shop_id' => $terminal->shop_id,
            'terminal_id' => $terminal->id,
            'opened_by' => $user->id,
            'opening_amount' => 10000,
            'status' => 'open',
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson(
                "/api/v1/caisse/cash-sessions/{$session->id}/close",
                [
                    'counted_amount' => 10000,
                    'closed_by' => $attacker->id,
                ]
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'cash_session.closer.id',
                $user->id
            );

        $this->assertDatabaseMissing('cash_sessions', [
            'id' => $session->id,
            'closed_by' => $attacker->id,
        ]);
    }

    public function test_negative_opening_amount_is_rejected(): void
    {
        [$user, $organization] = $this->context();

        $terminal = $this->terminalFor($organization);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson('/api/v1/caisse/cash-sessions/open', [
                'terminal_id' => $terminal->id,
                'opening_amount' => -1,
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('opening_amount');
    }

    public function test_negative_counted_amount_is_rejected(): void
    {
        [$user, $organization] = $this->context();

        $terminal = $this->terminalFor($organization);

        $session = CashSession::factory()->create([
            'organization_id' => $organization->id,
            'shop_id' => $terminal->shop_id,
            'terminal_id' => $terminal->id,
            'opened_by' => $user->id,
            'opening_amount' => 10000,
            'status' => 'open',
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson(
                "/api/v1/caisse/cash-sessions/{$session->id}/close",
                [
                    'counted_amount' => -1,
                ]
            );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('counted_amount');
    }
}