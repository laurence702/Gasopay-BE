<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

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

        static::creating(function ($branch) {
            // Get the next available ID
            $lastBranch = static::orderBy('id', 'desc')->first();
            $nextId = $lastBranch ? $lastBranch->id + 1 : 1;
            
            // Format as two digits
            $branch->id = str_pad($nextId, 2, '0', STR_PAD_LEFT);
        });
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
