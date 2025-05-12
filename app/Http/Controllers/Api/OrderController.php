<?php

namespace App\Http\Controllers\Api;

use Exception;
use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Str;
use App\Enums\PaymentTypeEnum;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Traits\ApiResponseTrait;
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
        $data['payment_type'] = ($data['amount_due'] !== $data['amount_paid']) ? PaymentTypeEnum::Part : PaymentTypeEnum::Full;
        try {
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
        //update to use middleware to validate is rider has unpaid balance, reject new order
        $data = $request->validated();
        $rider = User::findOrFail($data['payer_id']);
        if ($rider->hasUnpaidBalance()) {
            return response()->json(['message' => 'Please settle old debts first'], 400);
        }
        $data['created_by'] = $request->user()->id;
        $data['branch_id'] = $request->user()->branch_id;
        $data['payment_type'] = ($data['amount_due'] !== $data['amount_paid']) ? PaymentTypeEnum::Part : PaymentTypeEnum::Full;
        $data['payer_id'] = $request->get('payer_id');

        //then update rider balance
        $rider->balance += $data['amount_due'] - $data['amount_paid'];
        $rider->save();

        try {
            $order = Order::create($data);

            return $this->successResponse(new OrderResource($order), 'Order created successfully');
        } catch (\Exception $e) {
            Log::error('Order creation failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Order creation failed',
                'error' => $e->getMessage()
            ], 500);
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
