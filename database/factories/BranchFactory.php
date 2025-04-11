<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\User;
use App\Enums\RoleEnum;
use Illuminate\Database\Eloquent\Factories\Factory;

class BranchFactory extends Factory
{
    protected $model = Branch::class;

    public function definition(): array
    {
        $name = $this->faker->company();
        $phone = $this->faker->phoneNumber();

        // Create a branch admin user
        $admin = User::factory()->create([
            'role' => RoleEnum::Admin,
            'fullname' => $name . ' Admin',
            'email' => strtolower(str_replace(' ', '.', $name)) . '.admin@example.com',
            'phone' => $phone,
        ]);

        return [
            'name' => $name,
            'location' => $this->faker->address(),
            'branch_phone' => $phone,
            'branch_admin' => $admin->id,
        ];
    }
} 