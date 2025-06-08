<?php

namespace App\Http\Requests;

use App\Enums\RoleEnum;
use App\Enums\ProductTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentTypeEnum;

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
            'product_id' => 'required_without:product_type|exists:products,id',
            'product_type' => ['required_without:product_id', 'string', Rule::in(array_column(ProductTypeEnum::cases(), 'value'))],
            'user_id' => 'required|exists:users,id',
            'branch_id' => 'required|exists:branches,id',
            'quantity' => 'required|integer|min:1',
            'payment_method' => ['sometimes', 'required', 'string', Rule::in(array_column(PaymentMethodEnum::cases(), 'value'))],
            'payment_type' => ['sometimes', 'required', 'string', Rule::in(array_column(PaymentTypeEnum::cases(), 'value'))],
        ];
    }
}
