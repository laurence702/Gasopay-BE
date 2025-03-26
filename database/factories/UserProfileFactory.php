<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\VehicleType;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserProfileFactory extends Factory
{
    protected $model = UserProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'vehicle_type_id' => VehicleType::factory(),
            'nin' => $this->faker->numerify('##########'),
            'guarantors_name' => $this->faker->name(),
            'photo' => $this->faker->imageUrl(),
            'barcode' => $this->faker->uuid(),
        ];
    }
} 