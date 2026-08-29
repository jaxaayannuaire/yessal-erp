<?php

namespace Database\Factories\Caisse;

use App\Models\Caisse\Shop;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShopFactory extends Factory
{
    protected $model = Shop::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::query()->value('id') ?? 1,
            'name' => fake()->company(),
            'code' => strtoupper(fake()->unique()->bothify('SHOP-###')),
            'address' => fake()->address(),
            'phone' => fake()->phoneNumber(),
            'status' => 'active',
        ];
    }
}
