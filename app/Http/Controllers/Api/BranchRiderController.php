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
use Illuminate\Support\Facades\Cache;
use App\Http\Requests\AdminRegisterRiderRequest;
use App\Services\RiderRegistrationService;
use App\Http\Traits\ApiResponseTrait;
use Illuminate\Http\Response;

class BranchRiderController extends Controller
{
    use ApiResponseTrait;

    protected $riderRegistrationService;

    public function __construct(RiderRegistrationService $riderRegistrationService)
    {
        $this->riderRegistrationService = $riderRegistrationService;
    }

    /**
     * Register a new rider by a Branch Admin.
     *
     * @param AdminRegisterRiderRequest $request
     * @return JsonResponse
     */
    public function store(AdminRegisterRiderRequest $request): JsonResponse
    {
        Log::info('Admin attempting to register rider', $request->all());
        try {
            $validated = $request->validated();

            // Default role to Rider and set verification status based on admin's choice
            $validated['role'] = RoleEnum::Rider->value;
            $verifiedImmediately = $validated['verify_now'] ?? false;

            $rider = $this->riderRegistrationService->createRider($validated, $verifiedImmediately);

            // You might want to send a notification to the rider here
            // if ($verifiedImmediately) { ... send verified notification ... }
            // else { ... send pending notification ... }

            Cache::flush(); // Clear relevant caches related to riders/pending approvals

            return $this->successResponse(
                new UserResource($rider->load(['userProfile', 'branch'])),
                'Rider registered successfully by admin.',
                Response::HTTP_CREATED
            );
        } catch (\Throwable $e) {
            Log::error('Admin rider registration failed:', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return $this->errorResponse('Rider registration failed due to an internal error.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

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
        $pendingRiders = Cache::remember(
            'pending_riders_branch_' . $branchId . '_page_' . ($request->input('page', 1)),
            300, // Cache for 5 minutes
            function () use ($branchId, $perPage) {
                return User::with(['userProfile', 'branch'])
                    ->where('role', RoleEnum::Rider)
                    ->where('branch_id', $branchId)
                    ->where('verification_status', ProfileVerificationStatusEnum::PENDING)
                    ->orderBy('created_at', 'desc')
                    ->paginate($perPage);
            }
        );
        
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
            'status' => ['required', 'string', 'in:pending,verified,rejected'],
            'rejection_reason' => ['sometimes', 'required_if:status,rejected', 'string', 'max:500'],
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
                // Clear rejection details if previously rejected
                $rider->rejection_reason = null;
                $rider->rejected_by = null;
                $rider->rejected_at = null;
            } elseif ($validated['status'] === ProfileVerificationStatusEnum::REJECTED->value) {
                // If rejecting, capture reason and details
                $rider->rejection_reason = $validated['rejection_reason'] ?? null;
                $rider->rejected_by = $request->user()->fullname;
                $rider->rejected_at = now();
                // Clear email_verified_at if rejected
                $rider->email_verified_at = null;
            } else {
                // If setting to pending or any other status, clear verification details
                $rider->email_verified_at = null;
                $rider->rejection_reason = null;
                $rider->rejected_by = null;
                $rider->rejected_at = null;
            }
            
            $rider->save();
            
            // Clear cache for pending approvals as a rider's status has changed
            Cache::forget('pending_riders_branch_' . $rider->branch_id . '_page_1'); // Clear first page of cache
            // You might want to clear all pages of this cache key if pagination is widely used.
            // For simplicity, I am only clearing the first page, assuming it's the most frequently accessed.
            
            // Send verification notification
            try {
                $notificationService = app()->make(\App\Services\NotificationService::class);
                $notificationService->sendVerificationNotification($rider, $validated['status']);
            } catch (\Exception $e) {
                Log::warning('Failed to send verification notification: ' . $e->getMessage());
                // Continue execution even if notification fails
            }
            
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