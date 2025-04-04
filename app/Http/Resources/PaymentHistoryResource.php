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
            'product' => $this->product->name,
            'payer' => $this->payer->fullname,
            'approver' => $this->approver?->fullname,
            'branch' => $this->branch->name,
            'amount_due' => $this->amount_due,
            'amount_paid' => $this->amount_paid,
            'outstanding' => $this->outstanding,
            'payment_type' => $this->payment_type->value,
            'payment_method' => $this->payment_method->value,
            'status' => $this->status->value,
            'quantity' => $this->quantity,
            'created_at' => $this->created_at
        ];
    }
}
