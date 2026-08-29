<?php

namespace Database\Factories\Caisse;

use App\Models\Caisse\Product;
use App\Models\Caisse\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'shop_id' => Shop::factory(),
            'category_id' => null,
            'name' => fake()->words(3, true),
            'sku' => strtoupper(fake()->unique()->bothify('SKU-#####')),
            'barcode' => fake()->unique()->numerify('###########'),
            'unit' => 'unit',
            'purchase_price' => fake()->numberBetween(100, 10000),
            'sale_price' => fake()->numberBetween(500, 25000),
            'tax_rate' => 0,
            'status' => 'active',
        ];
    }
}
