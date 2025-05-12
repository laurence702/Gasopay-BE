<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Enums\RoleEnum;
use App\Enums\ProfileVerificationStatusEnum;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\UserResource;

class BranchRiderController extends Controller
{
    /**
     * Get all riders for a branch with filtering options
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getRiders(Request $request): JsonResponse
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
        $perPage = $request->input('per_page', 15);
        
        // Filtering options
        $search = $request->input('search');
        $verificationStatus = $request->input('verification_status');
        $isActive = $request->has('is_active') ? $request->boolean('is_active') : null;
        
        $query = User::with(['userProfile', 'branch'])
            ->where('role', RoleEnum::Rider)
            ->where('branch_id', $branchId);
        
        // Apply filters if provided
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('fullname', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        
        if ($verificationStatus) {
            $query->where('verification_status', $verificationStatus);
        }
        
        if ($isActive !== null) {
            $query->where(function ($q) use ($isActive) {
                if ($isActive) {
                    $q->whereNull('banned_at')->whereNull('deleted_at');
                } else {
                    $q->where(function($subQ) {
                        $subQ->whereNotNull('banned_at')->orWhereNotNull('deleted_at');
                    });
                }
            });
        }
        
        // Sort by most recent first
        $query->orderBy('created_at', 'desc');
        
        // Paginate results
        $riders = $query->paginate($perPage);
        
        return response()->json(UserResource::collection($riders));
    }
    
    /**
     * Get pending approval riders for a branch
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getPendingApprovals(Request $request): JsonResponse
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
        $perPage = $request->input('per_page', 15);
        
        // Get all riders with PENDING verification status
        $pendingRiders = User::with(['userProfile', 'branch'])
            ->where('role', RoleEnum::Rider)
            ->where('branch_id', $branchId)
            ->where('verification_status', ProfileVerificationStatusEnum::PENDING)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
        
        return response()->json(UserResource::collection($pendingRiders));
    }
    
    /**
     * Update a rider's verification status
     * 
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function updateVerificationStatus(Request $request, string $id): JsonResponse
    {
        // Validate the request
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:pending,verified,rejected']
        ]);
        
        // Find the rider
        $rider = User::findOrFail($id);
        
        // Ensure the rider belongs to the admin's branch
        if ($request->user()->role === RoleEnum::Admin && $rider->branch_id !== $request->user()->branch_id) {
            return response()->json(['error' => 'Unauthorized to update this rider'], 403);
        }
        
        // Ensure the user is a rider
        if ($rider->role !== RoleEnum::Rider) {
            return response()->json(['error' => 'User is not a rider'], 400);
        }
        
        // If trying to verify, ensure rider has a profile
        if ($validated['status'] === ProfileVerificationStatusEnum::VERIFIED->value && $rider->userProfile === null) {
            return response()->json(['error' => 'Cannot verify rider without a profile'], 400);
        }
        
        try {
            // Update the verification status
            $rider->verification_status = $validated['status'];
            $rider->verified_by = $request->user()->fullname;
            
            // If verifying, also set email_verified_at
            if ($validated['status'] === ProfileVerificationStatusEnum::VERIFIED->value) {
                $rider->email_verified_at = now();
            }
            
            $rider->save();
            
            return response()->json([
                'message' => 'Rider verification status updated successfully',
                'rider' => new UserResource($rider->fresh(['userProfile', 'branch']))
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating rider verification status: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update verification status'], 500);
        }
    }
} 