<?php

namespace App\Http\Requests;

use App\Enums\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && $user->role === RoleEnum::SuperAdmin;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:products,id',
            'payer_id' => 'required|exists:users,id',
            'branch_id' => 'required|exists:branches,id',
            'quantity' => 'required|integer|min:1',
        ];
    }
} 