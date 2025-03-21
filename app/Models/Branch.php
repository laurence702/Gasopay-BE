<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'location',
        'branch_admin',
        'branch_phone',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'branch_admin');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
