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
        'amount',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
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

        $payment = $this->paymentHistory;
        $payment->markAsPaid($this->amount, $approver, PaymentMethodEnum::Bank->value);

        $user = $payment->user;
        $user->balance += $payment->amount_due - $payment->amount_paid;
        $user->save();
    }
}
