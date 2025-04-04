<?php

namespace App\Http\Requests;

use App\Enums\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;

class StorePaymentHistoryRequest extends FormRequest
{
    public function authorize(): bool  
    {  
        $user = $this->user();

        return $user && $user->role === RoleEnum::SuperAdmin;  
    }  


    public function rules()
    {
        return [
            'product_id' => 'required|exists:products,id',
            'payer_id' => 'required|exists:users,id',
            'branch_id' => 'required|exists:branches,id',
            'amount_due' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:1',
        ];
    }
}
