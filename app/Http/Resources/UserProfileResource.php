<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\VehicleTypeResource;

class UserProfileResource extends JsonResource
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
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'address' => $this->address,
            'phone' => $this->phone,
            'vehicle_type' => $this->vehicle_type,
            'nin' => $this->nin,
            'guarantors_name' => $this->guarantors_name,
            'guarantors_address' => $this->guarantors_address,
            'guarantors_phone' => $this->guarantors_phone,
            'profilePicUrl' => $this->profile_pic_url,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
