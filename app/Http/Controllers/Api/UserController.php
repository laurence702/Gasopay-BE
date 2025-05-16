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

class UserController extends Controller
{
    use ApiResponseTrait;

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
            'role' => $request->role,
            'branch_id' => $request->branch_id,
        ]);

        Cache::flush();

        return $this->successResponse(
            new UserResource($user->load(['branch', 'userProfile'])),
            'User created successfully.',
            201
        );
    }

    public function register_rider(RegisterRiderRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['password'] = Hash::make($validated['password']);
        $validated['role'] = RoleEnum::Rider;
        $validated['ip_address'] = $request->getClientIp();
        try {
            $user = DB::transaction(function () use ($validated) {
                $user = User::create($validated);

                $user->userProfile()->create([
                    'phone' => $validated['phone'],
                    'address' => $validated['address'],
                    'nin' => $validated['nin'],
                    'guarantors_name' => $validated['guarantors_name'],
                    'guarantors_address' => $validated['guarantors_address'],
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
                // Continue execution even if SMS fails
            }

            Cache::flush();

            return $this->successResponse(
                new UserResource($user->load(['branch', 'userProfile'])),
                'Rider registered successfully.',
                201
            );

        } catch (\Throwable $e) {
            Log::error('Rider registration failed:', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return $this->errorResponse('Rider registration failed due to an internal error.', 500);
        }
    }

    public function createAdmin(CreateAdminRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['password'] = Hash::make($validated['password']);
            $validated['role'] = RoleEnum::Admin;
            $validated['verrification_status'] = ProfileVerificationStatusEnum::VERIFIED;
            $user = User::create($validated);
            Cache::flush();

            return $this->successResponse(
                new UserResource($user),
                'Admin created successfully.',
                201
            );
        } catch (\Exception $e) {
            Log::error('Admin creation failed:', ['error' => $e->getMessage()]);
            return $this->errorResponse('Admin creation failed.', 500);
        }
    }


    public function show(User $user): JsonResponse
    {
        $cacheKey = "user:{$user->id}";

        $data = Cache::remember($cacheKey, 300, function () use ($user) {
            return new UserResource($user->load(['branch', 'userProfile']));
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
        $user->update($request->validated());

        Cache::flush();

        return $this->successResponse(
            new UserResource($user->load(['branch', 'userProfile'])),
            'User updated successfully.'
        );
    }

    /**
     * Ban a specific user.
     *
     * @param Request $request
     * @param User $user The user to ban (via route model binding)
     * @return JsonResponse
     */
    public function ban(Request $request, User $user): JsonResponse
    {
        if ($request->user()->id === $user->id) {
            return $this->errorResponse('You cannot ban yourself.', 400);
        }

        if ($user->role === RoleEnum::SuperAdmin) {
            return $this->errorResponse('Cannot ban a Super Admin.', 403); 
        }

        // Check if already banned
        if ($user->banned_at !== null) {
            return $this->errorResponse('User is already banned.', 400);
        }

        $user->banned_at = now();
        $user->save();

        //Log out the user
        $user->tokens()->delete(); 

        $this->flushUserCache($user->id);

        return $this->successResponse(new UserResource($user->refresh()), 'User banned successfully.');
    }

    public function destroy(User $user): JsonResponse
    {
        if ($user->trashed()) {
            return $this->errorResponse('User already deleted.', 404);
        }

        $user->delete();
        Cache::flush();

        return $this->successResponse(null, 'User deleted successfully', 200);
    }

    public function restore($id): JsonResponse
    {
        $user = User::withTrashed()->findOrFail($id);

        if (!$user->trashed()) {
            return $this->errorResponse('User is not deleted.', 400);
        }

        $user->restore();
        Cache::flush();

        return $this->successResponse(null, 'User restored successfully');
    }

    public function forceDelete($id): JsonResponse
    {
        $user = User::withTrashed()->findOrFail($id);

        if (!$user->trashed()) {
            return $this->errorResponse('User must be soft-deleted first.', 400);
        }

        $user->forceDelete();
        Cache::flush();

        return $this->successResponse(null, 'User permanently deleted');
    }

    /**
     * Update the verification status of a Rider.
     *
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     */
    public function updateVerificationStatus(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', new EnumRule(ProfileVerificationStatusEnum::class)],
            'rider_id' => ['required', 'exists:users,id']
        ]);
        $user = User::find($validated['rider_id']);
        if ($user->role !== RoleEnum::Rider) {
            return $this->errorResponse('User is not a rider.', 400);
        }

        $newStatus = ProfileVerificationStatusEnum::from($validated['status']);

        if ($newStatus === ProfileVerificationStatusEnum::VERIFIED && $user->userProfile === null) {
            return $this->errorResponse('Cannot verify rider without a profile.', 400);
        }

        try {
            if($newStatus === ProfileVerificationStatusEnum::REJECTED){
                $user->verification_status = $newStatus;
                $user->save();
                //send sms to the rider
                try {
                    $smsService = app()->make(\App\Services\AfricasTalkingService::class);
                    $smsService->send(
                        $user->phone, 
                        "Your profile has been rejected. Please update your profile and try again."
                    );
                } catch (\Exception $e) {
                    Log::warning('Failed to send rejection SMS: ' . $e->getMessage());
                    // Continue execution even if SMS fails
                }
            }else{
                $user->verification_status = $newStatus;
                $user->verified_by = $request->user()->fullname;
                $user->email_verified_at = now();
                $user->save();
                //send sms to the rider
                try {
                    $smsService = app()->make(\App\Services\AfricasTalkingService::class);
                    $smsService->send(
                        $user->phone, 
                        "Your profile has been verified. You can now login to the app."
                    );
                } catch (\Exception $e) {
                    Log::warning('Failed to send verification SMS: ' . $e->getMessage());
                    // Continue execution even if SMS fails
                }
            }

            $this->flushUserCache($user->id);

            // Only flush tags if the cache driver supports it
            if (method_exists(Cache::getStore(), 'tags')) {
                Cache::tags(['users_list'])->flush();
            }

            $user->refresh()->load('branch', 'userProfile');

            return $this->successResponse(
                new UserResource($user),
                'Rider status updated to ' . $newStatus->value . '.',
                200
            );
        } catch (Exception $e) {
            Log::error("Error updating verification status for rider {$user->id}: " . $e->getMessage());
            return $this->errorResponse('Failed to update rider verification status.', 500);
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
            return $this->errorResponse('Failed to fetch users.', 500);
        }
    }
}
