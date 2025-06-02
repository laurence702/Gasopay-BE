<?php

namespace App\Http\Requests;

use App\Enums\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;

class SuperAdminUpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only Super Admins are authorized to use this request.
        return $this->user()->role === RoleEnum::SuperAdmin->value;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'fullname' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users')->ignore($this->user)],
            'phone' => ['sometimes', 'string', 'max:20', Rule::unique('users')->ignore($this->user)],
            'role' => ['sometimes', new Enum(RoleEnum::class), Rule::notIn([RoleEnum::SuperAdmin->value])], // Super Admin cannot change another user to Super Admin
            'branch_id' => ['nullable', 'exists:branches,id'],
            // Add other fields a Super Admin might update, e.g., status, verification fields etc.
            // 'status' => ['sometimes', new Enum(UserStatusEnum::class)],
            // 'verification_status' => ['sometimes', new Enum(ProfileVerificationStatusEnum::class)],
            // 'verified_by' => ['nullable', 'string', 'max:255'],
        ];

        // Add password validation only if password is provided
        if ($this->filled('password')) {
            $rules['password'] = ['required', Password::defaults(), 'confirmed'];
            $rules['password_confirmation'] = ['required', 'same:password'];
        }

        return $rules;
    }
}
