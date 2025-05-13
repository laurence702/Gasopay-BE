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
            'product' => ['required'],
            'payment_type' => ['required'],
            'payment_method' => ['required'],
            'amount_due' => 'required|numeric|min:1000',
            'amount_paid' => 'required|numeric|min:1000',
            'payer_id' => 'required|string|exists:users,id',
        ];
    }
} 