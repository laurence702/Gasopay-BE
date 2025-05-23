<?php

namespace App\Models;

use Illuminate\Support\Str;
use App\Enums\ProofStatusEnum;
use App\Enums\PaymentMethodEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PaymentProof extends Model
{
    use HasUuids;

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

    protected $fillable = [
        'payment_history_id',
        'proof_url',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'status' => ProofStatusEnum::class,
        'approved_at' => 'datetime',
    ];

    public function paymentHistory()
    {
        return $this->belongsTo(PaymentHistory::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Approval Logic
    public function approve(User $approver)
    {
        $this->status = ProofStatusEnum::Approved;
        $this->approved_by = $approver->id;
        $this->approved_at = now();
        $this->save();
    }
}
