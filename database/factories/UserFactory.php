<?php

namespace Database\Factories;

use App\Enums\RoleEnum;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'fullname' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'role' => RoleEnum::Regular->value,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'branch_id' => Branch::factory()->create()->id, // Default to a branch for all roles except superadmin
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => RoleEnum::SuperAdmin->value,
            'branch_id' => null,
        ]);
    }

    /**
     * Set the user's role to admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => RoleEnum::Admin->value,
            'branch_id' => Branch::factory()->create()->id,
        ]);
    }

    /**
     * Set the user's role to rider.
     */
    public function rider(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => RoleEnum::Rider->value,
            'branch_id' => Branch::factory()->create()->id, // Rider must have a branch
        ]);
    }
}
