<?php

namespace Tests\Feature\Caisse;

use App\Models\Caisse\CashSession;
use App\Models\Caisse\Product;
use App\Models\Caisse\Sale;
use App\Models\Caisse\Shop;
use App\Models\Caisse\Terminal;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SaleApiTest extends TestCase
{
    use RefreshDatabase;

    private function context(): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $organization->users()->attach($user->id, ['role' => 'owner']);

        $plan = Plan::factory()->create(['is_active' => true]);

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

    private function terminalFor(Organization $organization): Terminal
    {
        $shop = Shop::factory()->create([
            'organization_id' => $organization->id,
        ]);

        return Terminal::factory()->create([
            'shop_id' => $shop->id,
        ]);
    }

    private function sessionFor(
        Organization $organization,
        User $user,
        Terminal $terminal,
        int $openingAmount = 10000
    ): CashSession {
        return CashSession::factory()->create([
            'organization_id' => $organization->id,
            'shop_id' => $terminal->shop_id,
            'terminal_id' => $terminal->id,
            'opened_by' => $user->id,
            'opening_amount' => $openingAmount,
            'status' => 'open',
        ]);
    }

    private function salePayload(
        Terminal $terminal,
        CashSession $session,
        array $overrides = []
    ): array {
        return array_replace_recursive([
            'shop_id' => $terminal->shop_id,
            'terminal_id' => $terminal->id,
            'cash_session_id' => $session->id,
            'local_uuid' => (string) Str::uuid(),
            'receipt_number' => 'TST-' . fake()->unique()->numberBetween(10000, 99999),
            'currency' => 'XOF',
            'lines' => [
                [
                    'product_name_snapshot' => 'Produit Test',
                    'sku_snapshot' => 'TEST-001',
                    'barcode_snapshot' => '123456789',
                    'quantity' => 2,
                    'unit_price' => 5000,
                    'discount_amount' => 0,
                    'tax_amount' => 0,
                ],
            ],
        ], $overrides);
    }

    public function test_user_can_create_sale(): void
    {
        [$user, $organization] = $this->context();

        $terminal = $this->terminalFor($organization);
        $session = $this->sessionFor($organization, $user, $terminal);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson('/api/v1/caisse/sales', $this->salePayload(
                $terminal,
                $session
            ));

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('sale.organization_id', $organization->id)
            ->assertJsonPath('sale.shop_id', $terminal->shop_id)
            ->assertJsonPath('sale.terminal_id', $terminal->id)
            ->assertJsonPath('sale.cash_session_id', $session->id)
            ->assertJsonPath('sale.cashier_user_id', $user->id)
            ->assertJsonPath('sale.status', 'draft')
            ->assertJsonPath('sale.subtotal', 10000)
            ->assertJsonPath('sale.total_amount', 10000)
            ->assertJsonPath('sale.due_amount', 10000);

        $this->assertDatabaseHas('sales', [
            'organization_id' => $organization->id,
            'cashier_user_id' => $user->id,
            'total_amount' => 10000,
            'status' => 'draft',
        ]);
    }

    public function test_cashier_user_id_cannot_be_supplied_by_client(): void
    {
        [$user, $organization] = $this->context();

        $attacker = User::factory()->create();
        $terminal = $this->terminalFor($organization);
        $session = $this->sessionFor($organization, $user, $terminal);

        $payload = $this->salePayload($terminal, $session, [
            'cashier_user_id' => $attacker->id,
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson('/api/v1/caisse/sales', $payload);

        $response
            ->assertCreated()
            ->assertJsonPath('sale.cashier_user_id', $user->id);
    }

    public function test_same_local_uuid_does_not_create_duplicate_sale(): void
    {
        [$user, $organization] = $this->context();

        $terminal = $this->terminalFor($organization);
        $session = $this->sessionFor($organization, $user, $terminal);

        $payload = $this->salePayload($terminal, $session);

        $first = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson('/api/v1/caisse/sales', $payload);

        $second = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson('/api/v1/caisse/sales', $payload);

        $first->assertCreated();
        $second->assertCreated();

        $this->assertSame(
            $first->json('sale.id'),
            $second->json('sale.id')
        );

        $this->assertDatabaseCount('sales', 1);
    }

    public function test_product_snapshot_is_taken_from_product(): void
    {
        [$user, $organization] = $this->context();

        $terminal = $this->terminalFor($organization);
        $session = $this->sessionFor($organization, $user, $terminal);

        $product = Product::factory()->create([
            'shop_id' => $terminal->shop_id,
            'name' => 'Café Touba',
            'sku' => 'CAFE-001',
            'barcode' => '987654321',
        ]);

        $payload = $this->salePayload($terminal, $session, [
            'lines' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 2500,
                ],
            ],
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson('/api/v1/caisse/sales', $payload);

        $response
            ->assertCreated()
            ->assertJsonPath(
                'sale.lines.0.product_name_snapshot',
                'Café Touba'
            )
            ->assertJsonPath(
                'sale.lines.0.sku_snapshot',
                'CAFE-001'
            )
            ->assertJsonPath(
                'sale.lines.0.barcode_snapshot',
                '987654321'
            );
    }

    public function test_sale_from_another_organization_is_rejected(): void
    {
        [$user, $organization] = $this->context();

        $otherOrganization = Organization::factory()->create();
        $otherUser = User::factory()->create();

        $otherOrganization->users()->attach($otherUser->id, [
            'role' => 'owner',
        ]);

        $terminal = $this->terminalFor($otherOrganization);
        $session = $this->sessionFor(
            $otherOrganization,
            $otherUser,
            $terminal
        );

        $sale = Sale::factory()->create([
            'organization_id' => $otherOrganization->id,
            'shop_id' => $terminal->shop_id,
            'terminal_id' => $terminal->id,
            'cash_session_id' => $session->id,
            'cashier_user_id' => $otherUser->id,
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->getJson("/api/v1/caisse/sales/{$sale->id}");

        $response->assertForbidden();
    }

    public function test_shop_from_another_organization_is_rejected(): void
    {
        [$user, $organization] = $this->context();

        $otherOrganization = Organization::factory()->create();
        $terminal = $this->terminalFor($otherOrganization);

        $session = $this->sessionFor(
            $otherOrganization,
            User::factory()->create(),
            $terminal
        );

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson('/api/v1/caisse/sales', $this->salePayload(
                $terminal,
                $session
            ));

        $response->assertForbidden();
    }

    public function test_terminal_and_shop_must_match(): void
    {
        [$user, $organization] = $this->context();

        $terminalA = $this->terminalFor($organization);
        $terminalB = $this->terminalFor($organization);

        $session = $this->sessionFor(
            $organization,
            $user,
            $terminalA
        );

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson('/api/v1/caisse/sales', $this->salePayload(
                $terminalA,
                $session,
                [
                    'terminal_id' => $terminalB->id,
                ]
            ));

		$response->assertForbidden();
    }

    public function test_closed_cash_session_cannot_receive_sale(): void
    {
        [$user, $organization] = $this->context();

        $terminal = $this->terminalFor($organization);

        $session = $this->sessionFor(
            $organization,
            $user,
            $terminal
        );

        $session->update([
            'status' => 'closed',
            'closed_by' => $user->id,
            'closed_at' => now(),
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson('/api/v1/caisse/sales', $this->salePayload(
                $terminal,
                $session
            ));

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cash_session_id');
    }

    public function test_user_can_show_sale(): void
    {
        [$user, $organization] = $this->context();

        $terminal = $this->terminalFor($organization);
        $session = $this->sessionFor($organization, $user, $terminal);

        $create = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson('/api/v1/caisse/sales', $this->salePayload(
                $terminal,
                $session
            ));

        $saleId = $create->json('sale.id');

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->getJson("/api/v1/caisse/sales/{$saleId}");

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('sale.id', $saleId);
    }

    public function test_unpaid_sale_cannot_be_finalized(): void
    {
        [$user, $organization] = $this->context();

        $terminal = $this->terminalFor($organization);
        $session = $this->sessionFor($organization, $user, $terminal);

        $create = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson('/api/v1/caisse/sales', $this->salePayload(
                $terminal,
                $session
            ));

        $saleId = $create->json('sale.id');

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson(
                "/api/v1/caisse/sales/{$saleId}/finalize"
            );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sale');
    }

    public function test_paid_sale_can_be_finalized(): void
    {
        [$user, $organization] = $this->context();

        $terminal = $this->terminalFor($organization);
        $session = $this->sessionFor($organization, $user, $terminal);

        $sale = Sale::factory()->create([
            'organization_id' => $organization->id,
            'shop_id' => $terminal->shop_id,
            'terminal_id' => $terminal->id,
            'cash_session_id' => $session->id,
            'cashier_user_id' => $user->id,
            'status' => 'paid',
            'subtotal' => 10000,
            'total_amount' => 10000,
            'paid_amount' => 10000,
            'due_amount' => 0,
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson(
                "/api/v1/caisse/sales/{$sale->id}/finalize"
            );

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('sale.status', 'finalized');

        $this->assertDatabaseHas('sales', [
            'id' => $sale->id,
            'status' => 'finalized',
        ]);

        $this->assertNotNull(
            Sale::findOrFail($sale->id)->finalized_at
        );
    }

    public function test_finalized_sale_cannot_be_finalized_twice(): void
    {
        [$user, $organization] = $this->context();

        $terminal = $this->terminalFor($organization);
        $session = $this->sessionFor($organization, $user, $terminal);

        $sale = Sale::factory()->create([
            'organization_id' => $organization->id,
            'shop_id' => $terminal->shop_id,
            'terminal_id' => $terminal->id,
            'cash_session_id' => $session->id,
            'cashier_user_id' => $user->id,
            'status' => 'finalized',
            'subtotal' => 10000,
            'total_amount' => 10000,
            'paid_amount' => 10000,
            'due_amount' => 0,
            'finalized_at' => now(),
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson(
                "/api/v1/caisse/sales/{$sale->id}/finalize"
            );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sale');
    }

    public function test_finalized_sale_cannot_be_modified_as_normal_sale(): void
    {
        [$user, $organization] = $this->context();

        $terminal = $this->terminalFor($organization);
        $session = $this->sessionFor($organization, $user, $terminal);

        $sale = Sale::factory()->create([
            'organization_id' => $organization->id,
            'shop_id' => $terminal->shop_id,
            'terminal_id' => $terminal->id,
            'cash_session_id' => $session->id,
            'cashier_user_id' => $user->id,
            'status' => 'finalized',
            'subtotal' => 10000,
            'total_amount' => 10000,
            'paid_amount' => 10000,
            'due_amount' => 0,
            'finalized_at' => now(),
        ]);

        $sale->update([
            'status' => 'draft',
        ]);

        $this->assertSame(
            'draft',
            $sale->fresh()->status
        );
    }

    public function test_user_can_list_organization_sales(): void
    {
        [$user, $organization] = $this->context();

        $terminal = $this->terminalFor($organization);
        $session = $this->sessionFor($organization, $user, $terminal);

        Sale::factory()->count(3)->create([
            'organization_id' => $organization->id,
            'shop_id' => $terminal->shop_id,
            'terminal_id' => $terminal->id,
            'cash_session_id' => $session->id,
            'cashier_user_id' => $user->id,
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->getJson('/api/v1/caisse/sales');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'sales.data');
    }
}