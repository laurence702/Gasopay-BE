<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\User;
use App\Enums\RoleEnum;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BranchFactory extends Factory
{
    protected $model = Branch::class;

    public function definition(): array
    {
        $name = $this->faker->company();
        
        $adminPhone = $this->faker->unique()->numerify('212######');

        $branchPhone = $this->faker->unique()->numerify('333########');

        $admin = User::factory()->create([
            'role' => RoleEnum::Admin,
            'fullname' => $name . ' Admin',
            'email' => strtolower(str_replace(' ', '.', $name)) . '.admin@example.com',
            'phone' => $adminPhone,
        ]);

        return [
            'name' => $name,
            'location' => $this->faker->address(),
            'branch_phone' => $branchPhone,
            'branch_admin' => $admin->id,
        ];
    }
} 