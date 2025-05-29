<?php

namespace App\Models;

use App\Enums\OrderStatusEnum;
use App\Enums\PaymentTypeEnum;
use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentStatusEnum;
use App\Traits\GeneratesOrderReference;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasUuids, HasFactory, GeneratesOrderReference;

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
        'order_reference',
        'payer_id',
        'branch_id',
        'product',
        'amount_due',
        'created_by',
        'payment_type',
        'payment_method',
        'payment_status',
    ];

    protected $casts = [
        'amount_due' => 'decimal:2',
        'quantity' => 'integer',
        'payment_status' => PaymentStatusEnum::class,
        'payment_type' => PaymentTypeEnum::class,
        'payment_method' => PaymentMethodEnum::class,
    ];

    protected $attributes = [
        'payment_status' => PaymentStatusEnum::Pending->value,
    ];

    /**
     * Validation rules for the model
     *
     * @return array
     */
    public static function rules(): array
    {
        return [
            'order_reference' => [
                'max:14',
                'min:12',
                'unique:orders,order_reference',
                'required',
                'string',
                'regex:/^ORD-\d{4}-[A-Z0-9]{5}$/',
            ],
            'payer_id' => 'required|exists:users,id',
            'branch_id' => 'required|exists:branches,id',
            'product' => 'required|in:keke,car,cng,pms,lpg',
            'amount_due' => 'required|numeric|min:0',
            'payment_type' => 'required|in:full,part',
            'payment_method' => 'required|in:cash,bank_transfer,wallet',
            'payment_status' => 'required|in:pending,paid,failed,completed,approved,rejected',
        ];
    }

    public static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            $model->id = (string) Str::uuid();
            $model->product = Str::lower($model->product);
            
            // Generate order reference if not provided
            if (empty($model->order_reference)) {
                $model->order_reference = $model->generateOrderReference();
            }
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
     * Get the creator of the order
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the branch of the order
     */
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

    // Computed attribute for balance
    public function getBalanceAttribute()
    {
        $paid = $this->payments()->sum('amount');
        return $this->amount_due - $paid;
    }

    // Computed attribute for payment_status
    public function getComputedPaymentStatusAttribute()
    {
        $paid = $this->payments()->sum('amount');
        if ($paid >= $this->amount_due) {
            return PaymentStatusEnum::Paid;
        } elseif ($paid > 0) {
            return PaymentStatusEnum::Pending;
        } else {
            return PaymentStatusEnum::Pending;
        }
    }
} 