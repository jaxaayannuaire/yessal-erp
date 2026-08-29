<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'plan_id' => Plan::factory(),
            'billing_cycle' => 'monthly',
            'price' => 5000,
            'currency' => 'XOF',
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'trial_ends_at' => null,
            'cancelled_at' => null,
            'grace_period_ends_at' => null,
        ];
    }
}