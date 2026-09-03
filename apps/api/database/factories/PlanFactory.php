<?php

namespace Database\Factories;

use App\Models\Entitlement;
use App\Models\Module;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'price_monthly' => 5000,
            'price_yearly' => 50000,
            'currency' => 'XOF',
            'features' => ['pos.sell'],
            'max_users' => 5,
            'max_products' => 1000,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function withCaisseEntitlement(): static
    {
        return $this->afterCreating(function (Plan $plan): void {
            $module = Module::firstOrCreate(
                ['slug' => 'caisse'],
                [
                    'name' => 'Caisse',
                    'description' => 'Gestion des caisses, ventes et paiements.',
                    'is_active' => true,
                    'sort_order' => 10,
                ]
            );

            $entitlement = Entitlement::firstOrCreate(
                ['slug' => 'pos.sell'],
                [
                    'name' => 'Effectuer une vente',
                    'description' => null,
                    'is_active' => true,
                    'sort_order' => 0,
                ]
            );

            $module->entitlements()->syncWithoutDetaching([$entitlement->id]);
            $plan->modules()->syncWithoutDetaching([$module->id]);
        });
    }
}
