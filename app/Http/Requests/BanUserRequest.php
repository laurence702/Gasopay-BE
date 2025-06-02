<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\User;

class BanUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is handled by the UserPolicy, which this Form Request will automatically use.
        $user = $this->route('user');
        if ($user && $user->banned_at) {
            return false;
        }
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
            'ban_reason' => ['required', 'string', 'max:255'],
        ];
    }
}