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

    // Add Nigerian names and places (with emphasis on South-South)
    protected $nigerianNames = [
        'Ejiro Ovie', 'Osas Igbinosa', 'Timi Ebi', 'Blessing Ehizogie', 'Chinedu Obus',
        'Aghogho Ufuoma', 'Onome Pere', 'Doubra Kuro', 'Tariq Bello', 'Godwin Eguavoen',
        'Kessiana Akpojotor', 'Amen Imuetiyan', 'Ese Uruemu', 'Boma George', 'Tamuno Iyalla',
        'Ufuoma Precious', 'Oghenekaro Ochuko', 'Eghosa Amen', 'Idowu Funke', 'Adekunle Taiwo'
    ];

    protected $nigerianAddresses = [
        '12 Sapele Road, Benin City, Edo State',
        '5 Effurun-Sapele Road, Warri, Delta State',
        '8 Azikoro Road, Yenagoa, Bayelsa State',
        '3 Airport Road, Effurun, Delta State',
        '10 Ugbowo Road, Benin City, Edo State',
        '7 Isaac Boro Expressway, Yenagoa, Bayelsa State',
        '2 Nnebisi Road, Asaba, Delta State',
        '15 Ekenwan Road, Benin City, Edo State',
        '9 PTI Road, Effurun, Delta State',
        '4 Melford Okilo Road, Yenagoa, Bayelsa State'
    ];

    protected $nigerianPhonePrefixes = [
        '070', '080', '081', '090', '091'
    ];

    public function run(): void
    {
        //Create branches first
        $branches = Branch::factory()->count(5)->create();

        // Create super admin with a unique phone number
        $superAdminPhone = $this->generateUniquePhone();
        User::factory()->create([
            'fullname' => $this->getRandomName(),
            'email' => 'admin@example.com',
            'phone' => $superAdminPhone,
            'password' => Hash::make('password'),
            'role' => RoleEnum::SuperAdmin,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create branch admins
        $branches->each(function ($branch) {
            $adminPhone = $this->generateUniquePhone();
            User::factory()->create([
                'fullname' => $this->getRandomName(),
                'email' => strtolower(str_replace(' ', '', $branch->name)) . '@example.com',
                'phone' => $adminPhone,
                'password' => Hash::make('password'),
                'role' => RoleEnum::Admin,
                'branch_id' => $branch->id,
                'verification_status' => ProfileVerificationStatusEnum::VERIFIED,
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
                'fullname' => $this->getRandomName(),
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
                "guarantors_name" => $this->getRandomName(),
                "guarantors_phone" => $this->generateUniquePhone(),
                "guarantors_address" => $this->getRandomAddress(),
                "address" => $this->getRandomAddress(),
                "profile_pic_url" => '/path/to/image'. $i,
            ]);
        }
       }

    private function getRandomName(): string
    {
        return Arr::random($this->nigerianNames);
    }

    private function getRandomAddress(): string
    {
        return Arr::random($this->nigerianAddresses);
    }

    private function generateUniquePhone(): string
    {
        do {
            // Generate a Nigerian-style phone number
            $prefix = Arr::random($this->nigerianPhonePrefixes);
            $suffix = str_pad(random_int(0, 9999999), 7, '0', STR_PAD_LEFT);
            $phone = $prefix . $suffix;
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