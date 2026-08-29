<?php

namespace Database\Factories\Caisse;

use App\Models\Caisse\Product;
use App\Models\Caisse\StockLevel;
use App\Models\Caisse\StockLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockLevelFactory extends Factory
{
    protected $model = StockLevel::class;

    public function definition(): array
    {
        return [
            'stock_location_id' => StockLocation::factory(),
            'product_id' => Product::factory(),
            'product_variant_id' => null,
            'quantity' => fake()->numberBetween(0, 100),
            'reserved_quantity' => 0,
        ];
    }
}
