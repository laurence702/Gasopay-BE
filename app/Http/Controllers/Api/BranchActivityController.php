<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Branch;
use App\Models\Order;
use App\Models\PaymentHistory;
use App\Enums\RoleEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class BranchActivityController extends Controller
{
    /**
     * Get recent activities for a branch
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getRecentActivities(Request $request): JsonResponse
    {
        // Get branch_id from request, or if not provided use authenticated admin's branch
        $branchId = $request->input('branch_id');
        
        if (!$branchId && $request->user()->role === RoleEnum::Admin) {
            $branchId = $request->user()->branch_id;
        }
        
        // Ensure the requesting user has access to this branch
        if ($request->user()->role === RoleEnum::Admin && $request->user()->branch_id != $branchId) {
            return response()->json(['error' => 'Unauthorized to access this branch'], 403);
        }

        // Pagination
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        
        // Get activities from the past 30 days by default, or use date range if provided
        $startDate = $request->input('start_date', Carbon::now()->subDays(30)->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());
        
        // Get activities from different sources
        // 1. Order completions
        $orders = Order::with('user')
            ->where('branch_id', $branchId)
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->where('status', 'completed') // Adjust status as per your model
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'activity' => 'Order Completed',
                    'rider' => [
                        'id' => $order->user->id,
                        'name' => $order->user->fullname,
                    ],
                    'amount' => $order->total_amount, // Adjust column name as per your model
                    'status' => 'Completed',
                    'date' => $order->completed_at ?? $order->updated_at
                ];
            });
            
        // 2. Fuel purchases or payment histories
        $payments = PaymentHistory::with('user')
            ->whereHas('user', function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            })
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'activity' => 'Fuel Purchase',
                    'rider' => [
                        'id' => $payment->user->id,
                        'name' => $payment->user->fullname,
                    ],
                    'amount' => $payment->amount,
                    'status' => $payment->status,
                    'date' => $payment->created_at
                ];
            });
            
        // 3. New rider registrations
        $registrations = User::where('role', RoleEnum::Rider)
            ->where('branch_id', $branchId)
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'activity' => 'Rider Registration',
                    'rider' => [
                        'id' => $user->id,
                        'name' => $user->fullname,
                    ],
                    'amount' => null,
                    'status' => $user->verification_status,
                    'date' => $user->created_at
                ];
            });
            
        // Combine all activities
        $allActivities = $orders->concat($payments)->concat($registrations)
            ->sortByDesc('date')
            ->values()
            ->all();
            
        // Paginate the results
        $offset = ($page - 1) * $perPage;
        $paginatedActivities = array_slice($allActivities, $offset, $perPage);
        
        return response()->json([
            'data' => $paginatedActivities,
            'meta' => [
                'current_page' => (int)$page,
                'per_page' => (int)$perPage,
                'total' => count($allActivities),
                'last_page' => ceil(count($allActivities) / $perPage)
            ]
        ]);
    }

    /**
     * Get order history for a branch
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getOrderHistory(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Order::with(['orderOwner', 'paymentHistories']); // Include payment histories
            
        // Role-based filtering
        switch ($user->role) {
            case RoleEnum::SuperAdmin:
                // SuperAdmin can see all orders
                break;
                
            case RoleEnum::Admin:
                // Admin can only see orders from their branch
                $query->where('branch_id', $user->branch_id);
                break;
                
            case RoleEnum::Rider:
                // Rider can only see their own orders
                $query->where('user_id', $user->id);
                break;
                
            default:
                return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        // Pagination
        $perPage = $request->input('per_page', 15);
        
        // Filtering options
        $status = $request->input('status');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $branchId = $request->input('branch_id');
        
        // Apply filters if provided
        if ($branchId && $user->role === RoleEnum::SuperAdmin) {
            $query->where('branch_id', $branchId);
        }
        
        if ($status) {
            $query->where('status', $status);
        }
        
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);
        }
        
        // Sort by most recent first
        $query->orderBy('created_at', 'desc');
        
        // Paginate results
        $orders = $query->paginate($perPage);
        
        // Transform the response to include payment information
        $orders->getCollection()->transform(function ($order) {
            $order->total_paid = $order->paymentHistories->sum('amount');
            $order->remaining_balance = $order->amount_due - $order->total_paid;
            $order->payment_status = $order->total_paid >= $order->amount_due ? 'paid' : 'partial';
            return $order;
        });
        
        return response()->json($orders);
    }
} 