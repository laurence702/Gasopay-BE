<?php

namespace App\Http\Requests;

use App\Enums\VehicleTypeEnum;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserProfileRequest extends FormRequest
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
            'user_id' => ['required', 'exists:users,id'],
            'phone' => ['required', 'string', 'max:20'],
            'address' => ['required', 'string', 'max:255'],
            'vehicle_type' => ['sometimes', new Enum(VehicleTypeEnum::class)],
            'nin' => ['nullable', 'string', 'max:20', 'unique:user_profiles,nin'],
            'guarantors_name' => ['required', 'string', 'max:255'],
            'guarantors_address' => ['required', 'string', 'max:255'],
            'guarantors_phone' => ['required', 'string', 'max:20'],
            'profilePicUrl' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
