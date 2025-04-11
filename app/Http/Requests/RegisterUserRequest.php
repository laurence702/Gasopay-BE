<?php

namespace App\Http\Requests;

use App\Enums\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

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
            'fullname' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'required|string|max:20',
            'role' => ['required', 'string', new Enum(RoleEnum::class)],
            'branch_id' => 'nullable|exists:branches,id',
            
            // User Profile fields (only required for regular users and riders)
            'address' => 'required_if:role,regular,rider|string|max:255',
            'vehicle_type_id' => 'required_if:role,rider|exists:vehicle_types,id',
            'nin' => 'required_if:role,regular,rider|string|max:20',
            'guarantors_name' => 'required_if:role,regular,rider|string|max:255',
            'photo' => 'nullable|image|max:2048',
            'barcode' => 'nullable|string|max:255',
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
