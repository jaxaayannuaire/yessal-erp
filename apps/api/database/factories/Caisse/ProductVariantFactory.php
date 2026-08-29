<?php

namespace Database\Factories\Caisse;

use App\Models\Caisse\Product;
use App\Models\Caisse\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'name' => fake()->words(2, true),
            'sku' => strtoupper(fake()->unique()->bothify('VAR-#####')),
            'barcode' => fake()->unique()->numerify('300000000001'),
            'attributes' => ['option' => fake()->word()],
            'purchase_price' => fake()->numberBetween(100, 5000),
            'sale_price' => fake()->numberBetween(500, 15000),            
        ];
    }
}
