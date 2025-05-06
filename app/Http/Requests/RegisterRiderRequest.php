<?php

namespace App\Http\Requests;

use App\Enums\RoleEnum;
use App\Enums\VehicleTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRiderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fullname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['required', 'string', 'max:20', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'address' => ['required', 'string'],
            'vehicle_type' => ['required', Rule::in(VehicleTypeEnum::cases())],
            'nin' => ['required', 'string'],
            'guarantors_name' => ['required', 'string'],
            'guarantors_address' => ['required', 'string'],
            'guarantors_phone' => ['required', 'string'],
            'profilePicUrl' => ['required', 'string'],
        ];
    }
} 