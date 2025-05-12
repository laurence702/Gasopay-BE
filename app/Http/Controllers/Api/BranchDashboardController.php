<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Branch;
use App\Models\Order;
use App\Enums\RoleEnum;
use App\Enums\ProfileVerificationStatusEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class BranchDashboardController extends Controller
{
    /**
     * Get dashboard statistics for a branch
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getStatistics(Request $request): JsonResponse
    {
        // Get branch_id from request, or if not provided use authenticated admin's branch
        $branchId = $request->input('branch_id') ?? $request->user()->branch_id;
        
        if (!$branchId && $request->user()->role === RoleEnum::Admin) {
            $branchId = $request->user()->branch_id;
        }
        
        // Ensure the requesting user has access to this branch
        if ($request->user()->role === RoleEnum::Admin && $request->user()->branch_id != $branchId) {
            return response()->json(['error' => 'Unauthorized to access this branch'], 403);
        }

        // Total riders count for this branch
        $totalRiders = User::where('role', RoleEnum::Rider)
            ->where('branch_id', $branchId)
            ->count();
            
        // New riders this month
        $newRidersThisMonth = User::where('role', RoleEnum::Rider)
            ->where('branch_id', $branchId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // Active orders count
        $activeOrders = Order::where('branch_id', $branchId)
            ->where('status', 'active') // Adjust status as per your model
            ->count();
            
        // Order completion rate
        $totalOrdersThisMonth = Order::where('branch_id', $branchId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
            
        $completedOrdersThisMonth = Order::where('branch_id', $branchId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->where('status', 'completed') // Adjust status as per your model
            ->count();
            
        $completionRate = $totalOrdersThisMonth > 0 
            ? round(($completedOrdersThisMonth / $totalOrdersThisMonth) * 100, 1) 
            : 0;

        // Weekly revenue
        $weeklyRevenue = Order::where('branch_id', $branchId)
            ->where('status', 'completed') // Adjust status as per your model
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->sum('total_amount'); // Adjust column name as per your model
            
        // Last week's revenue for comparison
        $lastWeekRevenue = Order::where('branch_id', $branchId)
            ->where('status', 'completed') // Adjust status as per your model
            ->whereBetween('created_at', [
                now()->subWeek()->startOfWeek(), 
                now()->subWeek()->endOfWeek()
            ])
            ->sum('total_amount'); // Adjust column name as per your model
            
        $revenueChangePercent = $lastWeekRevenue > 0 
            ? round((($weeklyRevenue - $lastWeekRevenue) / $lastWeekRevenue) * 100, 1)
            : 0;

        // Pending approvals count
        $pendingApprovals = User::where('role', RoleEnum::Rider)
            ->where('branch_id', $branchId)
            ->where('verification_status', ProfileVerificationStatusEnum::PENDING)
            ->count();

        return response()->json([
            'total_riders' => [
                'count' => $totalRiders,
                'new_this_month' => $newRidersThisMonth
            ],
            'active_orders' => [
                'count' => $activeOrders,
                'completion_rate' => $completionRate
            ],
            'weekly_revenue' => [
                'amount' => $weeklyRevenue,
                'change_percent' => $revenueChangePercent,
            ],
            'pending_approvals' => [
                'count' => $pendingApprovals
            ]
        ]);
    }

    /**
     * Get information about a specific branch
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getBranchInfo(Request $request): JsonResponse
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

        // Get branch details
        $branch = Branch::with('branchAdmin')->findOrFail($branchId);

        return response()->json($branch);
    }
} 