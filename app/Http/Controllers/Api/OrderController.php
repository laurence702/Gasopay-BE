<?php

namespace App\Http\Controllers\Api;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\OrderResource;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    public function __construct()
    {
        //comment: move middleware to routes using ->withMiddleware()
        $this->middleware('auth:sanctum');
        $this->middleware('role:SuperAdmin')->only(['store', 'update', 'destroy']);
    }

    public function index(): AnonymousResourceCollection
    {
        $orders = Order::with(['payer', 'product', 'branch', 'payments'])
            ->paginate(10);
        return OrderResource::collection($orders);
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $data = $request->validated();
        $product = Product::findOrFail($data['product_id']);
        $data['amount_due'] = $product->price * $data['quantity'];

        $order = Order::create($data);

        return (new OrderResource($order))
            ->response()
            ->setStatusCode(201);
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