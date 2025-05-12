<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Order;
use App\Enums\RoleEnum;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\UserResource;
use App\Http\Resources\OrderResource;

class QRScannerController extends Controller
{
    /**
     * Process a QR code scan from a branch admin
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function processScan(Request $request): JsonResponse
    {
        // Validate the request
        $validated = $request->validate([
            'scan_data' => ['required', 'string'],
            'scan_type' => ['required', 'string', 'in:rider_id,order_id']
        ]);
        
        // Get branch_id from authenticated admin
        $branchId = $request->user()->branch_id;
        
        try {
            // Process scan data based on type
            if ($validated['scan_type'] === 'rider_id') {
                return $this->processRiderScan($validated['scan_data'], $branchId);
            } elseif ($validated['scan_type'] === 'order_id') {
                return $this->processOrderScan($validated['scan_data'], $branchId);
            }
            
            return response()->json(['error' => 'Invalid scan type'], 400);
        } catch (\Exception $e) {
            Log::error('QR scan processing error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to process QR scan'], 500);
        }
    }
    
    /**
     * Process a rider ID scan
     * 
     * @param string $riderId
     * @param string $branchId
     * @return JsonResponse
     */
    private function processRiderScan(string $riderId, string $branchId): JsonResponse
    {
        // Find the rider
        $rider = User::with('userProfile')
            ->where('id', $riderId)
            ->where('role', RoleEnum::Rider)
            ->first();
            
        if (!$rider) {
            return response()->json(['error' => 'Rider not found'], 404);
        }
        
        // Get rider's basic information
        $riderData = new UserResource($rider);
        
        // Get rider's pending orders at this branch
        $pendingOrders = Order::where('user_id', $riderId)
            ->where('branch_id', $branchId)
            ->where('status', 'pending') // Adjust status as per your model
            ->get();
            
        // Get rider's order history at this branch
        $recentOrders = Order::where('user_id', $riderId)
            ->where('branch_id', $branchId)
            ->where('status', '!=', 'pending') // Adjust status as per your model
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        
        return response()->json([
            'rider' => $riderData,
            'pending_orders' => OrderResource::collection($pendingOrders),
            'recent_orders' => OrderResource::collection($recentOrders),
            'scan_type' => 'rider_id'
        ]);
    }
    
    /**
     * Process an order ID scan
     * 
     * @param string $orderId
     * @param string $branchId
     * @return JsonResponse
     */
    private function processOrderScan(string $orderId, string $branchId): JsonResponse
    {
        // Find the order
        $order = Order::with(['user', 'user.userProfile', 'product'])
            ->where('id', $orderId)
            ->where('branch_id', $branchId)
            ->first();
            
        if (!$order) {
            return response()->json(['error' => 'Order not found or not associated with this branch'], 404);
        }
        
        return response()->json([
            'order' => new OrderResource($order),
            'rider' => new UserResource($order->user),
            'scan_type' => 'order_id'
        ]);
    }
} 