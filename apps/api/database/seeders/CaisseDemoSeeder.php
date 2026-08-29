<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use App\Models\Caisse\Category;
use App\Models\Caisse\Customer;
use App\Models\Caisse\Device;
use App\Models\Caisse\Product;
use App\Models\Caisse\Shop;
use App\Models\Caisse\StockLevel;
use App\Models\Caisse\StockLocation;
use App\Models\Caisse\Terminal;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CaisseDemoSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()->firstOrCreate(
            ['id' => 1],
            [
                'name' => 'Jaxaay Group',
                'slug' => 'jaxaay-group',
                'country' => 'SN',
                'currency' => 'XOF',
                'status' => 'active',
            ]
        );

        $user = User::query()->first();

        if (! $user) {
            $this->command?->warn('Aucun User existant : les tests nécessitant un utilisateur devront utiliser UserFactory du Core.');
        }

        $shop = Shop::query()->firstOrCreate(
            ['organization_id' => $organization->id, 'code' => 'SHOP-001'],
            [
                'name' => 'Jaxaay Group — Boutique principale',
                'status' => 'active',
            ]
        );

        $terminal = Terminal::query()->firstOrCreate(
            ['shop_id' => $shop->id, 'code' => 'POS-001'],
            [
                'name' => 'Caisse principale',
                'status' => 'active',
            ]
        );

        Device::query()->firstOrCreate(
            ['organization_id' => $organization->id, 'device_uuid' => '00000000-0000-0000-0000-000000000001'],
            [
                'shop_id' => $shop->id,
                'terminal_id' => $terminal->id,
                'name' => 'Yessal Test Device',
                'platform' => 'android',
                'app_version' => '0.1.0',
                'status' => 'active',
                'paired_at' => now(),
            ]
        );

        $category = Category::query()->firstOrCreate(
            ['shop_id' => $shop->id, 'slug' => 'general'],
            ['name' => 'Général', 'status' => 'active']
        );

        Product::query()->firstOrCreate(
            ['shop_id' => $shop->id, 'sku' => 'DEMO-001'],
            [
                'category_id' => $category->id,
                'name' => 'Produit de démonstration',
                'barcode' => '200000000001',
                'unit' => 'unit',
                'purchase_price' => 500,
                'sale_price' => 1000,
                'tax_rate' => 0,
                'status' => 'active',
            ]
        );

        Customer::query()->firstOrCreate(
            ['shop_id' => $shop->id, 'phone' => '+221770000001'],
            [
                'name' => 'Client comptant',
                'email' => null,
                'address' => null,
                'credit_enabled' => false,
                'status' => 'active',
            ]
        );

        $location = StockLocation::query()->firstOrCreate(
            ['shop_id' => $shop->id, 'name' => 'Stock principal'],
            ['type' => 'store', 'status' => 'active']
        );

        $product = Product::query()->where('shop_id', $shop->id)->where('sku', 'DEMO-001')->first();

        if ($product) {
            StockLevel::query()->firstOrCreate(
                ['stock_location_id' => $location->id, 'product_id' => $product->id],
                ['quantity' => 100, 'reserved_quantity' => 0]
            );
        }

        $this->command?->info('Yessal Caisse demo seed terminé pour Jaxaay Group.');
    }
}
