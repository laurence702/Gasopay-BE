<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\User;
use App\Models\Branch;
use App\Models\UserProfile;
use App\Enums\ProfileVerificationStatusEnum;
use App\Enums\VehicleTypeEnum;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Arr;

class DatabaseSeeder extends Seeder
{
    protected $usedPhones = [];

    public function run(): void
    {
       // Create super admin with a unique phone number
        $superAdminPhone = $this->generateUniquePhone();
        User::factory()->create([
            'fullname' => 'Super Admin',
            'email' => 'test@example.com',
            'phone' => $superAdminPhone,
            'password' => Hash::make('password'),
            'role' => RoleEnum::SuperAdmin,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

       // Create branches and their admins
        $branches = Branch::factory()->count(5)->create()->each(function ($branch) {
            $adminPhone = $this->generateUniquePhone();
            User::factory()->create([
                'fullname' => $branch->name . ' Admin',
                'email' => strtolower(str_replace(' ', '', $branch->name)) . '@example.com',
                'phone' => $adminPhone,
                'password' => Hash::make('password'),
                'role' => RoleEnum::Admin,
                'branch_id' => $branch->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $verificationStatuses = [
            ProfileVerificationStatusEnum::PENDING,
            ProfileVerificationStatusEnum::VERIFIED,
            ProfileVerificationStatusEnum::REJECTED,
        ];
        $vehicleTypes = [
            VehicleTypeEnum::Car,
            VehicleTypeEnum::Keke,
        ];

        // Create riders
        for ($i = 1; $i <= 5; $i++) {
            $isBanned = $i <= 2;
            $verificationStatus = Arr::random($verificationStatuses);
            $riderPhone = $this->generateUniquePhone();

            $rider = User::factory()->create([
                'fullname' => 'Rider User ' . $i,
                'email' => 'rider' . $i . '@example.com',
                'phone' => $riderPhone,
                'password' => Hash::make('password'),
                'role' => RoleEnum::Rider,
                'branch_id' => $branches->random()->id,
                'verification_status' => $verificationStatus,
                'banned_at' => $isBanned ? now() : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            UserProfile::factory()->create([
                'user_id' => $rider->id,
                'vehicle_type' => Arr::random($vehicleTypes),
                "guarantors_name" => 'guarantor'. $i,
                "guarantors_phone" => $this->generateUniquePhone(), // Generate unique phone for guarantor
                "guarantors_address" => 'guarantor address'. $i,
                "address" => 'address'. $i,
                "profile_pic_url" => '/path/to/image'. $i,
            ]);
        }
    }

    private function generateUniquePhone(): string
    {
        do {
            $phone = '555' . random_int(1000000, 9999999);
        } while ($this->phoneExists($phone));

        $this->usedPhones[] = $phone;
        return $phone;
    }

    private function phoneExists(string $phone): bool
    {
        $existsInDB = User::where('phone', $phone)->exists();
        $existsInLocal = in_array($phone, $this->usedPhones);
        return $existsInDB || $existsInLocal;
    }
}