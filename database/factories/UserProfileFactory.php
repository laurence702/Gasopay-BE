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
            'profile_pic_url' => $this->faker->imageUrl(),
            'guarantors_address' => $this->faker->address(),
            'guarantors_phone' => $this->faker->phoneNumber(),
        ];
    }
} 