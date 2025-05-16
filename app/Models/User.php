<?php

namespace App\Models;

use App\Enums\RoleEnum;
use App\Traits\Cacheable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use App\Enums\ProfileVerificationStatusEnum;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasUuids, Cacheable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'fullname',
        'email',
        'phone',
        'password',
        'role',
        'branch_id',
        'verification_status',
        'banned_at',
        'banned_reason',
        'ip_address',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @var bool
     */
    public $incrementing = false;

    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->id = (string) Str::ulid();
        });
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'role' => RoleEnum::class,
        'verification_status' => ProfileVerificationStatusEnum::class,
        'banned_at' => 'datetime',
        'balance' => 'decimal:2',
    ];

    /**
     * Get the user profile associated with the user.
     */
    public function userProfile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    /**
     * Get the branch associated with the user.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Check if the user has a specific role.
     *
     * @param string $role
     * @return bool
     */
    public function hasRole(string $role): bool
    {
        if (!$this->role) {
            return false;
        }

        // Convert the role string to enum case
        $roleEnum = RoleEnum::tryFrom($role);
        if (!$roleEnum) {
            return false;
        }

        return $this->role === $roleEnum;
    }

    public function canApprovePayments()
    {
        return $this->role === RoleEnum::Admin || $this->role === RoleEnum::SuperAdmin;
    }

    /**
     * Override the default cache TTL for users.
     */
    protected function getCacheTTL(): int
    {
        return 1800; // 30 minutes for users
    }

    /**
     * Scope a query to only include active users.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    /**
     * Scope a query to only include deleted users.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDeleted($query)
    {
        return $query->whereNotNull('deleted_at');
    }

    public function hasUnpaidBalance(): bool
    {
        return $this->balance > 0;
    }

    /**
     * rider orders accessor
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'payer_id');
    }
}
