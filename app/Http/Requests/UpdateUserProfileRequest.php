<?php

namespace App\Http\Requests;

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
            'vehicle_type_id' => ['nullable', 'exists:vehicle_types,id'],
            'nin' => ['sometimes', 'string', 'max:20'],
            'guarantors_name' => ['sometimes', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'barcode' => ['nullable', 'string', 'max:255'],
        ];
    }
}
