<?php

namespace App\Http\Requests;

use App\Enums\RoleEnum;
use App\Enums\PaymentTypeEnum;
use App\Enums\ProductTypeEnum;
use Illuminate\Validation\Rule;
use App\Enums\PaymentMethodEnum;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && $user->role === RoleEnum::Admin;
    }

    public function rules(): array
    {
        return [
            'product' => ['required', Rule::enum(ProductTypeEnum::class)],
            'payment_type' => ['required', Rule::enum(PaymentTypeEnum::class)],
            'payment_method' => ['required', Rule::enum(PaymentMethodEnum::class)],
            'amount_due' => 'required|numeric|min:10000',
            'amount_paid' => 'required|numeric|min:10000',
            'payer_id' => 'required|string|exists:users,id',
        ];
    }
} 