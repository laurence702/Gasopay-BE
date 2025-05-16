<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentProofResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_history_id' => $this->payment_history_id,
            'proof_url' => $this->proof_url,
            'status' => $this->status,
            'approved_by' => $this->when($this->approved_by, function () {
                return $this->approver?->fullname;
            }),
            'approved_at' => $this->approved_at,
            'created_at' => $this->created_at,
            'payment_history' => $this->whenLoaded('paymentHistory', function () {
                return [
                    'id' => $this->paymentHistory->id,
                    'order_id' => $this->paymentHistory->order_id,
                    'amount' => $this->paymentHistory->amount,
                    'payment_method' => $this->paymentHistory->payment_method,
                    'status' => $this->paymentHistory->status,
                    'reference' => $this->paymentHistory->reference,
                ];
            }),
            'order' => $this->whenLoaded('paymentHistory.order', function () {
                return [
                    'id' => $this->paymentHistory->order->id,
                    'product' => $this->paymentHistory->order->product,
                    'amount_due' => $this->paymentHistory->order->amount_due,
                    'balance' => $this->paymentHistory->order->balance,
                    'payment_status' => $this->paymentHistory->order->payment_status,
                ];
            }),
            'user' => $this->whenLoaded('paymentHistory.user', function () {
                return [
                    'id' => $this->paymentHistory->user->id,
                    'fullname' => $this->paymentHistory->user->fullname,
                    'phone' => $this->paymentHistory->user->phone,
                ];
            }),
        ];
    }
}
