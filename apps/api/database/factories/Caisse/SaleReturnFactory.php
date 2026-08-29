<?php

namespace Database\Factories\Caisse;

use App\Models\Caisse\Sale;
use App\Models\Caisse\SaleReturn;
use Illuminate\Database\Eloquent\Factories\Factory;

class SaleReturnFactory extends Factory
{
    protected $model = SaleReturn::class;

    public function definition(): array
    {
        return [
            'sale_id' => Sale::factory(),
            'amount' => fake()->numberBetween(100, 10000),
            'reason' => fake()->sentence(5),
            'status' => 'completed',
            'created_by' => null,
        ];
    }
}
