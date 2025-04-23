<?php

namespace Database\Factories;

use App\Enums\VehicleTypeEnum;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserProfileFactory extends Factory
{
    protected $model = UserProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'address' => $this->faker->address(),
            'vehicle_type' => $this->faker->randomElement(VehicleTypeEnum::cases())->value,
            'nin' => $this->faker->numerify('##########'),
            'guarantors_name' => $this->faker->name(),
            'photo' => $this->faker->imageUrl(),
            'barcode' => $this->faker->uuid(),
        ];
    }
} 