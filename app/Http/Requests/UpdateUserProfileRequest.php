<?php

namespace App\Http\Requests;

use App\Enums\VehicleTypeEnum;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
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
            'phone' => ['sometimes', 'string', 'max:20'],
            'address' => ['sometimes', 'string', 'max:255'],
            'vehicle_type' => ['sometimes', new Enum(VehicleTypeEnum::class)],
            'nin' => ['sometimes', 'string', 'max:20'],
            'guarantors_name' => ['sometimes', 'string', 'max:255'],
            'guarantors_address' => ['sometimes', 'string', 'max:255'],
            'guarantors_phone' => ['sometimes', 'string', 'max:20'],
            'photo' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
