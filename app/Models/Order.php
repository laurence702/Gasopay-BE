<?php

namespace App\Models;

use App\Enums\OrderStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Order extends Model
{
    use HasUuids;

    protected $fillable = [
        'payer_id',
        'branch_id',
        'product_id',
        'quantity',
        'amount_due',
        'status',
    ];

    protected $casts = [
        'amount_due' => 'decimal:2',
        'quantity' => 'integer',
        'status' => OrderStatusEnum::class,
    ];

    protected $attributes = [
        'status' => OrderStatusEnum::Pending,
    ];

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payer_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PaymentHistory::class);
    }
} 