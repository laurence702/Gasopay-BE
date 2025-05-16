<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Models\PaymentHistory;
use App\Models\Order;
use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\OrderStatusEnum;
use App\Enums\PaymentTypeEnum;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Http\Requests\MarkCashPaymentRequest;
use App\Http\Resources\PaymentHistoryResource;
use App\Http\Requests\StorePaymentHistoryRequest;
use App\Http\Requests\UpdatePaymentHistoryRequest;

class PaymentHistoryController extends Controller
{
    use AuthorizesRequests;

    public function __construct()
    {
        // Middleware to restrict access based on roles
        // $this->middleware('auth:sanctum');
        // $this->middleware('role:SuperAdmin')->only(['store', 'update', 'destroy']);
        // $this->middleware('role:Admin|SuperAdmin')->only('markCashPayment');
    }

    public function index(): JsonResponse
    {
        $cacheKey = 'payment_histories:index';
        
        $payments = cache()->remember($cacheKey, 300, function () {
            return PaymentHistory::with(['user', 'approver'])
                ->paginate(10);
        });

        return PaymentHistoryResource::collection($payments)->response();
    }

    /**
     * Store a new payment transaction (SuperAdmin only).
     */
    public function store(StorePaymentHistoryRequest $request): JsonResponse
    {
        $this->authorize('create', PaymentHistory::class);

        $data = $request->validated();
        $productModel = Product::findOrFail($data['product_id']);
        $amount_due = $productModel->price * $data['quantity'];

        // Create Order first
        $order = Order::create([
            'payer_id' => $data['user_id'],
            'branch_id' => $data['branch_id'],
            'product' => $productModel->name,
            'amount_due' => $amount_due,
            'created_by' => $request->user()->id,
            'payment_type' => $data['payment_type'] ?? PaymentTypeEnum::Full->value,
            'payment_method' => $data['payment_method'] ?? PaymentMethodEnum::Cash->value,
            'payment_status' => PaymentStatusEnum::Pending->value,
        ]);

        // Then create PaymentHistory linked to this new Order
        $payment = PaymentHistory::create([
            'order_id' => $order->id,
            'user_id' => $data['user_id'],
            'amount' => $amount_due,
            'payment_method' => $order->payment_method,
            'status' => PaymentStatusEnum::Pending,
        ]);

        // Load relationships for the resource, especially the new order
        $payment->load(['order.branch', 'user', 'approver', 'paymentProofs']);

        return (new PaymentHistoryResource($payment))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display a specific payment history.
     */
    public function show(PaymentHistory $paymentHistory): PaymentHistoryResource
    {
        $this->authorize('view', $paymentHistory);
        
        // Eager load necessary relationships for the resource
        $paymentHistory->load(['order.branch', 'user', 'approver', 'paymentProofs']);
        // Note: If order needs product, it should be 'order.product'
        // Assuming PaymentHistoryResource might use order.branch, user (payer), approver, and paymentProofs.

        // The findCached and subsequent load are removed as $paymentHistory is already resolved
        // and eager loading is now done above.
        // $paymentHistory = PaymentHistory::findCached($paymentHistory->id);
        // $paymentHistory->load(['payer', 'approver', 'product', 'branch', 'paymentProofs']); 
        
        return new PaymentHistoryResource($paymentHistory);
    }

    /**
     * Update a payment history (SuperAdmin only).
     */
    public function update(UpdatePaymentHistoryRequest $request, PaymentHistory $paymentHistory): PaymentHistoryResource
    {
        $this->authorize('update', $paymentHistory);

        $data = $request->validated();
        
        // Remove logic for product_id, quantity, amount_due as these are Order properties
        // if (isset($data['quantity']) && isset($data['product_id'])) {
        //     $product = \App\Models\Product::findOrFail($data['product_id']);
        //     $data['amount_due'] = $product->price * $data['quantity'];
        // }

        $paymentHistory->update($data); // Only updates fields defined in $request->validated() and PH fillable
        
        // Eager load relationships for the resource
        $paymentHistory->load(['order.branch', 'user', 'approver', 'paymentProofs']);

        return new PaymentHistoryResource($paymentHistory);
    }

    /**
     * Delete a payment history (SuperAdmin only).
     */
    public function destroy(PaymentHistory $paymentHistory): JsonResponse
    {
        $this->authorize('delete', $paymentHistory); // Added authorize
        $paymentHistory->delete();
        // cache()->tags(['payment_histories'])->flush(); // Temporarily commented out
        
        return response()->json(['message' => 'Payment history deleted'], 204);
    }

    /**
     * Mark a cash payment as paid (Branch Admin or SuperAdmin).
     */
    public function markCashPayment(MarkCashPaymentRequest $request, PaymentHistory $paymentHistory): JsonResponse
    {
        $this->authorize('markCashPayment', $paymentHistory); // Added authorize
        $amount = $request->validated()['amount'];
        $paymentHistory->markAsPaid($amount, $request->user(), PaymentMethodEnum::Cash->value);
        // cache()->tags(['payment_histories'])->flush(); // Temporarily commented out

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
