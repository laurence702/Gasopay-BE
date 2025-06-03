<?php

namespace App\Models;

use App\Enums\RoleEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Branch extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'name',
        'location',
        'branch_phone',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function admin()
    {
        return $this->hasOne(User::class)->where('role', RoleEnum::Admin);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
