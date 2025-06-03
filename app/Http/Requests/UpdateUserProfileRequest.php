<?php

namespace App\Http\Requests;

use App\Enums\VehicleTypeEnum;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Ensure the authenticated user is updating their own profile or is an admin
        $userProfile = $this->route('userProfile');
        $user = $this->user();

        if (!$userProfile || !$user) {
            return false;
        }

        // Allow if user is updating their own profile
        if ($userProfile->user_id === $user->id) {
            return true;
        }

        // Allow if user is an admin
        return $user->role === \App\Enums\RoleEnum::Admin->value || 
               $user->role === \App\Enums\RoleEnum::SuperAdmin->value;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // We need the userProfile ID for the unique rule ignore case on NIN
        $userProfileId = $this->route('userProfile')->id ?? null;

        return [
            'phone' => ['sometimes', 'string', 'max:20'], // Consider unique rule if phone is unique on profile
            'address' => ['sometimes', 'string', 'max:255'],
            'vehicle_type' => ['sometimes', new Enum(VehicleTypeEnum::class)],
            'nin' => ['sometimes', 'string', 'max:20', Rule::unique('user_profiles')->ignore($userProfileId)],
            'guarantors_name' => ['sometimes', 'string', 'max:255'],
            'guarantors_address' => ['sometimes', 'string', 'max:255'],
            'guarantors_phone' => ['sometimes', 'string', 'max:20'],
            'photo' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
