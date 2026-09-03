<?php

namespace Tests\Feature\Caisse;

use App\Models\Caisse\CashSession;
use App\Models\Caisse\Product;
use App\Models\Caisse\ProductVariant;
use App\Models\Caisse\Sale;
use App\Models\Caisse\SaleLine;
use App\Models\Caisse\Shop;
use App\Models\Caisse\StockLevel;
use App\Models\Caisse\StockLocation;
use App\Models\Caisse\Terminal;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SaleStockFinalizationTest extends TestCase
{
    use RefreshDatabase;

    private function context(): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->users()->attach($user->id, ['role' => 'owner']);

        $plan = Plan::factory()->withCaisseEntitlement()->create();

        Subscription::factory()->create([
            'organization_id' => $organization->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addMonth(),
        ]);

        return [$user, $organization];
    }

    private function headers(Organization $organization): array
    {
        return ['X-Organization-Id' => (string) $organization->id];
    }

    private function saleContext(Organization $organization, User $user): array
    {
        $shop = Shop::factory()->create(['organization_id' => $organization->id]);
        $terminal = Terminal::factory()->create(['shop_id' => $shop->id]);
        $session = CashSession::factory()->create([
            'organization_id' => $organization->id,
            'shop_id' => $shop->id,
            'terminal_id' => $terminal->id,
            'opened_by' => $user->id,
            'status' => 'open',
        ]);
        $location = StockLocation::factory()->create([
            'organization_id' => $organization->id,
            'shop_id' => $shop->id,
            'status' => 'active',
        ]);
        $sale = Sale::factory()->create([
            'organization_id' => $organization->id,
            'shop_id' => $shop->id,
            'terminal_id' => $terminal->id,
            'cash_session_id' => $session->id,
            'cashier_user_id' => $user->id,
            'status' => 'paid',
            'subtotal' => 10000,
            'total_amount' => 10000,
            'paid_amount' => 10000,
            'due_amount' => 0,
        ]);

        return [$shop, $terminal, $session, $location, $sale];
    }

    private function productWithStock(
        Shop $shop,
        StockLocation $location,
        float $quantity
    ): Product {
        $product = Product::factory()->create(['shop_id' => $shop->id]);

        StockLevel::create([
            'stock_location_id' => $location->id,
            'product_id' => $product->id,
            'product_variant_id' => null,
            'quantity' => $quantity,
            'reserved_quantity' => 0,
        ]);

        return $product;
    }

    private function line(Sale $sale, Product $product, float $quantity): SaleLine
    {
        return SaleLine::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'product_variant_id' => null,
            'product_name_snapshot' => $product->name,
            'sku_snapshot' => $product->sku,
            'barcode_snapshot' => $product->barcode,
            'quantity' => $quantity,
            'unit_price' => $product->sale_price,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => (int) $product->sale_price * $quantity,
        ]);
    }

    public function test_finalization_decrements_stock_and_creates_a_sale_movement(): void
    {
        [$user, $organization] = $this->context();
        [$shop, , , $location, $sale] = $this->saleContext($organization, $user);
        $product = $this->productWithStock($shop, $location, 10);
        $this->line($sale, $product, 3);

        $this->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson("/api/v1/caisse/sales/{$sale->id}/finalize")
            ->assertOk()
            ->assertJsonPath('sale.status', 'finalized');

        $this->assertDatabaseHas('stock_levels', [
            'stock_location_id' => $location->id,
            'product_id' => $product->id,
            'quantity' => 7,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'organization_id' => $organization->id,
            'stock_location_id' => $location->id,
            'product_id' => $product->id,
            'type' => 'sale_out',
            'quantity' => 3,
            'reference_type' => 'sale',
            'reference_id' => (string) $sale->id,
        ]);
    }

    public function test_finalization_decrements_each_product_line(): void
    {
        [$user, $organization] = $this->context();
        [$shop, , , $location, $sale] = $this->saleContext($organization, $user);
        $firstProduct = $this->productWithStock($shop, $location, 10);
        $secondProduct = $this->productWithStock($shop, $location, 8);
        $this->line($sale, $firstProduct, 2);
        $this->line($sale, $secondProduct, 5);

        $this->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson("/api/v1/caisse/sales/{$sale->id}/finalize")
            ->assertOk();

        $this->assertDatabaseHas('stock_levels', [
            'product_id' => $firstProduct->id,
            'quantity' => 8,
        ]);
        $this->assertDatabaseHas('stock_levels', [
            'product_id' => $secondProduct->id,
            'quantity' => 3,
        ]);
        $this->assertDatabaseCount('stock_movements', 2);
    }

    public function test_insufficient_stock_rolls_back_sale_and_all_prior_line_changes(): void
    {
        [$user, $organization] = $this->context();
        [$shop, , , $location, $sale] = $this->saleContext($organization, $user);
        $availableProduct = $this->productWithStock($shop, $location, 10);
        $insufficientProduct = $this->productWithStock($shop, $location, 1);
        $this->line($sale, $availableProduct, 2);
        $this->line($sale, $insufficientProduct, 2);

        $this->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson("/api/v1/caisse/sales/{$sale->id}/finalize")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sale');

        $this->assertSame('paid', $sale->fresh()->status);
        $this->assertNull($sale->fresh()->finalized_at);
        $this->assertDatabaseHas('stock_levels', [
            'product_id' => $availableProduct->id,
            'quantity' => 10,
        ]);
        $this->assertDatabaseHas('stock_levels', [
            'product_id' => $insufficientProduct->id,
            'quantity' => 1,
        ]);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_second_finalize_does_not_decrement_stock_twice(): void
    {
        [$user, $organization] = $this->context();
        [$shop, , , $location, $sale] = $this->saleContext($organization, $user);
        $product = $this->productWithStock($shop, $location, 4);
        $this->line($sale, $product, 1);

        $this->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson("/api/v1/caisse/sales/{$sale->id}/finalize")
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson("/api/v1/caisse/sales/{$sale->id}/finalize")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sale');

        $this->assertDatabaseHas('stock_levels', [
            'product_id' => $product->id,
            'quantity' => 3,
        ]);
        $this->assertDatabaseCount('stock_movements', 1);
    }

    public function test_variant_stock_is_decremented_without_touching_product_stock(): void
    {
        [$user, $organization] = $this->context();
        [$shop, , , $location, $sale] = $this->saleContext($organization, $user);
        $product = Product::factory()->create(['shop_id' => $shop->id]);
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
        StockLevel::create([
            'stock_location_id' => $location->id,
            'product_id' => null,
            'product_variant_id' => $variant->id,
            'quantity' => 6,
            'reserved_quantity' => 0,
        ]);
        SaleLine::create([
            'sale_id' => $sale->id,
            'product_id' => null,
            'product_variant_id' => $variant->id,
            'product_name_snapshot' => $product->name,
            'sku_snapshot' => $variant->sku,
            'barcode_snapshot' => $variant->barcode,
            'quantity' => 2,
            'unit_price' => $variant->sale_price,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 2 * $variant->sale_price,
        ]);

        $this->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson("/api/v1/caisse/sales/{$sale->id}/finalize")
            ->assertOk();

        $this->assertDatabaseHas('stock_levels', [
            'product_variant_id' => $variant->id,
            'quantity' => 4,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => null,
            'product_variant_id' => $variant->id,
            'type' => 'sale_out',
        ]);
    }

    public function test_sale_cannot_consume_a_product_or_stock_location_from_another_organization(): void
    {
        [$user, $organization] = $this->context();
        [, , , , $sale] = $this->saleContext($organization, $user);
        $otherOrganization = Organization::factory()->create();
        $otherShop = Shop::factory()->create([
            'organization_id' => $otherOrganization->id,
        ]);
        $otherLocation = StockLocation::factory()->create([
            'organization_id' => $otherOrganization->id,
            'shop_id' => $otherShop->id,
        ]);
        $otherProduct = $this->productWithStock($otherShop, $otherLocation, 5);
        $this->line($sale, $otherProduct, 1);

        $this->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson("/api/v1/caisse/sales/{$sale->id}/finalize")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sale');

        $this->assertSame('paid', $sale->fresh()->status);
        $this->assertDatabaseHas('stock_levels', [
            'stock_location_id' => $otherLocation->id,
            'product_id' => $otherProduct->id,
            'quantity' => 5,
        ]);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_product_api_stock_adjustment_payment_and_finalization_work_together(): void
    {
        [$user, $organization] = $this->context();
        [$shop, $terminal, $session, $location] = $this->saleContext($organization, $user);

        $product = $this->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson('/api/v1/caisse/products', [
                'shop_id' => $shop->id,
                'name' => 'Produit finalisable',
                'sku' => 'FINAL-001',
                'barcode' => '89000000001',
                'sale_price' => 2000,
            ])
            ->assertCreated()
            ->json('product');

        $this->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson('/api/v1/caisse/stock/adjustments', [
                'stock_location_id' => $location->id,
                'product_id' => $product['id'],
                'quantity' => 3,
            ])
            ->assertCreated();

        $sale = $this->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson('/api/v1/caisse/sales', [
                'shop_id' => $shop->id,
                'terminal_id' => $terminal->id,
                'cash_session_id' => $session->id,
                'local_uuid' => (string) Str::uuid(),
                'receipt_number' => 'FIN-' . fake()->unique()->numerify('#####'),
                'currency' => 'XOF',
                'lines' => [[
                    'product_id' => $product['id'],
                    'quantity' => 2,
                    'unit_price' => 2000,
                ]],
            ])
            ->assertCreated()
            ->json('sale');

        $this->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson("/api/v1/caisse/sales/{$sale['id']}/payments/cash", [
                'amount' => 4000,
            ])
            ->assertCreated()
            ->assertJsonPath('sale.status', 'paid');

        $this->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson("/api/v1/caisse/sales/{$sale['id']}/finalize")
            ->assertOk()
            ->assertJsonPath('sale.status', 'finalized');

        $this->assertDatabaseHas('stock_levels', [
            'stock_location_id' => $location->id,
            'product_id' => $product['id'],
            'quantity' => 1,
        ]);
    }
}
