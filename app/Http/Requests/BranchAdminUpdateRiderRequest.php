<?php

namespace App\Http\Requests;

use App\Enums\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BranchAdminUpdateRiderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $authenticatedUser = $this->user();
        $targetUser = $this->route('user'); // Assuming the route parameter is {user}

        // Check if target user exists
        if (!$targetUser) {
            return false;
        }

        // Check if the authenticated user is a Branch Admin
        if ($authenticatedUser->role !== RoleEnum::Admin->value) {
            return false;
        }

        // Check if the target user is a Rider
        if ($targetUser->role !== RoleEnum::Rider->value) {
            return false;
        }

        // Check if the target Rider belongs to the Branch Admin's branch
        return $authenticatedUser->branch_id === $targetUser->branch_id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $targetUser = $this->route('user'); // Assuming the route parameter is {user}
        $targetUserId = $targetUser?->id;
        $userProfileId = $targetUser?->userProfile?->id ?? null;

        return [
            'fullname' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:20', Rule::unique('users')->ignore($targetUserId)],
            'address' => ['sometimes', 'string', 'max:255'],
            'nin' => ['sometimes', 'string', 'max:255', Rule::unique('user_profiles')->ignore($userProfileId)], // Assuming NIN is on user_profiles
            'vehicle_type' => ['sometimes', 'string', 'max:255'], // Assuming vehicle_type is on user_profiles
            // Add other fields Branch Admin can update for a Rider
        ];
    }
}
