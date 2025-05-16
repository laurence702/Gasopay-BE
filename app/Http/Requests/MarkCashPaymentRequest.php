<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MarkCashPaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // The route parameter for this custom route is 'paymentHistory' (camelCase)
        return $this->user()->can('markCashPayment', $this->route('paymentHistory'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:0.01', // Amount must be provided and positive
        ];
    }
}
