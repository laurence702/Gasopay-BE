<?php

namespace App\Http\Controllers;

use App\Models\PaymentProof;
use Illuminate\Http\Request;
use App\Enums\ProofStatusEnum;
use App\Models\PaymentHistory;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\PaymentProofResource;
use App\Http\Requests\StorePaymentProofRequest;

class PaymentProofController extends Controller
{
    public function store(StorePaymentProofRequest $request, PaymentHistory $payment)
    {
        // Rider submits proof
        if (Auth::user()->id !== $payment->payer_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $proof = $payment->paymentProofs()->create([
            'proof_url' => $request->proof_url,
            'amount' => $request->amount,
        ]);

        return new PaymentProofResource($proof);
    }

    public function approve(PaymentProof $proof)
    {
        if (!Auth::check() ||  !Auth::user()?->canApprovePayments()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $proof->approve(Auth::user());
        return new PaymentProofResource($proof->load('paymentHistory'));
    }

    public function reject(PaymentProof $proof)
    {
        if (!Auth::user()?->canApprovePayments()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $proof->update(['status' => ProofStatusEnum::Rejected]);
        return new PaymentProofResource($proof);
    }
}
