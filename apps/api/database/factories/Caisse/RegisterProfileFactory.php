<?php

namespace Database\Factories\Caisse;

use App\Models\Caisse\RegisterProfile;
use App\Models\Caisse\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

class RegisterProfileFactory extends Factory
{
    protected $model = RegisterProfile::class;

    public function definition(): array
    {
        return [
            'shop_id' => Shop::factory(),
            'name' => 'Profil ' . fake()->unique()->numberBetween(1, 9999),
            'default_customer_id' => null,
            'settings' => [],
            'status' => 'active',
        ];
    }
}
