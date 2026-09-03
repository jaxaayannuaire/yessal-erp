<?php

namespace Tests\Feature\Caisse;

use App\Models\Caisse\CashMovement;
use App\Models\Caisse\CashSession;
use App\Models\Caisse\Sale;
use App\Models\Caisse\SalePayment;
use App\Models\Caisse\Shop;
use App\Models\Caisse\Terminal;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentApiTest extends TestCase
{
    use RefreshDatabase;

    private function context(): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $organization->users()->attach($user->id, ['role' => 'owner']);

        $plan = Plan::factory()->withCaisseEntitlement()->create(['is_active' => true]);

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
        return ['X-Organization-Id' => $organizationId];
    }

    private function saleContext(
        Organization $organization,
        User $user
    ): array {
        $shop = Shop::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $terminal = Terminal::factory()->create([
            'shop_id' => $shop->id,
        ]);

        $session = CashSession::factory()->create([
            'organization_id' => $organization->id,
            'shop_id' => $shop->id,
            'terminal_id' => $terminal->id,
            'opened_by' => $user->id,
            'opening_amount' => 10000,
            'status' => 'open',
        ]);

        $sale = Sale::factory()->create([
            'organization_id' => $organization->id,
            'shop_id' => $shop->id,
            'terminal_id' => $terminal->id,
            'cash_session_id' => $session->id,
            'cashier_user_id' => $user->id,
            'status' => 'draft',
            'subtotal' => 10000,
            'total_amount' => 10000,
            'paid_amount' => 0,
            'due_amount' => 10000,
        ]);

        return [$shop, $terminal, $session, $sale];
    }

    public function test_user_can_make_full_cash_payment(): void
    {
        [$user, $organization] = $this->context();
        [, , $session, $sale] = $this->saleContext($organization, $user);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson(
                "/api/v1/caisse/sales/{$sale->id}/payments/cash",
                [
                    'amount' => 10000,
                    'reference' => 'CASH-001',
                ]
            );

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('payment.amount', 10000)
            ->assertJsonPath('payment.payment_method', 'cash')
            ->assertJsonPath('payment.status', 'confirmed')
            ->assertJsonPath('sale.paid_amount', 10000)
            ->assertJsonPath('sale.due_amount', 0)
            ->assertJsonPath('sale.status', 'paid');

        $this->assertDatabaseHas('sale_payments', [
            'sale_id' => $sale->id,
            'amount' => 10000,
            'status' => 'confirmed',
            'external_reference' => 'CASH-001',
        ]);
    }

    public function test_partial_cash_payment_updates_sale(): void
    {
        [$user, $organization] = $this->context();
        [, , $session, $sale] = $this->saleContext($organization, $user);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson(
                "/api/v1/caisse/sales/{$sale->id}/payments/cash",
                ['amount' => 4000]
            );

        $response
            ->assertCreated()
            ->assertJsonPath('payment.amount', 4000)
            ->assertJsonPath('sale.paid_amount', 4000)
            ->assertJsonPath('sale.due_amount', 6000)
            ->assertJsonPath('sale.status', 'partially_paid');
    }

    public function test_multiple_cash_payments_can_complete_sale(): void
    {
        [$user, $organization] = $this->context();
        [, , $session, $sale] = $this->saleContext($organization, $user);

        $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson(
                "/api/v1/caisse/sales/{$sale->id}/payments/cash",
                ['amount' => 4000]
            )
            ->assertCreated();

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson(
                "/api/v1/caisse/sales/{$sale->id}/payments/cash",
                ['amount' => 6000]
            );

        $response
            ->assertCreated()
            ->assertJsonPath('sale.paid_amount', 10000)
            ->assertJsonPath('sale.due_amount', 0)
            ->assertJsonPath('sale.status', 'paid');

        $this->assertDatabaseCount('sale_payments', 2);
    }

    public function test_payment_cannot_exceed_remaining_amount(): void
    {
        [$user, $organization] = $this->context();
        [, , $session, $sale] = $this->saleContext($organization, $user);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson(
                "/api/v1/caisse/sales/{$sale->id}/payments/cash",
                ['amount' => 10001]
            );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('amount');
    }

    public function test_zero_payment_is_rejected(): void
    {
        [$user, $organization] = $this->context();
        [, , , $sale] = $this->saleContext($organization, $user);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson(
                "/api/v1/caisse/sales/{$sale->id}/payments/cash",
                ['amount' => 0]
            );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('amount');
    }

    public function test_negative_payment_is_rejected(): void
    {
        [$user, $organization] = $this->context();
        [, , , $sale] = $this->saleContext($organization, $user);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson(
                "/api/v1/caisse/sales/{$sale->id}/payments/cash",
                ['amount' => -100]
            );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('amount');
    }

    public function test_payment_on_closed_session_is_rejected(): void
    {
        [$user, $organization] = $this->context();
        [, , $session, $sale] = $this->saleContext($organization, $user);

        $session->update([
            'status' => 'closed',
            'closed_by' => $user->id,
            'closed_at' => now(),
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson(
                "/api/v1/caisse/sales/{$sale->id}/payments/cash",
                ['amount' => 5000]
            );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cash_session_id');
    }

    public function test_finalized_sale_cannot_receive_payment(): void
    {
        [$user, $organization] = $this->context();
        [, , , $sale] = $this->saleContext($organization, $user);

        $sale->update([
            'status' => 'finalized',
            'paid_amount' => 10000,
            'due_amount' => 0,
            'finalized_at' => now(),
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson(
                "/api/v1/caisse/sales/{$sale->id}/payments/cash",
                ['amount' => 1000]
            );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sale');
    }

    public function test_payment_creates_cash_movement(): void
    {
        [$user, $organization] = $this->context();
        [, , $session, $sale] = $this->saleContext($organization, $user);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson(
                "/api/v1/caisse/sales/{$sale->id}/payments/cash",
                ['amount' => 5000]
            );

        $response->assertCreated();

        $paymentId = $response->json('payment.id');

        $this->assertDatabaseHas('cash_movements', [
            'organization_id' => $organization->id,
            'cash_session_id' => $session->id,
            'type' => 'sale',
            'amount' => 5000,
            'created_by' => $user->id,
            'reference' => 'sale_payment:' . $paymentId,
        ]);
    }

    public function test_user_can_list_sale_payments(): void
    {
        [$user, $organization] = $this->context();
        [, , , $sale] = $this->saleContext($organization, $user);

        SalePayment::create([
            'sale_id' => $sale->id,
            'payment_method' => 'cash',
            'provider' => 'cash',
            'amount' => 5000,
            'change_amount' => 0,
            'status' => 'confirmed',
            'external_reference' => 'TEST-001',
            'declared_at' => now(),
            'confirmed_at' => now(),
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->getJson(
                "/api/v1/caisse/sales/{$sale->id}/payments"
            );

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'payments');
    }

    public function test_payment_from_another_organization_is_rejected(): void
    {
        [$user, $organization] = $this->context();

        $otherOrganization = Organization::factory()->create();
        $otherUser = User::factory()->create();

        $otherOrganization->users()->attach(
            $otherUser->id,
            ['role' => 'owner']
        );

        [, , , $sale] = $this->saleContext(
            $otherOrganization,
            $otherUser
        );

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson(
                "/api/v1/caisse/sales/{$sale->id}/payments/cash",
                ['amount' => 5000]
            );

        $response->assertForbidden();

        $this->assertDatabaseCount('sale_payments', 0);
        $this->assertDatabaseCount('cash_movements', 0);
    }
}
