<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\User;
use App\Models\Branch;
use App\Models\UserProfile;
use App\Enums\ProfileVerificationStatusEnum;
use App\Enums\VehicleTypeEnum;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Arr; // Import Arr facade

class DatabaseSeeder extends Seeder
{
    protected $usedPhones = [];

    public function run(): void
    {
        User::factory()->create([
            'fullname' => 'Super Admin User',
            'email' => 'superadmin@example.com',
            'phone' => '1110001111',
            'password' => Hash::make('password'),
            'role' => RoleEnum::SuperAdmin,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $branches = Branch::factory()->count(5)->create()->each(function ($branch) {
            User::factory()->create([
                'fullname' => $branch->name . ' Admin',
                'email' => strtolower(str_replace(' ', '', $branch->name)) . '@example.com',
                'phone' => $branch->branch_phone,
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

        for ($i = 1; $i <= 10; $i++) {
            $isBanned = $i <= 2;
            $verificationStatus = Arr::random($verificationStatuses);

            // Generate a unique phone number
            $phone = $this->generateUniquePhone();

            $rider = User::factory()->create([
                'fullname' => 'Rider User ' . $i,
                'email' => 'rider' . $i . '@example.com',
                'phone' => $phone,
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
                'phone' => $phone,
                'vehicle_type' => Arr::random($vehicleTypes),
            ]);
        }
    }

    /**
     * Generate a unique phone number.
     * Assumes phone numbers are 10 digits starting with 555.
     */
    private function generateUniquePhone(): string
    {
        do {
            // Generate 7 random digits after "555"
            $phone = '555' . random_int(1000000, 9999999);
        } while ($this->phoneExists($phone));

        // Store to used phones to avoid local duplicates during seeding
        $this->usedPhones[] = $phone;

        return $phone;
    }

    /**
     * Check if phone already exists in the database or locally in seeder's used pool.
     */
    private function phoneExists(string $phone): bool
    {
        // Check in existing DB records
        $existsInDB = User::where('phone', $phone)->exists();

        // Check in current seeder run array
        $existsInLocal = in_array($phone, $this->usedPhones);

        return $existsInDB || $existsInLocal;
    }
}