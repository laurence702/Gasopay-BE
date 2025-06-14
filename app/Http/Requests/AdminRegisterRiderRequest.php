<?php

namespace App\Http\Requests;

use App\Enums\RoleEnum;
use App\Enums\VehicleTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AdminRegisterRiderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only Admins and Super Admins are authorized to register riders.
        $user = $this->user();
        return $user && ($user->role === RoleEnum::Admin || $user->role === RoleEnum::SuperAdmin);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'fullname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['required', 'string', 'max:255', 'unique:users'],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            'branch_id' => ['nullable', 'exists:branches,id'], // Admin can specify, or it can be derived
            'verify_now' => ['sometimes', 'boolean'], // Optional: Admin can choose to immediately verify

            // User Profile fields (required for riders)
            'address' => ['required', 'string'],
            'vehicle_type' => ['required', Rule::in(VehicleTypeEnum::cases())],
            'nin' => ['required', 'string'],
            'guarantors_name' => ['required', 'string'],
            'guarantors_address' => ['required', 'string'],
            'guarantors_phone' => ['required', 'string'],
            'profilePicUrl' => ['nullable', 'string'],
            'photo' => 'nullable|image|max:2048',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // If an Admin is creating a rider, default the branch_id to their own branch
        if ($this->user() && $this->user()->role === RoleEnum::Admin && !$this->has('branch_id')) {
            $this->merge([
                'branch_id' => $this->user()->branch_id,
            ]);
        }
    }
} 