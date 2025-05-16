<?php

namespace App\Http\Requests;

use App\Enums\RoleEnum;
use App\Enums\OrderStatusEnum;
use App\Enums\PaymentTypeEnum;
use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && in_array($user->role, [RoleEnum::Admin, RoleEnum::SuperAdmin, RoleEnum::Rider]);
    }

    public function rules(): array
    {
        $user = $this->user();
        $baseRules = [
            'product_id' => 'sometimes|exists:products,id',
            'payer_id' => 'sometimes|exists:users,id',
            'branch_id' => 'sometimes|exists:branches,id',
            'quantity' => 'sometimes|integer|min:1',
            'status' => ['sometimes', new Enum(OrderStatusEnum::class)],
            'product' => ['sometimes', 'string'],
            'payment_type' => ['sometimes', new Enum(PaymentTypeEnum::class)],
            'payment_method' => ['sometimes', new Enum(PaymentMethodEnum::class)],
            'payment_status' => ['sometimes', new Enum(PaymentStatusEnum::class)],
            'amount_due' => 'sometimes|numeric|min:0',
        ];
        
        // Fields for payment processing
        $paymentRules = [
            'payment_amount' => 'sometimes|required_with:payment_method|numeric|min:0',
            'reference' => 'sometimes|nullable|string|max:255',
            'proof_url' => 'sometimes|nullable|url|max:255',
        ];
        
        // Admin only fields
        $adminRules = [
            'mark_as_paid' => 'sometimes|boolean',
        ];
        
        // Include admin-only rules for admin/super admin
        if (in_array($user->role, [RoleEnum::Admin, RoleEnum::SuperAdmin])) {
            return array_merge($baseRules, $paymentRules, $adminRules);
        }
        
        // For riders, only allow payment processing fields
        return array_merge($baseRules, $paymentRules);
    }
} 