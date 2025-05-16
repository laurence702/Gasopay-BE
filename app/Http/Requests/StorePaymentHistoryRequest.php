<?php

namespace App\Http\Requests;

use App\Enums\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentHistoryRequest extends FormRequest
{
    public function authorize(): bool  
    {  
        $user = $this->user();

        return $user && ($user->role === RoleEnum::SuperAdmin || $user->role === RoleEnum::Admin);  
    }  


    public function rules()
    {
        return [
            'product_id' => 'required|exists:products,id',
            'user_id' => 'required|exists:users,id',
            'branch_id' => 'required|exists:branches,id',
            'quantity' => 'required|integer|min:1',
            'payment_method' => ['sometimes', 'required', 'string', Rule::in(array_column(\App\Enums\PaymentMethodEnum::cases(), 'value'))],
            'payment_type' => ['sometimes', 'required', 'string', Rule::in(array_column(\App\Enums\PaymentTypeEnum::cases(), 'value'))],
        ];
    }
}
