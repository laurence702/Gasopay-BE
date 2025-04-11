<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payer' => new UserResource($this->whenLoaded('payer')),
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'product' => new ProductResource($this->whenLoaded('product')),
            'payments' => PaymentHistoryResource::collection($this->whenLoaded('payments')),
            'quantity' => $this->quantity,
            'amount_due' => $this->amount_due,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
} 