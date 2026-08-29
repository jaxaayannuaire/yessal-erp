<?php

namespace Database\Factories\Caisse;

use App\Models\Caisse\Category;
use App\Models\Caisse\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'shop_id' => Shop::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'status' => 'active',
        ];
    }
}
