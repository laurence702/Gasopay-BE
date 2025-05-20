<?php

namespace App\Http\Requests;

use App\Enums\RoleEnum;
use App\Enums\PaymentTypeEnum;
use Illuminate\Validation\Rule;
use App\Enums\PaymentMethodEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && in_array($user->role, [RoleEnum::Admin, RoleEnum::SuperAdmin]);
    }

    public function rules(): array
    {
        $rules = [
            'product' => ['required', 'string'],
            'payment_type' => ['required', new Enum(PaymentTypeEnum::class)],
            'payment_method' => ['required', new Enum(PaymentMethodEnum::class)],
            'amount_due' => 'required|numeric|min:0',
            'amount_paid' => 'required|numeric|min:0|lte:amount_due',
            'payer_id' => 'required|string|exists:users,id',
        ];

        // If payment type is partial, require amount_paid and validate minimum amount
        if ($this->input('payment_type') === PaymentTypeEnum::Part->value) {
            $rules['amount_paid'] = 'required|numeric|min:1000';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'amount_paid.min' => 'Partial payment must be at least ₦1,000',
        ];
    }
} 