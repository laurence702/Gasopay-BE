<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentHistoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // The route parameter is 'payment_history' for apiResource
        return $this->user()->can('update', $this->route('payment_history'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => 'sometimes|required|string|in:pending,approved,rejected,paid,completed,failed', // Example rule
            'amount' => 'sometimes|required|numeric|min:0',
            // Add other updatable fields and their rules
        ];
    }
}
