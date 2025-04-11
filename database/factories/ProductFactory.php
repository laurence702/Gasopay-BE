<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'unit' => $this->faker->randomElement(['kg', 'liter', 'piece']),
            'price' => $this->faker->randomFloat(2, 10, 1000),
        ];
    }
} 