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
        'payer_id',
        'branch_id',
        'amount',
        'status',
        'payment_method',
        'approved_by',
        'approved_at',
        'payment_type',
        'payment_proof_id',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'status' => PaymentStatusEnum::class,
        'payment_method' => PaymentMethodEnum::class,
        'payment_type' => PaymentTypeEnum::class,
        'paid_at' => 'datetime',
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

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payer_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function paymentProof(): BelongsTo
    {
        return $this->belongsTo(PaymentProof::class);
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
