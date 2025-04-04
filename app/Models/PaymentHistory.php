<?php

namespace App\Models;

use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PaymentHistory extends Model
{
    use HasUuids;

    protected $fillable = [
        'order_id',
        'payer_id',
        'branch_id',
        'amount',
        'status',
        'payment_method',
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
}
