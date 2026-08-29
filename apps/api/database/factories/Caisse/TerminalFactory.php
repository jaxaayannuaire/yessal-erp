<?php

namespace Database\Factories\Caisse;

use App\Models\Caisse\Shop;
use App\Models\Caisse\Terminal;
use Illuminate\Database\Eloquent\Factories\Factory;

class TerminalFactory extends Factory
{
    protected $model = Terminal::class;

    public function definition(): array
    {
        return [
            'shop_id' => Shop::factory(),
            'register_profile_id' => null,
            'name' => 'Caisse ' . fake()->numberBetween(1, 99),
            'code' => strtoupper(fake()->unique()->bothify('POS-###')),
            'status' => 'active',
        ];
    }
}
