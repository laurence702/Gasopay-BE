<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserProfile;
use App\Enums\RoleEnum;
use App\Enums\ProfileVerificationStatusEnum;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class RiderRegistrationService
{
    /**
     * Create a new rider user and their profile.
     *
     * @param array $data The rider data, including user and user profile details.
     * @param bool $verifiedImmediately Whether the rider should be immediately marked as verified.
     * @return User
     * @throws \Throwable
     */
    public function createRider(array $data, bool $verifiedImmediately = false): User
    {
        return DB::transaction(function () use ($data, $verifiedImmediately) {
            $user = User::create([
                'fullname' => $data['fullname'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => Hash::make($data['password']),
                'role' => RoleEnum::Rider,
                'branch_id' => $data['branch_id'] ?? null,
                'verification_status' => $verifiedImmediately ? ProfileVerificationStatusEnum::VERIFIED : ProfileVerificationStatusEnum::PENDING,
                'email_verified_at' => $verifiedImmediately ? now() : null,
            ]);

            // Create user profile for the rider
            $user->userProfile()->create([
                'address' => $data['address'] ?? null,
                'vehicle_type' => $data['vehicle_type'] ?? null,
                'nin' => $data['nin'] ?? null,
                'guarantors_name' => $data['guarantors_name'] ?? null,
                'guarantors_address' => $data['guarantors_address'] ?? null,
                'guarantors_phone' => $data['guarantors_phone'] ?? null,
                'profile_pic_url' => $data['profilePicUrl'] ?? null,
            ]);

            return $user;
        });
    }
} 