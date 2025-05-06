<?php

namespace App\Models;

use App\Enums\VehicleTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class UserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'address',
        'phone',
        'vehicle_type',
        'nin',
        'guarantors_name',
        'guarantors_address',
        'guarantors_phone',
        'profile_pic_url',
    ];

    protected $casts = [
        'vehicle_type' => VehicleTypeEnum::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
