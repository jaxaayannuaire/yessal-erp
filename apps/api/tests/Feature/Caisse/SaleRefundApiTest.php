<?php

namespace Tests\Feature\Caisse;

use App\Models\Caisse\CashMovement;
use App\Models\Caisse\CashSession;
use App\Models\Caisse\Sale;
use App\Models\Caisse\SalePayment;
use App\Models\Caisse\SaleReturn;
use App\Models\Caisse\Shop;
use App\Models\Caisse\Terminal;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SaleRefundApiTest extends TestCase
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

    private function saleContext(
        Organization $organization,
        User $user,
        string $sessionStatus = 'open',
        string $saleStatus = 'finalized',
        string $paymentMethod = 'cash'
    ): array {
        $shop = Shop::factory()->create(['organization_id' => $organization->id]);
        $terminal = Terminal::factory()->create(['shop_id' => $shop->id]);
        $session = CashSession::factory()->create([
            'organization_id' => $organization->id,
            'shop_id' => $shop->id,
            'terminal_id' => $terminal->id,
            'opened_by' => $user->id,
            'opening_amount' => 100,
            'status' => $sessionStatus,
            'opened_at' => now(),
        ]);
        $sale = Sale::factory()->create([
            'organization_id' => $organization->id,
            'shop_id' => $shop->id,
            'terminal_id' => $terminal->id,
            'cash_session_id' => $session->id,
            'cashier_user_id' => $user->id,
            'status' => $saleStatus,
            'subtotal' => 1000,
            'total_amount' => 1000,
            'paid_amount' => 1000,
            'due_amount' => 0,
            'finalized_at' => now(),
        ]);
        $payment = SalePayment::create([
            'sale_id' => $sale->id,
            'payment_method' => $paymentMethod,
            'provider' => $paymentMethod,
            'amount' => 1000,
            'change_amount' => 0,
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        if ($paymentMethod === 'cash') {
            CashMovement::create([
                'organization_id' => $organization->id,
                'cash_session_id' => $session->id,
                'type' => 'sale',
                'amount' => 1000,
                'reason' => 'Paiement vente',
                'reference' => 'sale_payment:'.$payment->id,
                'created_by' => $user->id,
                'created_at' => now(),
            ]);
        }

        return [$session, $sale, $payment];
    }

    public function test_refund_route_requires_the_caisse_security_chain_and_permission(): void
    {
        $this->postJson('/api/v1/caisse/sales/1/refund')->assertUnauthorized();

        $organization = $this->organizationWithAccess();
        $owner = $this->owner($organization);
        [, $sale, $payment] = $this->saleContext($organization, $owner);
        $payload = [
            'sale_payment_id' => $payment->id,
            'amount' => 100,
            'reason' => 'Erreur de caisse',
        ];

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->postJson("/api/v1/caisse/sales/{$sale->id}/refund", $payload)
            ->assertForbidden();

        $cashier = $this->userWithRole($organization, 'cashier');
        $this->actingAs($cashier, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson("/api/v1/caisse/sales/{$sale->id}/refund", $payload)
            ->assertForbidden();

        $manager = $this->userWithRole($organization, 'manager');
        $this->actingAs($manager, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson("/api/v1/caisse/sales/{$sale->id}/refund", $payload)
            ->assertCreated();
    }

    public function test_refund_requires_an_active_subscription_and_caisse_entitlement(): void
    {
        $organization = Organization::factory()->create();
        $owner = $this->owner($organization);
        [, $sale, $payment] = $this->saleContext($organization, $owner);
        $payload = [
            'sale_payment_id' => $payment->id,
            'amount' => 100,
            'reason' => 'Contrôle accès',
        ];

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson("/api/v1/caisse/sales/{$sale->id}/refund", $payload)
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
            ->postJson("/api/v1/caisse/sales/{$sale->id}/refund", $payload)
            ->assertForbidden();
    }

    public function test_cash_refunds_are_partial_then_total_and_reduce_theoretical_amount(): void
    {
        $organization = $this->organizationWithAccess();
        $owner = $this->owner($organization);
        [$session, $sale, $payment] = $this->saleContext($organization, $owner);

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson("/api/v1/caisse/sales/{$sale->id}/refund", [
                'sale_payment_id' => $payment->id,
                'amount' => 300,
                'reason' => 'Retour partiel',
            ])
            ->assertCreated()
            ->assertJsonPath('refund.amount', 300)
            ->assertJsonPath('refund.refund_method', 'cash')
            ->assertJsonPath('refund.status', 'completed')
            ->assertJsonPath('sale.status', 'finalized');

        $this->assertDatabaseHas('cash_movements', [
            'organization_id' => $organization->id,
            'cash_session_id' => $session->id,
            'type' => 'refund',
            'amount' => 300,
        ]);

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->getJson("/api/v1/caisse/cash-sessions/{$session->id}")
            ->assertOk()
            ->assertJsonPath('theoretical_amount', 800);

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson("/api/v1/caisse/sales/{$sale->id}/refund", [
                'sale_payment_id' => $payment->id,
                'amount' => 700,
                'reason' => 'Remboursement du solde',
            ])
            ->assertCreated()
            ->assertJsonPath('sale.status', 'refunded');

        $this->assertDatabaseCount('sale_returns', 2);
        $this->assertDatabaseCount('cash_movements', 3);
    }

    public function test_refund_amount_cannot_exceed_the_confirmed_payment_or_be_zero(): void
    {
        $organization = $this->organizationWithAccess();
        $owner = $this->owner($organization);
        [, $sale, $payment] = $this->saleContext($organization, $owner);

        foreach ([0, 1001] as $amount) {
            $this->actingAs($owner, 'sanctum')
                ->withHeaders($this->headers($organization))
                ->postJson("/api/v1/caisse/sales/{$sale->id}/refund", [
                    'sale_payment_id' => $payment->id,
                    'amount' => $amount,
                    'reason' => 'Montant invalide',
                ])
                ->assertUnprocessable();
        }

        $this->assertDatabaseCount('sale_returns', 0);
        $this->assertDatabaseCount('cash_movements', 1);
    }

    public function test_closed_session_and_non_cash_payment_are_not_refunded_locally(): void
    {
        $organization = $this->organizationWithAccess();
        $owner = $this->owner($organization);
        [, $closedSale, $closedPayment] = $this->saleContext(
            $organization,
            $owner,
            'closed'
        );

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson("/api/v1/caisse/sales/{$closedSale->id}/refund", [
                'sale_payment_id' => $closedPayment->id,
                'amount' => 100,
                'reason' => 'Session fermée',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cash_session');

        [, $cardSale, $cardPayment] = $this->saleContext(
            $organization,
            $owner,
            'open',
            'finalized',
            'card'
        );

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson("/api/v1/caisse/sales/{$cardSale->id}/refund", [
                'sale_payment_id' => $cardPayment->id,
                'amount' => 100,
                'reason' => 'Remboursement externe',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sale_payment_id');

        $this->assertDatabaseCount('sale_returns', 0);
    }

    public function test_refunds_are_tenant_scoped_and_cancellation_remains_independent(): void
    {
        $organization = $this->organizationWithAccess();
        $owner = $this->owner($organization);
        $otherOrganization = $this->organizationWithAccess();
        $otherOwner = $this->owner($otherOrganization);
        [, $otherSale, $otherPayment] = $this->saleContext(
            $otherOrganization,
            $otherOwner,
            'open',
            'cancelled'
        );

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson("/api/v1/caisse/sales/{$otherSale->id}/refund", [
                'sale_payment_id' => $otherPayment->id,
                'amount' => 100,
                'reason' => 'Tentative interdite',
            ])
            ->assertForbidden();

        $this->actingAs($otherOwner, 'sanctum')
            ->withHeaders($this->headers($otherOrganization))
            ->postJson("/api/v1/caisse/sales/{$otherSale->id}/refund", [
                'sale_payment_id' => $otherPayment->id,
                'amount' => 1000,
                'reason' => 'Remboursement après annulation',
            ])
            ->assertCreated()
            ->assertJsonPath('sale.status', 'cancelled');

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->getJson("/api/v1/caisse/sales/{$otherSale->id}/refunds")
            ->assertForbidden();
    }

    public function test_payment_must_belong_to_the_sale_being_refunded(): void
    {
        $organization = $this->organizationWithAccess();
        $owner = $this->owner($organization);
        [, $sale] = $this->saleContext($organization, $owner);
        [, , $otherPayment] = $this->saleContext($organization, $owner);

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson("/api/v1/caisse/sales/{$sale->id}/refund", [
                'sale_payment_id' => $otherPayment->id,
                'amount' => 100,
                'reason' => 'Paiement d’une autre vente',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sale_payment_id');

        $this->assertDatabaseCount('sale_returns', 0);
    }

    public function test_reporting_exposes_refunds_and_net_amount_without_changing_gross_amount(): void
    {
        $organization = $this->organizationWithAccess();
        $owner = $this->owner($organization);
        [, $sale, $payment] = $this->saleContext($organization, $owner);

        SaleReturn::create([
            'organization_id' => $organization->id,
            'sale_id' => $sale->id,
            'sale_payment_id' => $payment->id,
            'reference_number' => 'REF-'.Str::uuid(),
            'reason' => 'Test reporting',
            'amount' => 250,
            'refund_method' => 'cash',
            'status' => 'completed',
            'created_by' => $owner->id,
            'created_at' => now(),
        ]);

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->getJson('/api/v1/caisse/reports/overview?from='.now()->toDateString().'&to='.now()->toDateString())
            ->assertOk()
            ->assertJsonPath('sales.gross_amount', 1000)
            ->assertJsonPath('sales.net_amount', 750)
            ->assertJsonPath('refunds.count', 1)
            ->assertJsonPath('refunds.amount', 250);
    }
}
