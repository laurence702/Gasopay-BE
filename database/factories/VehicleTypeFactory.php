<?php

namespace Database\Factories;

use App\Models\VehicleType;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleTypeFactory extends Factory
{
    protected $model = VehicleType::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->randomElement(['Car', 'Truck', 'Van', 'Motorcycle', 'Bicycle', 'Scooter', 'Bus', 'Train']),
        ];
    }
} 