<?php

namespace App\Http\Controllers\Api;

use Exception;
use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Models\PaymentHistory;
use Illuminate\Support\Str;
use App\Services\AfricasTalkingService;
use App\Enums\PaymentTypeEnum;
use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentStatusEnum;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Http\Traits\ApiResponseTrait;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;

class OrderController extends Controller
{
    use ApiResponseTrait;
    
    protected AfricasTalkingService $smsService;
    
    public function __construct(AfricasTalkingService $smsService) 
    {
        $this->smsService = $smsService;
    }

    public function index(): JsonResponse
    {
        $cacheKey = 'orders:' . md5(request()->fullUrl());
        
        return Cache::remember($cacheKey, 300, function() {
            $orders = Order::with(['payer', 'branch', 'payments'])
                ->paginate(30);
            return $this->successResponse(OrderResource::collection($orders), 'Orders retrieved successfully');
        });
    }

    public function createOrder(StoreOrderRequest $request): JsonResponse
    {
        $data = $request->validated();
        $rider = User::findOrFail($data['payer_id']);
    
        if ($rider->balance > 0 && !$rider->can_access_credit) {
            return $this->errorResponse('Rider has outstanding balance and no credit access', 400);
        }
    
        try {
            $result = DB::transaction(function () use ($data, $rider, $request) {
                $order = Order::create([
                    'payer_id' => $data['payer_id'],
                    'created_by' => $request->user()->id,
                    'branch_id' => $request->user()->branch_id,
                    'product' => $data['product'],
                    'amount_due' => $data['amount_due'],
                    'payment_type' => $data['payment_type'],
                    'payment_method' => $data['payment_method'],
                    'payment_status' => $data['amount_due'] == $data['amount_paid'] 
                        ? PaymentStatusEnum::Paid 
                        : PaymentStatusEnum::Pending,
                ]);
 
                // Create payment history for the initial payment
                if ($data['amount_paid'] > 0) {
                   $payment_history =  PaymentHistory::create([
                        'order_id' => $order->id,
                        'user_id' => $data['payer_id'],
                        'amount' => $data['amount_paid'],
                        'payment_method' => $data['payment_method'],
                        'status' => PaymentStatusEnum::Paid,
                        'approved_by' => $request->user()->id,
                        'approved_at' => now(),
                    ]);
                }
                
                // Update rider balance if partial payment
                if ($data['amount_due'] > $data['amount_paid']) {
                    $rider->balance += $data['amount_due'] - $data['amount_paid'];
                    $rider->save();
                }
                
                // Send SMS notification
                try {
                    $this->smsService->send(
                        $rider->phone, 
                        "You have a new order from {$order->branch->name} for {$order->product} amounting to {$order->amount_due}, balance due: {$order->balance}"
                    );
                } catch (\Exception $e) {
                    Log::warning('SMS notification failed: ' . $e->getMessage());
                }
                
                return $order;
            });

            Cache::flush();
    
            return $this->successResponse(
                new OrderResource($result->load(['payer', 'branch', 'payments'])), 
                'Order created successfully',
                201
            );
        } catch (\Exception $e) {
            Log::error('Failed to create order: ' . $e->getMessage());
            return $this->errorResponse('Failed to create order: ' . $e->getMessage(), 500);
        }
    }

    public function show(Order $order): JsonResponse
    {
        $cacheKey = "order:{$order->id}";
        
        $data = Cache::remember($cacheKey, 300, function() use ($order) {
            return new OrderResource($order->load(['payer', 'branch', 'payments']));
        });
        
        return $this->successResponse($data, 'Order retrieved successfully.');
    }

    public function update(UpdateOrderRequest $request, Order $order): JsonResponse
    {
        $data = $request->validated();
        
        try {
            DB::transaction(function() use ($data, $order, $request) {
                // Update order details if needed
                if (isset($data['product']) || isset($data['amount_due']) || isset($data['payment_type'])) {
                    $order->update($data);
                }
                
                // Process additional payment if provided
                if (isset($data['payment_amount']) && $data['payment_amount'] > 0) {
                    // Create payment history record
                    PaymentHistory::create([
                        'order_id' => $order->id,
                        'user_id' => $order->payer_id,
                        'amount' => $data['payment_amount'],
                        'payment_method' => $data['payment_method'] ?? PaymentMethodEnum::Cash,
                        'status' => PaymentStatusEnum::Paid,
                        'reference' => $data['reference'] ?? null,
                        'approved_by' => $request->user()->id,
                        'approved_at' => now(),
                    ]);
                    
                    // If payment completes the order
                    $totalPaid = $order->payments->sum('amount') + $data['payment_amount'];
                    if ($totalPaid >= $order->amount_due) {
                        $order->payment_status = PaymentStatusEnum::Paid;
                        $order->save();
                        
                        // Update rider balance
                        $rider = $order->payer;
                        $rider->balance -= min($rider->balance, $data['payment_amount']);
                        $rider->save();
                    }
                }
                
                // If admin is marking order as paid without actual payment record
                if (isset($data['mark_as_paid']) && $data['mark_as_paid'] === true) {
                    $order->payment_status = PaymentStatusEnum::Paid;
                    $order->save();
                    
                    // Clear rider balance for this order
                    $rider = $order->payer;
                    $outstandingAmount = $order->amount_due - $order->payments->sum('amount');
                    $rider->balance -= min($rider->balance, $outstandingAmount);
                    $rider->save();
                }
            });
            
            // Clear cache
            Cache::forget("order:{$order->id}");
            Cache::flush();
            
            return $this->successResponse(
                new OrderResource($order->fresh()->load(['payer', 'branch', 'payments'])),
                'Order updated successfully'
            );
        } catch (\Exception $e) {
            Log::error('Failed to update order: ' . $e->getMessage());
            return $this->errorResponse('Failed to update order: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(Order $order): JsonResponse
    {
        $order->delete();
        Cache::flush();
        return $this->successResponse(null, 'Order deleted successfully', 200);
    }
}
