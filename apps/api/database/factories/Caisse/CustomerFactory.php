<?php

namespace Database\Factories\Caisse;

use App\Models\Caisse\Customer;
use App\Models\Caisse\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'shop_id' => Shop::factory(),
            'name' => fake()->name(),
            'phone' => fake()->numerify('+22177#######'),
            'email' => fake()->safeEmail(),
            'address' => fake()->address(),
            'credit_enabled' => false,
            'status' => 'active',
        ];
    }
}
