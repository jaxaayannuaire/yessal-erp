<?php

namespace Database\Factories\Caisse;

use App\Models\Caisse\Sale;
use App\Models\Caisse\SalePayment;
use Illuminate\Database\Eloquent\Factories\Factory;

class SalePaymentFactory extends Factory
{
    protected $model = SalePayment::class;

    public function definition(): array
    {
        return [
            'sale_id' => Sale::factory(),
            'payment_method' => 'cash',
            'provider' => null,
            'amount' => fake()->numberBetween(500, 50000),
            'change_amount' => 0,
            'status' => 'confirmed',
            'external_reference' => null,
            'declared_at' => now(),
            'confirmed_at' => now(),
        ];
    }
}
