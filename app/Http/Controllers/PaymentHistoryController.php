<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Models\PaymentHistory;
use App\Enums\PaymentMethodEnum;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use App\Http\Requests\MarkCashPaymentRequest;
use App\Http\Resources\PaymentHistoryResource;
use App\Http\Requests\StorePaymentHistoryRequest;
use App\Http\Requests\UpdatePaymentHistoryRequest;

class PaymentHistoryController extends Controller
{
    public function __construct()
    {
        // Middleware to restrict access based on roles
        $this->middleware('auth:sanctum');
        $this->middleware('role:SuperAdmin')->only(['store', 'update', 'destroy']);
        $this->middleware('role:Admin|SuperAdmin')->only('markCashPayment');
    }

    public function index(): JsonResponse
    {
        $cacheKey = 'payment_histories:index';
        
        $payments = cache()->remember($cacheKey, 300, function () {
            return PaymentHistory::with(['payer', 'approver', 'product', 'branch'])
                ->paginate(10);
        });

        return PaymentHistoryResource::collection($payments)->response();
    }

    /**
     * Store a new payment transaction (SuperAdmin only).
     */
    public function store(StorePaymentHistoryRequest $request): JsonResponse
    {
        $data = $request->validated();
        $product = Product::findOrFail($data['product_id']);
        $amount_due = $product->price * $data['quantity'];

        $payment = PaymentHistory::create([
            'product_id' => $data['product_id'],
            'payer_id' => $data['payer_id'],
            'branch_id' => $data['branch_id'],
            'amount_due' => $amount_due,
            'quantity' => $data['quantity'],
            'status' => \App\Enums\PaymentStatusEnum::Pending,
        ]);

        cache()->tags(['payment_histories'])->flush();

        return (new PaymentHistoryResource($payment))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display a specific payment history.
     */
    public function show(PaymentHistory $paymentHistory): PaymentHistoryResource
    {
        $paymentHistory = PaymentHistory::findCached($paymentHistory->id);
        $paymentHistory->load(['payer', 'approver', 'product', 'branch', 'paymentProofs']);
        return new PaymentHistoryResource($paymentHistory);
    }

    /**
     * Update a payment history (SuperAdmin only).
     */
    public function update(UpdatePaymentHistoryRequest $request, PaymentHistory $paymentHistory): PaymentHistoryResource
    {
        $data = $request->validated();
        if (isset($data['quantity']) && isset($data['product_id'])) {
            $product = \App\Models\Product::findOrFail($data['product_id']);
            $data['amount_due'] = $product->price * $data['quantity'];
        }

        $paymentHistory->update($data);
        cache()->tags(['payment_histories'])->flush();

        return new PaymentHistoryResource($paymentHistory);
    }

    /**
     * Delete a payment history (SuperAdmin only).
     */
    public function destroy(PaymentHistory $paymentHistory): JsonResponse
    {
        $paymentHistory->delete();
        cache()->tags(['payment_histories'])->flush();
        
        return response()->json(['message' => 'Payment history deleted'], 204);
    }

    /**
     * Mark a cash payment as paid (Branch Admin or SuperAdmin).
     */
    public function markCashPayment(MarkCashPaymentRequest $request, PaymentHistory $paymentHistory): JsonResponse
    {
        $amount = $request->validated()['amount'];
        $paymentHistory->markAsPaid($amount, $request->user(), PaymentMethodEnum::Cash->value);
        cache()->tags(['payment_histories'])->flush();

        // Send payment notification
        try {
            $order = $paymentHistory->order;
            if ($order) {
                $outstanding = $order->amount_due - $order->amount_paid;
                $notificationService = app()->make(\App\Services\NotificationService::class);
                $notificationService->sendPaymentNotification($order, $amount, $outstanding);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to send payment notification: ' . $e->getMessage());
            // Continue execution even if notification fails
        }

        return response()->json(['message' => 'Cash payment marked successfully']);
    }
}
