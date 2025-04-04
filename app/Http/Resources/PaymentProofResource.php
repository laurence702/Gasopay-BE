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
            'amount' => $this->amount,
            'status' => $this->status->value,
            'approved_by' => $this->approver?->fullname,
            'approved_at' => $this->approved_at,
        ];
    }
}
