<?php

namespace App\Http\Controllers\Api;

use Exception;
use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Str;
use App\Services\SmsService;
use App\Enums\PaymentTypeEnum;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Http\Traits\ApiResponseTrait;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    use ApiResponseTrait;
    public function __construct() {}

    public function index(): AnonymousResourceCollection
    {
        $orders = Order::with(['payer', 'branch', 'payments'])
            ->paginate(10);
        return OrderResource::collection($orders);
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['id'] = (string) Str::uuid();
        $product = Product::findOrFail($data['product_id']);
        $data['amount_due'] = $product->price * $data['quantity'];
        $data['created_by'] = $request->user()->id;
        $data['branch_id'] = $request->user()->branch_id;
        $data['payer_id'] = $request->get('rider_id');
        $data['payment_type'] = ($data['amount_due'] !== $data['amount_paid']) ? PaymentTypeEnum::Part : PaymentTypeEnum::Full;
        try {
            //dd('Order details', $data);
            $order = Order::create($data);

            return (new OrderResource($order))
                ->response()
                ->setStatusCode(201);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['message' => 'Order creation failed', 'error' => $e->getMessage()], 500);
        }
    }

    public function createOrder(StoreOrderRequest $request): JsonResponse
    {
        $data = $request->validated();
        $rider = User::findOrFail($data['payer_id']);
    
        if ($rider->hasUnpaidBalance()) {
            return response()->json(
                ['message' => 'Please settle old debts first'], 
                400
            );
        }
    
        $orderData = $this->prepareOrderData($data, $rider);
        $order = null;
    
        try {
            DB::transaction(function () use ($orderData, $rider, &$order) {
                $order = Order::create($orderData);
                
                $this->updateRiderBalance($rider, $orderData);
                
                $this->sendOrderNotifications($order, $rider);
            });
    
            return $this->successResponse(
                new OrderResource($order), 
                'Order created successfully'
            );
        } catch (\Exception $e) {
            Log::error('Failed to create order: ' . $e->getMessage());
            return response()->json(
                ['message' => 'Failed to create order'], 
                500
            );
        }
    }
    
    protected function prepareOrderData(array $data, User $rider): array
    {
        return [
            'id' => (string) Str::uuid(),
            'created_by' => $rider->id,
            'product' => Str::lower($data['product']),
            'branch_id' => $rider->branch_id,
            'payment_type' => $data['amount_due'] !== $data['amount_paid'] 
                ? PaymentTypeEnum::Part 
                : PaymentTypeEnum::Full,
            'payer_id' => $data['payer_id'],
            'amount_due' => $data['amount_due'],
            'amount_paid' => $data['amount_paid'],
        ];
    }
    
    protected function updateRiderBalance(User $rider, array $orderData): void
    {
        $rider->balance += $orderData['amount_due'] - $orderData['amount_paid'];
        $rider->save();
    }
    
    protected function sendOrderNotifications(Order $order, User $rider): void
    {
        try {
            $smsService = app()->make(\App\Services\SmsService::class);
            $smsService->send(
                $rider->phone, 
                "You have a new order from {$order->branch} for {$order->product} amounting to {$order->amount_due}"
            );
    
            $notificationService = app()->make(\App\Services\NotificationService::class);
            $notificationService->sendOrderCreatedNotification($order);
        } catch (\Exception $e) {
            Log::warning('Failed to send order notification: ' . $e->getMessage());
            // Continue execution even if notification fails
        }
    }

    public function show(Order $order): OrderResource
    {
        $order->load(['payer', 'product', 'branch', 'payments']);
        return new OrderResource($order);
    }

    public function update(UpdateOrderRequest $request, Order $order): OrderResource
    {
        $data = $request->validated();
        if (isset($data['quantity']) && isset($data['product_id'])) {
            $product = Product::findOrFail($data['product_id']);
            $data['amount_due'] = $product->price * $data['quantity'];
        }

        $order->update($data);
        return new OrderResource($order);
    }

    public function destroy(Order $order): JsonResponse
    {
        $order->delete();
        return response()->json(['message' => 'Order deleted'], 204);
    }
}
