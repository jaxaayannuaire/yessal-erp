<?php

namespace Tests\Feature\Caisse;

use App\Models\Caisse\CashSession;
use App\Models\Caisse\Customer;
use App\Models\Caisse\Product;
use App\Models\Caisse\Sale;
use App\Models\Caisse\SalePayment;
use App\Models\Caisse\Shop;
use App\Models\Caisse\StockLevel;
use App\Models\Caisse\StockLocation;
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

class ReportingApiTest extends TestCase
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

    private function userWithRole(Organization $organization, string $roleSlug): User
    {
        $user = User::factory()->create();
        $organization->users()->attach($user->id, ['role' => 'member']);
        $user->organizationRoleAssignments()->create([
            'organization_id' => $organization->id,
            'role_id' => Role::query()->where('slug', $roleSlug)->firstOrFail()->id,
        ]);

        return $user;
    }

    private function headers(Organization $organization): array
    {
        return ['X-Organization-Id' => (string) $organization->id];
    }

    private function sale(
        Organization $organization,
        Shop $shop,
        Terminal $terminal,
        CashSession $session,
        User $user,
        string $status,
        int $amount,
        ?\DateTimeInterface $finalizedAt = null
    ): Sale {
        return Sale::create([
            'organization_id' => $organization->id,
            'shop_id' => $shop->id,
            'terminal_id' => $terminal->id,
            'cash_session_id' => $session->id,
            'cashier_user_id' => $user->id,
            'local_uuid' => (string) Str::uuid(),
            'receipt_number' => 'REP-' . fake()->unique()->numerify('#####'),
            'status' => $status,
            'subtotal' => $amount,
            'total_amount' => $amount,
            'paid_amount' => $status === 'finalized' ? $amount : 0,
            'due_amount' => 0,
            'currency' => 'XOF',
            'finalized_at' => $finalizedAt,
        ]);
    }

    private function cashSession(
        Organization $organization,
        Shop $shop,
        Terminal $terminal,
        User $user,
        string $status = 'closed'
    ): CashSession {
        return CashSession::factory()->create([
            'organization_id' => $organization->id,
            'shop_id' => $shop->id,
            'terminal_id' => $terminal->id,
            'opened_by' => $user->id,
            'status' => $status,
            'opening_amount' => 100,
            'counted_amount' => $status === 'closed' ? 1100 : null,
            'opened_at' => now(),
        ]);
    }

    public function test_guest_context_subscription_entitlement_and_permission_are_required(): void
    {
        $this->getJson('/api/v1/caisse/reports/overview')->assertUnauthorized();

        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/caisse/reports/overview')
            ->assertForbidden();

        $organization = Organization::factory()->create();
        $owner = $this->owner($organization);

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->getJson('/api/v1/caisse/reports/overview')
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
            ->getJson('/api/v1/caisse/reports/overview')
            ->assertForbidden();

        $allowedOrganization = $this->organizationWithAccess();
        $cashier = $this->userWithRole($allowedOrganization, 'cashier');
        $manager = $this->userWithRole($allowedOrganization, 'manager');

        $this->actingAs($cashier, 'sanctum')
            ->withHeaders($this->headers($allowedOrganization))
            ->getJson('/api/v1/caisse/reports/overview')
            ->assertForbidden();

        $this->actingAs($manager, 'sanctum')
            ->withHeaders($this->headers($allowedOrganization))
            ->getJson('/api/v1/caisse/reports/overview')
            ->assertOk();
    }

    public function test_empty_overview_returns_zero_metrics_for_the_current_day(): void
    {
        $organization = $this->organizationWithAccess();
        $owner = $this->owner($organization);

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->getJson('/api/v1/caisse/reports/overview')
            ->assertOk()
            ->assertJsonPath('sales.count', 0)
            ->assertJsonPath('sales.gross_amount', 0)
            ->assertJsonPath('sales.paid_amount', 0)
            ->assertJsonPath('sales.cancelled_count', 0)
            ->assertJsonPath('cash_sessions.opened_count', 0)
            ->assertJsonPath('stock.products_with_stock', 0)
            ->assertJsonPath('customers.active_count', 0);
    }

    public function test_overview_aggregates_finalized_sales_payments_sessions_stock_and_shops_per_tenant(): void
    {
        $organization = $this->organizationWithAccess();
        $owner = $this->owner($organization);
        $shop = Shop::factory()->create(['organization_id' => $organization->id]);
        $secondShop = Shop::factory()->create(['organization_id' => $organization->id]);
        $terminal = Terminal::factory()->create(['shop_id' => $shop->id]);
        $secondTerminal = Terminal::factory()->create(['shop_id' => $secondShop->id]);
        $session = $this->cashSession($organization, $shop, $terminal, $owner);
        $secondSession = $this->cashSession(
            $organization,
            $secondShop,
            $secondTerminal,
            $owner,
            'open'
        );
        $finalized = $this->sale(
            $organization,
            $shop,
            $terminal,
            $session,
            $owner,
            'finalized',
            1000,
            now()
        );
        SalePayment::create([
            'sale_id' => $finalized->id,
            'payment_method' => 'cash',
            'provider' => 'cash',
            'amount' => 600,
            'change_amount' => 0,
            'status' => 'confirmed',
        ]);
        SalePayment::create([
            'sale_id' => $finalized->id,
            'payment_method' => 'card',
            'provider' => 'card',
            'amount' => 400,
            'change_amount' => 0,
            'status' => 'confirmed',
        ]);
        $oldSale = $this->sale(
            $organization,
            $shop,
            $terminal,
            $session,
            $owner,
            'finalized',
            700,
            now()->subDay()
        );
        SalePayment::create([
            'sale_id' => $oldSale->id,
            'payment_method' => 'cash',
            'amount' => 700,
            'status' => 'confirmed',
        ]);
        $cancelled = $this->sale(
            $organization,
            $secondShop,
            $secondTerminal,
            $secondSession,
            $owner,
            'cancelled',
            500,
            now()->subHour()
        );
        $cancelled->touch();
        Customer::factory()->create(['shop_id' => $shop->id, 'status' => 'active']);
        $location = StockLocation::factory()->create([
            'organization_id' => $organization->id,
            'shop_id' => $shop->id,
        ]);
        $product = Product::factory()->create(['shop_id' => $shop->id]);
        StockLevel::create([
            'stock_location_id' => $location->id,
            'product_id' => $product->id,
            'product_variant_id' => null,
            'quantity' => 3,
            'reserved_quantity' => 0,
        ]);

        $otherOrganization = $this->organizationWithAccess();
        $otherUser = $this->owner($otherOrganization);
        $otherShop = Shop::factory()->create([
            'organization_id' => $otherOrganization->id,
        ]);
        $otherTerminal = Terminal::factory()->create(['shop_id' => $otherShop->id]);
        $otherSession = $this->cashSession(
            $otherOrganization,
            $otherShop,
            $otherTerminal,
            $otherUser
        );
        $otherSale = $this->sale(
            $otherOrganization,
            $otherShop,
            $otherTerminal,
            $otherSession,
            $otherUser,
            'finalized',
            9000,
            now()
        );
        SalePayment::create([
            'sale_id' => $otherSale->id,
            'payment_method' => 'cash',
            'amount' => 9000,
            'status' => 'confirmed',
        ]);

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->getJson('/api/v1/caisse/reports/overview?from=' . now()->toDateString() . '&to=' . now()->toDateString())
            ->assertOk()
            ->assertJsonPath('sales.count', 1)
            ->assertJsonPath('sales.gross_amount', 1000)
            ->assertJsonPath('sales.paid_amount', 1000)
            ->assertJsonPath('sales.cancelled_count', 1)
            ->assertJsonPath('payments.cash.amount', 600)
            ->assertJsonPath('payments.card.amount', 400)
            ->assertJsonPath('cash_sessions.opened_count', 1)
            ->assertJsonPath('cash_sessions.closed_count', 1)
            ->assertJsonPath('cash_sessions.opening_amount_total', 200)
            ->assertJsonPath('cash_sessions.closing_amount_total', 1100)
            ->assertJsonPath('stock.products_with_stock', 1)
            ->assertJsonPath('stock.total_units', 3)
            ->assertJsonPath('customers.active_count', 1)
            ->assertJsonCount(2, 'shops');

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->getJson('/api/v1/caisse/reports/overview?shop_id=' . $shop->id)
            ->assertOk()
            ->assertJsonPath('sales.count', 1)
            ->assertJsonCount(1, 'shops');

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->getJson('/api/v1/caisse/reports/overview?shop_id=' . $otherShop->id)
            ->assertForbidden();
    }

    public function test_real_sale_flow_is_reflected_then_removed_from_revenue_after_cancellation(): void
    {
        $organization = $this->organizationWithAccess();
        $owner = $this->owner($organization);
        $shop = Shop::factory()->create(['organization_id' => $organization->id]);
        $location = StockLocation::factory()->create([
            'organization_id' => $organization->id,
            'shop_id' => $shop->id,
            'status' => 'active',
        ]);
        $terminal = Terminal::factory()->create(['shop_id' => $shop->id]);
        $session = CashSession::factory()->create([
            'organization_id' => $organization->id,
            'shop_id' => $shop->id,
            'terminal_id' => $terminal->id,
            'opened_by' => $owner->id,
            'status' => 'open',
        ]);

        $product = $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson('/api/v1/caisse/products', [
                'shop_id' => $shop->id,
                'name' => 'Produit reporting',
                'sku' => 'REPORT-001',
                'barcode' => '800000000001',
                'sale_price' => 2000,
            ])
            ->assertCreated()
            ->json('product');

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson('/api/v1/caisse/stock/adjustments', [
                'stock_location_id' => $location->id,
                'product_id' => $product['id'],
                'quantity' => 2,
            ])
            ->assertCreated();

        $sale = $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson('/api/v1/caisse/sales', [
                'shop_id' => $shop->id,
                'terminal_id' => $terminal->id,
                'cash_session_id' => $session->id,
                'local_uuid' => (string) Str::uuid(),
                'receipt_number' => 'FLOW-' . fake()->unique()->numerify('#####'),
                'currency' => 'XOF',
                'lines' => [[
                    'product_id' => $product['id'],
                    'quantity' => 1,
                    'unit_price' => 2000,
                ]],
            ])
            ->assertCreated()
            ->json('sale');

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson("/api/v1/caisse/sales/{$sale['id']}/payments/cash", [
                'amount' => 2000,
            ])
            ->assertCreated();

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson("/api/v1/caisse/sales/{$sale['id']}/finalize")
            ->assertOk();

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->getJson('/api/v1/caisse/reports/overview')
            ->assertOk()
            ->assertJsonPath('sales.count', 1)
            ->assertJsonPath('sales.gross_amount', 2000)
            ->assertJsonPath('payments.cash.amount', 2000);

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson("/api/v1/caisse/sales/{$sale['id']}/cancel")
            ->assertOk();

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->getJson('/api/v1/caisse/reports/overview')
            ->assertOk()
            ->assertJsonPath('sales.count', 0)
            ->assertJsonPath('sales.gross_amount', 0)
            ->assertJsonPath('sales.paid_amount', 0)
            ->assertJsonPath('sales.cancelled_count', 1)
            ->assertJsonMissingPath('payments.cash');
    }
}
