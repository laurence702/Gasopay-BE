<?php

namespace App\Models;

use App\Enums\OrderStatusEnum;
use App\Enums\PaymentTypeEnum;
use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentStatusEnum;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use HasUuids;

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The data type of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    protected $fillable = [
        'payer_id',
        'branch_id',
        'product',
        'amount_due',
        'status',
        'created_by',
        'payment_type',
        'payment_method',
        'amount_paid',
        'payment_status',
    ];

    protected $casts = [
        'amount_due' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'quantity' => 'integer',
        'status' => OrderStatusEnum::class,
        'payment_status' => PaymentStatusEnum::class,
        'payment_type' => PaymentTypeEnum::class,
        'payment_method' => PaymentMethodEnum::class,
    ];

    protected $attributes = [
        'status' => OrderStatusEnum::Pending,
    ];

    public static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->id = (string) Str::uuid();
            $model->product = Str::lower($model->product);
        });
    }

    /**
     * Generate a new UUID for the model.
     */
    public function newUniqueId(): string
    {
        return (string) Str::uuid();
    }

    /**
     * Get the columns that should receive a unique identifier.
     *
     * @return array
     */
    public function uniqueIds(): array
    {
        return ['id'];
    }

    /**
     * Get the payer(rider, user) of the order
    */
    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payer_id');
    }

    /**
     * Get the admin who created the order
    */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PaymentHistory::class);
    }

    public function orderOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payer_id');
    }
} 