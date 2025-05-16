<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentHistoryResource extends JsonResource
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
            'order_id' => $this->order_id,
            
            // Information from the related Order
            'order_product_type' => $this->whenLoaded('order', $this->order?->product), // Enum value like 'pms', 'lpg'
            'order_branch_name' => $this->whenLoaded('order', optional($this->order?->branch)->name),
            'order_total_amount_due' => $this->whenLoaded('order', $this->order?->amount_due),
            'order_payment_type' => $this->whenLoaded('order', optional($this->order?->payment_type)->value),
            
            // Payer (User) Information for this payment history
            'payer_id' => $this->user_id,
            'payer_name' => $this->whenLoaded('user', optional($this->user)->fullname),
            
            // This specific Payment History transaction details
            'transaction_amount' => $this->amount,
            'payment_method' => optional($this->payment_method)->value,
            'status' => optional($this->status)->value,
            'reference' => $this->reference,
            
            // Approver Information for this payment history
            'approved_by_id' => $this->approved_by,
            'approved_by_name' => $this->whenLoaded('approver', optional($this->approver)->fullname),
            'approved_at' => $this->approved_at,
            
            'payment_proofs' => PaymentProofResource::collection($this->whenLoaded('paymentProofs')),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
