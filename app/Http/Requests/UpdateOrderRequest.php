<?php

namespace App\Http\Requests;

use App\Enums\RoleEnum;
use App\Enums\OrderStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && $user->role === RoleEnum::SuperAdmin;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'sometimes|exists:products,id',
            'payer_id' => 'sometimes|exists:users,id',
            'branch_id' => 'sometimes|exists:branches,id',
            'quantity' => 'sometimes|integer|min:1',
            'status' => ['sometimes', new Enum(OrderStatusEnum::class)],
        ];
    }
} 