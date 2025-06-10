<?php

namespace App\Http\Controllers\Api;

use Exception;
use App\Models\User;
use App\Enums\RoleEnum;
use App\Enums\ProfileVerificationStatusEnum;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rules\Enum as EnumRule;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\CreateAdminRequest;
use App\Http\Requests\RegisterRiderRequest;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Http\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\SuperAdminUpdateUserRequest;
use App\Http\Requests\BranchAdminUpdateRiderRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Response;

class UserController extends Controller
{
    use ApiResponseTrait, AuthorizesRequests;

    public function __construct()
    {
    }

    public function index(): AnonymousResourceCollection
    {
        $cacheKey = 'users:' . md5(request()->fullUrl());

        return Cache::remember($cacheKey, 300, function () {
            $users = User::with(['branch', 'userProfile'])
                ->withCount('orders')
                ->withSum('orders as orders_sum_amount_due', 'amount_due')
                ->latest()
                ->paginate(30);

            return UserResource::collection($users);
        });
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = User::create([
            'fullname' => $request->fullname,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => RoleEnum::Regular,
            'branch_id' => $request->branch_id,
        ]);

        Cache::flush();

        return $this->successResponse(
            new UserResource($user->load(['branch', 'userProfile'])),
            'User created successfully.',
            Response::HTTP_CREATED
        );
    }

    public function register_rider(RegisterRiderRequest $request)
    {
        Log::info('User info', $request->all());
        $validated = $request->validated();
        $validated['password'] = Hash::make($validated['password']);
        $validated['ip_address'] = $request->getClientIp();
        $validated['role'] = RoleEnum::Rider;
        $validated['branch_id'] = ($request->user()->role == RoleEnum::Admin) ? $request->user()->branch_id : $request->branch_id;
        try {
            $user = DB::transaction(function () use ($validated, $request) {
                $user = User::create($validated);

                $user->userProfile()->create([
                    'phone' => $validated['phone'],
                    'address' => $validated['address'],
                    'nin' => $validated['nin'],
                    'guarantors_name' => $validated['guarantors_name'],
                    'guarantors_address' => $validated['guarantors_address'],
                    'branch_id' => $validated['branch_id'],
                    'guarantors_phone' => $validated['guarantors_phone'],
                    'vehicle_type' => $validated['vehicle_type'],
                    'profile_pic_url' => $validated['profilePicUrl'],
                    'ip_address' => $validated['ip_address']
                ]);

                return $user;
            });

            // Send welcome SMS to the rider
            try {
                $smsService = app()->make(\App\Services\AfricasTalkingService::class);
                $smsService->send(
                    $user->phone,
                    "Welcome to Gasopay! Your rider account has been created. Your verification status is pending. You will be notified once your account is verified."
                );
            } catch (\Exception $e) {
                Log::warning('Failed to send welcome SMS: ' . $e->getMessage());
            }

            Cache::flush();

            return $this->successResponse(
                new UserResource($user->load(['branch', 'userProfile'])),
                'Rider registered successfully.',
                Response::HTTP_CREATED
            );

        } catch (\Throwable $e) {
            Log::error('Rider registration failed:', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return $this->errorResponse('Rider registration failed due to an internal error.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function createAdmin(CreateAdminRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['password'] = Hash::make($validated['password']);
            $validated['role'] = RoleEnum::Admin;
            $validated['verrification_status'] = ProfileVerificationStatusEnum::VERIFIED;
            $validated['verified_by'] = $request->user()->fullname;
            $user = User::create($validated);
            Cache::flush();

            return $this->successResponse(
                new UserResource($user),
                'Admin created successfully.',
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            Log::error('Admin creation failed:', ['error' => $e->getMessage()]);
            return $this->errorResponse('Admin creation failed.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function show(User $user): JsonResponse
    {
        $cacheKey = "user:{$user->id}";

        $data = Cache::remember($cacheKey, 300, function () use ($user) {
            return new UserResource($user->load(['branch', 'userProfile'])->loadCount('orders'));
        });

        return $this->successResponse($data, 'User retrieved successfully.');
    }

    public function allUsers(): AnonymousResourceCollection
    {
        $cacheKey = 'all_users';

        return Cache::remember($cacheKey, 300, function () {
            $users = User::with(['branch', 'userProfile'])
                ->latest()
                ->get();

            return UserResource::collection($users);
        });
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $validatedData = $request->validated();

        // Check if password is provided in the request
        if (isset($validatedData['password'])) {
            // Hash the new password and update it in the validated data
            $validatedData['password'] = Hash::make($validatedData['password']);
            // Remove password_confirmation as it's only for validation
            unset($validatedData['password_confirmation']);
        }

        $user->update($validatedData);

        Cache::flush();

        return $this->successResponse(
            new UserResource($user->load(['branch', 'userProfile'])),
            'User updated successfully.'
        );
    }

    /**
     * Ban a specific user.
     *
     * @param  \App\Http\Requests\BanUserRequest  $request
     * @param  \App\Models\User  $user The user to ban (via route model binding)
     * @return \Illuminate\Http\JsonResponse
     */
    public function ban(Request $request, User $user): JsonResponse
    {
        try {
            Log::info('Ban request details:', [
                'request' => $request->all(),
                'target_user_id' => $user->id,
                'target_user_role' => $user->role,
                'acting_user_id' => $request->user()->id,
                'acting_user_role' => $request->user()->role,
                'is_banned' => $user->banned_at ? true : false
            ]);

            $actingUserRole = $request->user()->role;
            $targetUserRole = $user->role;

            // Only Admins and SuperAdmins can ban users
            if (!in_array($actingUserRole, [RoleEnum::Admin, RoleEnum::SuperAdmin])) {
                Log::info('Unauthorized user attempted to ban', ['user_role' => $actingUserRole]);
                return $this->errorResponse('You are not authorized to ban users.', Response::HTTP_FORBIDDEN);
            }
            // Cannot ban SuperAdmin
            if ($targetUserRole === RoleEnum::SuperAdmin) {
                Log::info('Attempted to ban SuperAdmin', ['user_id' => $user->id]);
                return $this->errorResponse('Cannot ban a Super Admin.', Response::HTTP_FORBIDDEN);
            }
            // Cannot ban self
            if ($request->user()->id === $user->id) {
                Log::info('User attempted to ban themselves', ['user_id' => $user->id]);
                return $this->errorResponse('You cannot ban yourself.', Response::HTTP_FORBIDDEN);
            }
            // Cannot ban already banned user
            if ($user->banned_at) {
                Log::info('Attempted to ban already banned user', ['user_id' => $user->id]);
                return $this->errorResponse('User is already banned.', Response::HTTP_FORBIDDEN);
            }

            $request->validate([
                'ban_reason' => 'nullable|string|max:255',
            ]);

            $user->banned_at = now();
            $user->ban_reason = $request->input('ban_reason');
            $user->save();
            
            $this->flushUserCache($user->id);
            
            Log::info('User banned successfully', [
                'user_id' => $user->id,
                'banned_at' => $user->banned_at,
                'banned_by' => $request->user()->id
            ]);
            
            return $this->successResponse(new UserResource($user->refresh()), 'User banned successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error banning user: ' . $e->getMessage());
            return $this->errorResponse('Failed to ban user.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Unban a specific user.
     *
     * @param  \App\Models\User  $user The user to unban (via route model binding)
     * @return \Illuminate\Http\JsonResponse
     */
    public function unban(User $user): JsonResponse
    {
        // Only Super Admins can unban users
        if (!Gate::allows('ban', $user)) {
            return $this->errorResponse('You are not authorized to unban users.', Response::HTTP_FORBIDDEN);
        }

        // Check if the user is actually banned
        if ($user->banned_at === null) {
            return $this->errorResponse('User is not banned.', Response::HTTP_BAD_REQUEST);
        }

        $user->banned_at = null;
        $user->ban_reason = null;
        $user->save();

        $this->flushUserCache($user->id);

        return $this->successResponse(new UserResource($user->refresh()), 'User unbanned successfully.');
    }

    public function destroy(User $user): JsonResponse
    {
        if ($user->trashed()) {
            return $this->errorResponse('User already deleted.', Response::HTTP_NOT_FOUND);
        }

        $user->delete();
        Cache::flush();

        return $this->successResponse(null, 'User deleted successfully', Response::HTTP_OK);
    }

    public function restore(User $user): JsonResponse
    {
        if (!$user->trashed()) {
            return $this->errorResponse('User is not deleted.', Response::HTTP_BAD_REQUEST);
        }

        $user->restore();
        Cache::flush();
        return $this->successResponse(
            new UserResource($user->load(['branch', 'userProfile'])),
            'User restored successfully'
        );
    }

    public function forceDelete(User $user): JsonResponse
    {
        if (!$user->trashed()) {
            return $this->errorResponse('User must be soft deleted before permanent deletion.', Response::HTTP_BAD_REQUEST);
        }

        $user->forceDelete();
        Cache::flush();
        return $this->successResponse(null, 'User permanently deleted');
    }

    /**
     * Update the verification status of a Rider.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateVerificationStatus(Request $request): JsonResponse
    {
        try {
            Log::info('Request payload for updateVerificationStatus:', ['request' => $request->all()]);
            $validated = $request->validate([
                'rider_id' => 'required|exists:users,id',
                'status' => ['required', new EnumRule(ProfileVerificationStatusEnum::class)],
            ]);

            $rider = User::findOrFail($validated['rider_id']);
            
            Log::info('Rider details:', [
                'rider_id' => $rider->id,
                'rider_role' => $rider->role,
                'has_profile' => $rider->userProfile ? true : false,
                'acting_user_role' => $request->user()->role
            ]);

            // Check if the user is a rider first
            if ($rider->role !== RoleEnum::Rider) {
                Log::info('User is not a rider', ['user_role' => $rider->role]);
                return $this->errorResponse('User is not a rider.', Response::HTTP_BAD_REQUEST);
            }
            // Then check if the rider has a profile
            if (!$rider->userProfile) {
                Log::info('Rider has no profile', ['rider_id' => $rider->id]);
                return $this->errorResponse('Cannot verify rider without a profile.', Response::HTTP_BAD_REQUEST);
            }

            // Check if the authenticated user has the required role
            $actingUserRole = $request->user()->role;
            if (!in_array($actingUserRole, [RoleEnum::Admin, RoleEnum::SuperAdmin])) {
                Log::info('Unauthorized user attempted to update verification status', [
                    'user_role' => $actingUserRole
                ]);
                return $this->errorResponse('You are not authorized to update rider verification status.', Response::HTTP_FORBIDDEN);
            }

            $rider->verification_status = $validated['status'];
            $rider->verified_by = $request->user()->fullname;
            $rider->save();

            Cache::flush();

            $response = $this->successResponse(
                new UserResource($rider->load(['branch', 'userProfile'])),
                'Rider verification status updated successfully.'
            );

            Log::info('Response from updateVerificationStatus:', ['response' => $response->getContent()]);

            return $response;
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error updating rider verification status: ' . $e->getMessage());
            return $this->errorResponse('Failed to update rider verification status.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     *
     * @param string $userId
     * @return void
     */
    private function flushUserCache(string $userId): void
    {
        Cache::forget("user_{$userId}");
    }

    /**
     * Retrieve all users without pagination (for load testing).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function indexUnpaginated(): JsonResponse
    {
        try {
            $users = User::all();
            return $this->successResponse(UserResource::collection($users), 'All users retrieved successfully.');
        } catch (\Throwable $e) {
            Log::error("Error fetching all unpaginated users: " . $e->getMessage());
            return $this->errorResponse('Failed to fetch users.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update a user's information (Super Admin only).
     *
     * @param  \App\Http\Requests\SuperAdminUpdateUserRequest  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function superAdminUpdate(SuperAdminUpdateUserRequest $request, User $user): JsonResponse
    {
        $validatedData = $request->validated();

        // Check if password is provided in the request
        if (isset($validatedData['password'])) {
            // Hash the new password and update it in the validated data
            $validatedData['password'] = Hash::make($validatedData['password']);
            // Remove password_confirmation as it's only for validation
            unset($validatedData['password_confirmation']);
        }

        $user->update($validatedData);

        Cache::flush();

        return $this->successResponse(
            new UserResource($user->load(['branch', 'userProfile'])),
            'User updated successfully by Super Admin.'
        );
    }

    /**
     * Update a rider's information (Branch Admin only).
     *
     * @param  \App\Http\Requests\BranchAdminUpdateRiderRequest  $request
     * @param  \App\Models\User  $user The rider user to update
     * @return \Illuminate\Http\JsonResponse
     */
    public function branchAdminUpdateRider(BranchAdminUpdateRiderRequest $request, User $user): JsonResponse
    {
        $validatedData = $request->validated();

        try {
            DB::transaction(function () use ($validatedData, $user) {
                // Update User model fields
                $user->update(
                    collect($validatedData)->only(['fullname', 'phone'])->toArray()
                );

                // Update UserProfile model fields
                if ($user->userProfile) {
                    $user->userProfile->update(
                        collect($validatedData)->only(['address', 'nin', 'vehicle_type'])->toArray()
                    );
                } else {
                    // This case should ideally not happen for a Rider, but handle defensively
                    Log::warning('Branch Admin attempting to update Rider without UserProfile.', ['user_id' => $user->id]);
                }
            });

            Cache::flush(); // Flush cache for updated user and potentially lists

            return $this->successResponse(
                new UserResource($user->load(['branch', 'userProfile'])),
                'Rider updated successfully by Branch Admin.'
            );

        } catch (\Throwable $e) {
            Log::error('Branch Admin Rider update failed:', ['user_id' => $user->id, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return $this->errorResponse('Failed to update rider information.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
