<?php

namespace Tests\Feature\Caisse;

use App\Models\Caisse\Product;
use App\Models\Caisse\Shop;
use App\Models\Caisse\StockLevel;
use App\Models\Caisse\StockLocation;
use App\Models\Caisse\Terminal;
use App\Models\Organization;
use Database\Seeders\CaisseDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaisseDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_creates_tenant_scoped_stock_and_is_idempotent(): void
    {
        $this->seed(CaisseDemoSeeder::class);
        $this->seed(CaisseDemoSeeder::class);

        $organization = Organization::query()->where('name', 'Jaxaay Group')->firstOrFail();
        $shop = Shop::query()
            ->where('organization_id', $organization->id)
            ->where('code', 'SHOP-001')
            ->firstOrFail();
        $terminal = Terminal::query()
            ->where('shop_id', $shop->id)
            ->where('code', 'POS-001')
            ->firstOrFail();
        $product = Product::query()
            ->where('shop_id', $shop->id)
            ->where('sku', 'DEMO-001')
            ->firstOrFail();
        $location = StockLocation::query()
            ->where('organization_id', $organization->id)
            ->where('shop_id', $shop->id)
            ->where('name', 'Stock principal')
            ->firstOrFail();
        $stockLevel = StockLevel::query()
            ->where('stock_location_id', $location->id)
            ->where('product_id', $product->id)
            ->firstOrFail();

        $this->assertSame('Jaxaay Group', $organization->name);
        $this->assertSame($organization->id, $location->organization_id);
        $this->assertSame($shop->id, $location->shop_id);
        $this->assertSame('store', $location->type);
        $this->assertSame('active', $location->status);
        $this->assertSame(100.0, (float) $stockLevel->quantity);
        $this->assertNotNull($terminal);

        $this->assertSame(1, Organization::query()->where('name', 'Jaxaay Group')->count());
        $this->assertSame(1, Shop::query()->where('organization_id', $organization->id)->where('code', 'SHOP-001')->count());
        $this->assertSame(1, Terminal::query()->where('shop_id', $shop->id)->where('code', 'POS-001')->count());
        $this->assertSame(1, Product::query()->where('shop_id', $shop->id)->where('sku', 'DEMO-001')->count());
        $this->assertSame(1, StockLocation::query()->where('organization_id', $organization->id)->where('shop_id', $shop->id)->where('name', 'Stock principal')->count());
        $this->assertSame(1, StockLevel::query()->where('stock_location_id', $location->id)->where('product_id', $product->id)->count());
    }
}
