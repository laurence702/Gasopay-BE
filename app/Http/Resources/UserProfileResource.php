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
            'user' => new UserResource($this->whenLoaded('user')),
            'address' => $this->address,
            'vehicle_type' => $this->vehicle_type,
            'nin' => $this->nin,
            'guarantors_name' => $this->guarantors_name,
            'guarantors_address' => $this->guarantors_address,
            'guarantors_phone' => $this->guarantors_phone,
            'profile_pic_url' => $this->profile_pic_url,
        ];
    }
}
