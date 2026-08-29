<?php

namespace Database\Factories\Caisse;

use App\Models\Caisse\Shop;
use App\Models\Caisse\StockLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockLocationFactory extends Factory
{
    protected $model = StockLocation::class;

    public function definition(): array
    {
        return [
            'shop_id' => Shop::factory(),
            'name' => 'Stock ' . fake()->unique()->numberBetween(1, 9999),
            'type' => 'store',
            'status' => 'active',
        ];
    }
}
