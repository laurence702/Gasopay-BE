<?php

namespace App\Http\Requests;

use App\Enums\RoleEnum;
use App\Enums\VehicleTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // User fields
            'fullname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['required', 'string', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in(RoleEnum::cases())],
            'branch_id' => 'nullable|exists:branches,id',
            
            // User Profile fields (only required for regular users and riders)
            'address' => ['required_if:role,' . RoleEnum::Rider->value, 'string'],
            'vehicle_type' => ['required_if:role,' . RoleEnum::Rider->value, Rule::in(VehicleTypeEnum::cases())],
            'nin' => ['required_if:role,' . RoleEnum::Rider->value, 'string'],
            'guarantors_name' => ['required_if:role,' . RoleEnum::Rider->value, 'string'],
            'photo' => 'nullable|image|max:2048',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if (!$this->has('role')) {
            $this->merge([
                'role' => RoleEnum::Regular->value
            ]);
        }
    }
}
