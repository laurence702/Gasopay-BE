<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // create super-admin user
        User::factory()->create([
            'fullname' => 'Test User',
            'email' => 'foo@bar.com',
            'password' => Hash::make('password'),
            'role' => RoleEnum::SuperAdmin,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
