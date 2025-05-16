<?php

namespace App\Http\Controllers\Api;

use App\Models\Order;
use App\Models\PaymentProof;
use App\Models\PaymentHistory;
use App\Enums\ProofStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\PaymentMethodEnum;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\PaymentProofResource;
use App\Http\Traits\ApiResponseTrait;
use App\Services\AfricasTalkingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class PaymentProofController extends Controller
{
    use ApiResponseTrait;
    
    protected AfricasTalkingService $smsService;
    
    public function __construct(AfricasTalkingService $smsService)
    {
        $this->smsService = $smsService;
    }
    
    /**
     * Display a listing of payment proofs pending approval
     */
    public function index(Request $request): JsonResponse
    {
        $cacheKey = 'payment_proofs:' . md5($request->fullUrl());
        
        $proofs = Cache::remember($cacheKey, 300, function() use ($request) {
            $query = PaymentProof::with(['paymentHistory.order', 'paymentHistory.user', 'approver'])
                ->orderBy('created_at', 'desc');
            
            // Filter by status if provided
            if ($request->has('status')) {
                $query->where('status', $request->status);
            } else {
                // Default to showing pending proofs
                $query->where('status', ProofStatusEnum::Pending->value);
            }
            
            return $query->paginate(15);
        });
        
        return $this->successResponse(
            PaymentProofResource::collection($proofs),
            'Payment proofs retrieved successfully'
        );
    }
    
    /**
     * Store a newly created payment proof
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'payment_amount' => 'required|numeric|min:0',
            'proof_url' => 'required|url',
            'payment_method' => 'required|in:bank_transfer,mobile_money',
            'reference' => 'required|string',
        ]);
        
        try {
            $result = DB::transaction(function() use ($validated, $request) {
                $order = Order::findOrFail($validated['order_id']);
                
                // Check if order belongs to requesting user or is an admin
                if ($request->user()->id !== $order->payer_id && 
                    !in_array($request->user()->role, ['Admin', 'SuperAdmin'])) {
                    throw new \Exception('Unauthorized to add payment for this order');
                }
                
                // Create payment history record
                $paymentHistory = PaymentHistory::create([
                    'order_id' => $validated['order_id'],
                    'user_id' => $order->payer_id,
                    'amount' => $validated['payment_amount'],
                    'payment_method' => $validated['payment_method'],
                    'status' => PaymentStatusEnum::Pending,
                    'reference' => $validated['reference'],
                ]);
                
                // Create payment proof
                $paymentProof = PaymentProof::create([
                    'payment_history_id' => $paymentHistory->id,
                    'proof_url' => $validated['proof_url'],
                    'status' => ProofStatusEnum::Pending,
                ]);
                
                // Notify admin via SMS that there's a new payment proof to approve
                try {
                    $admins = \App\Models\User::where('role', 'Admin')
                        ->where('branch_id', $order->branch_id)
                        ->pluck('phone')
                        ->toArray();
                    
                    if (!empty($admins)) {
                        $this->smsService->sendBulk(
                            $admins,
                            "New payment proof uploaded by {$order->payer->fullname} for order {$order->id}. Amount: {$validated['payment_amount']}. Please review."
                        );
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to send admin notification: ' . $e->getMessage());
                    // Continue execution even if notification fails
                }
                
                return $paymentProof;
            });
            
            // Clear cache
            Cache::flush();
            
            return $this->successResponse(
                new PaymentProofResource($result->load('paymentHistory.order')),
                'Payment proof uploaded successfully. Awaiting admin approval.',
                201
            );
            
        } catch (\Exception $e) {
            Log::error('Payment proof upload failed: ' . $e->getMessage());
            return $this->errorResponse('Payment proof upload failed: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Approve a payment proof
     */
    public function approve(PaymentProof $paymentProof): JsonResponse
    {
        try {
            DB::transaction(function() use ($paymentProof) {
                // Update payment proof status
                $paymentProof->status = ProofStatusEnum::Approved->value;
                $paymentProof->approved_by = Auth::id();
                $paymentProof->approved_at = now();
                $paymentProof->save();
                
                // Update payment history
                $paymentHistory = $paymentProof->paymentHistory;
                $paymentHistory->status = PaymentStatusEnum::Paid;
                $paymentHistory->approved_by = Auth::id();
                $paymentHistory->approved_at = now();
                $paymentHistory->save();
                
                // Get the order and update balance
                $order = $paymentHistory->order;
                $totalPaid = $order->payments->sum('amount');
                
                // If order is fully paid
                if ($totalPaid >= $order->amount_due) {
                    $order->payment_status = PaymentStatusEnum::Paid;
                    $order->save();
                }
                
                // Update rider balance
                $rider = $order->payer;
                $rider->balance = max(0, $rider->balance - $paymentHistory->amount);
                $rider->save();
                
                // Notify rider that payment was approved
                try {
                    $this->smsService->send(
                        $rider->phone,
                        "Your payment of {$paymentHistory->amount} for order {$order->id} has been approved. Thank you."
                    );
                } catch (\Exception $e) {
                    Log::warning('Failed to send rider notification: ' . $e->getMessage());
                    // Continue execution even if notification fails
                }
            });
            
            // Clear cache
            Cache::flush();
            
            return $this->successResponse(
                new PaymentProofResource($paymentProof->fresh()->load('paymentHistory.order')),
                'Payment proof approved successfully'
            );
            
        } catch (\Exception $e) {
            Log::error('Payment proof approval failed: ' . $e->getMessage());
            return $this->errorResponse('Payment proof approval failed: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Reject a payment proof
     */
    public function reject(Request $request, PaymentProof $paymentProof): JsonResponse
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:255',
        ]);
        
        try {
            DB::transaction(function() use ($paymentProof, $validated) {
                // Update payment proof status
                $paymentProof->status = ProofStatusEnum::Rejected->value;
                $paymentProof->approved_by = Auth::id();
                $paymentProof->approved_at = now();
                $paymentProof->save();
                
                // Update payment history status to rejected
                $paymentHistory = $paymentProof->paymentHistory;
                $paymentHistory->status = PaymentStatusEnum::Rejected;
                $paymentHistory->save();
                
                // Notify rider that payment was rejected
                try {
                    $rider = $paymentHistory->user;
                    $order = $paymentHistory->order;
                    
                    $this->smsService->send(
                        $rider->phone,
                        "Your payment proof for order {$order->id} was rejected. Reason: {$validated['rejection_reason']}. Please upload a valid proof."
                    );
                } catch (\Exception $e) {
                    Log::warning('Failed to send rider notification: ' . $e->getMessage());
                    // Continue execution even if notification fails
                }
            });
            
            // Clear cache
            Cache::flush();
            
            return $this->successResponse(
                new PaymentProofResource($paymentProof->fresh()->load('paymentHistory.order')),
                'Payment proof rejected successfully'
            );
            
        } catch (\Exception $e) {
            Log::error('Payment proof rejection failed: ' . $e->getMessage());
            return $this->errorResponse('Payment proof rejection failed: ' . $e->getMessage(), 500);
        }
    }
} 