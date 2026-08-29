<?php

namespace Database\Factories\Caisse;

use App\Models\Caisse\CashSession;
use App\Models\Caisse\Shop;
use App\Models\Caisse\Terminal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CashSessionFactory extends Factory
{
    protected $model = CashSession::class;

    public function definition(): array
    {
        $terminal = Terminal::factory()->create();
        $shop = Shop::find($terminal->shop_id);

        return [
            'organization_id' => $shop->organization_id,
            'shop_id' => $shop->id,
            'terminal_id' => $terminal->id,
            'device_id' => null,
            'opened_by' => User::factory(),
            'closed_by' => null,
            'opening_amount' => fake()->numberBetween(0, 100000),
            'expected_amount' => null,
            'counted_amount' => null,
            'variance_amount' => null,
            'variance_reason' => null,
            'status' => 'open',
            'opened_at' => now(),
            'closed_at' => null,
        ];
    }
}
