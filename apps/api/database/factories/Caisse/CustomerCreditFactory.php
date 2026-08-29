<?php

namespace Database\Factories\Caisse;

use App\Models\Caisse\Customer;
use App\Models\Caisse\CustomerCredit;
use App\Models\Caisse\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerCreditFactory extends Factory
{
    protected $model = CustomerCredit::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'sale_id' => Sale::factory(),
            'amount' => fake()->numberBetween(1000, 100000),
            'paid_amount' => 0,
            'due_amount' => fake()->numberBetween(1000, 100000),
            'status' => 'open',
            'due_at' => now()->addMonth(),
        ];
    }
}
