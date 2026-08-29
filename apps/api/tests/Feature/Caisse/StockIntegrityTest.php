<?php

namespace Tests\Feature\Caisse;

use App\Models\Caisse\Product;
use App\Models\Caisse\ProductVariant;
use App\Models\Caisse\Shop;
use App\Models\Caisse\StockLevel;
use App\Models\Caisse\StockLocation;
use App\Models\Organization;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_level_accepts_product_without_variant(): void
    {
        $organization = Organization::factory()->create();
        $shop = Shop::factory()->create([
            'organization_id' => $organization->id,
        ]);
        $location = StockLocation::factory()->create([
            'shop_id' => $shop->id,
        ]);
        $product = Product::factory()->create([
            'shop_id' => $shop->id,
        ]);

        $level = StockLevel::create([
            'stock_location_id' => $location->id,
            'product_id' => $product->id,
            'product_variant_id' => null,
            'quantity' => 10,
            'reserved_quantity' => 0,
        ]);

        $this->assertDatabaseHas('stock_levels', [
            'id' => $level->id,
            'product_id' => $product->id,
            'product_variant_id' => null,
        ]);
    }

    public function test_stock_level_accepts_variant_without_product(): void
    {
        $organization = Organization::factory()->create();
        $shop = Shop::factory()->create([
            'organization_id' => $organization->id,
        ]);
        $location = StockLocation::factory()->create([
            'shop_id' => $shop->id,
        ]);
        $variant = ProductVariant::factory()->create();

        $level = StockLevel::create([
            'stock_location_id' => $location->id,
            'product_id' => null,
            'product_variant_id' => $variant->id,
            'quantity' => 5,
            'reserved_quantity' => 0,
        ]);

        $this->assertDatabaseHas('stock_levels', [
            'id' => $level->id,
            'product_id' => null,
            'product_variant_id' => $variant->id,
        ]);
    }

    public function test_stock_level_rejects_product_and_variant_together(): void
    {
        $organization = Organization::factory()->create();
        $shop = Shop::factory()->create([
            'organization_id' => $organization->id,
        ]);
        $location = StockLocation::factory()->create([
            'shop_id' => $shop->id,
        ]);
        $product = Product::factory()->create([
            'shop_id' => $shop->id,
        ]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
        ]);

        $this->expectException(QueryException::class);

        StockLevel::create([
            'stock_location_id' => $location->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 10,
            'reserved_quantity' => 0,
        ]);
    }

    public function test_stock_level_rejects_without_product_or_variant(): void
    {
        $organization = Organization::factory()->create();
        $shop = Shop::factory()->create([
            'organization_id' => $organization->id,
        ]);
        $location = StockLocation::factory()->create([
            'shop_id' => $shop->id,
        ]);

        $this->expectException(QueryException::class);

        StockLevel::create([
            'stock_location_id' => $location->id,
            'product_id' => null,
            'product_variant_id' => null,
            'quantity' => 10,
            'reserved_quantity' => 0,
        ]);
    }
}