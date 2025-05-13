<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'fullname' => $this->fullname,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
            'verification_status' => $this->verification_status,
            'verified_by' => $this->verified_by,
            'branch_id' => $this->branch_id,
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'user_profile' => new UserProfileResource($this->whenLoaded('userProfile')),
            'balance' => $this->balance,
            'orders_count' => $this->whenCounted('orders'),
            'orders_total_amount' => $this->when(isset($this->orders_sum_amount_due), function() {
                return $this->orders_sum_amount_due;
            }),
            'banned_at' => $this->banned_at,
            'banned_reason' => $this?->banned_reason,
            'ip_address' => $this?->ip_address,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
