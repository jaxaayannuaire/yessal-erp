<?php

namespace Database\Factories\Caisse;

use App\Models\Caisse\CashMovement;
use App\Models\Caisse\CashSession;
use Illuminate\Database\Eloquent\Factories\Factory;

class CashMovementFactory extends Factory
{
    protected $model = CashMovement::class;

    public function definition(): array
    {
        return [
            'cash_session_id' => CashSession::factory(),
            'type' => 'cash_in',
            'amount' => fake()->numberBetween(100, 50000),
            'reason' => fake()->sentence(4),
            'reference_type' => null,
            'reference_id' => null,
            'created_by' => null,
        ];
    }
}
