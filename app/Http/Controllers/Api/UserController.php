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
                ->latest()
                ->paginate(10);

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

        return response()->json([
            'data' => new UserResource($user->load(['branch', 'userProfile']))
        ], 201);
    }

    public function register_rider(RegisterRiderRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['password'] = Hash::make($validated['password']);
        $validated['role'] = RoleEnum::Rider;

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
                ]);

                return $user;
            });

            Cache::flush();

            return response()->json([
                'data' => new UserResource($user->load(['branch', 'userProfile']))
            ], 201);

        } catch (\Throwable $e) {
            Log::error('Rider registration failed:', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Rider registration failed due to an internal error.'], 500);
        }
    }

    public function createAdmin(CreateAdminRequest $request): UserResource|JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['password'] = Hash::make($validated['password']);
            $validated['role'] = RoleEnum::Admin;
            $user = User::create($validated);
            Cache::flush();

            return new UserResource($user->load(['branch', 'userProfile']));
        } catch (\Exception $e) {
            Log::error('Admin creation failed:', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Admin creation failed'], 500);
        }
    }


    public function show(User $user): JsonResponse
    {
        $cacheKey = "user:{$user->id}";

        $data = Cache::remember($cacheKey, 300, function () use ($user) {
            return new UserResource($user->load(['branch', 'userProfile']));
        });

        return response()->json(['data' => $data]);
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

        return response()->json([
            'data' => new UserResource($user->load(['branch', 'userProfile']))
        ]);
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

    public function destroy(User $user)
    {
        if ($user->trashed()) {
            return response()->json(['message' => 'User already deleted'], 404);
        }

        $user->delete();
        // Cache::tags(['users'])->flush(); // Remove or comment out if using file cache
        Cache::flush(); // Use broad flush or specific forget if needed

        return response()->json(['message' => 'User deleted successfully']);
    }

    public function restore($id)
    {
        $user = User::withTrashed()->findOrFail($id);

        if (!$user->trashed()) { // Check if user is actually trashed
            return response()->json(['message' => 'User is not deleted'], 400);
        }

        $user->restore();

        return response()->json(['message' => 'User restored successfully']);
    }

    public function forceDelete($id)
    {
        $user = User::withTrashed()->findOrFail($id);

        if (!$user->trashed()) { // Check if user is actually trashed
            return response()->json(['message' => 'User must be soft-deleted first'], 400);
        }

        $user->forceDelete();

        return response()->json(['message' => 'User permanently deleted']);
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
            $user->verification_status = $newStatus;
            $user->verified_by = $request->user()->fullname;
            $user->email_verified_at = now();
            $user->save();

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
            return response()->json(UserResource::collection($users));
        } catch (\Throwable $e) {
            Log::error("Error fetching all unpaginated users: " . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to fetch users.'], 500);
        }
    }
}
