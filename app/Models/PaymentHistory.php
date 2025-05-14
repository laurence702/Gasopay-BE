<?php

namespace App\Models;

use Illuminate\Support\Str;
use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\PaymentTypeEnum;
use App\Traits\Cacheable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PaymentHistory extends Model
{
    use HasUuids, HasFactory, Cacheable;

    protected $fillable = [
        'order_id',
        'user_id',
        'amount',
        'payment_method',
        'status',
        'reference',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'status' => PaymentStatusEnum::class,
        'payment_method' => PaymentMethodEnum::class,
    ];

    protected $attributes = [
        'status' => PaymentStatusEnum::Pending,
    ];

    /**
     * @var bool
     */
    public $incrementing = false;

    protected static function boot()  
    {  
        parent::boot();  

        static::creating(function ($model) {  
            $model->id = (string) Str::uuid();  
        });  
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function paymentProofs()
    {
        return $this->hasMany(PaymentProof::class);
    }

    public function markAsPaid(float $amount, User $approver, string $paymentMethod): void
    {
        $this->update([
            'amount' => $amount,
            'status' => PaymentStatusEnum::Paid,
            'payment_method' => $paymentMethod,
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);
    }

    /**
     * Override the default cache TTL for payment histories.
     */
    protected function getCacheTTL(): int
    {
        return 900; // 15 minutes for payment histories
    }
}
