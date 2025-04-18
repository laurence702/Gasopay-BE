<?php

namespace App\Http\Controllers\Api;

use Exception;
use App\Models\User;
use App\Enums\RoleEnum;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\CreateAdminRequest;
use App\Http\Requests\RegisterRiderRequest;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
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
        try {
            $validated = $request->validated();
            $validated['password'] = Hash::make($validated['password']);
            $validated['role'] = RoleEnum::Rider;

            $user = User::create($validated);

            // Create user profile
            $user->userProfile()->create([
                'phone' => $validated['phone'],
                'address' => $validated['address'],
                'nin' => $validated['nin'],
                'guarantors_name' => $validated['guarantors_name'],
                'vehicle_type' => $validated['vehicle_type'],
            ]);

            Cache::flush();

            return response()->json([
                'data' => new UserResource($user->load(['branch', 'userProfile']))
            ], 201);
        } catch (\Exception $e) {
            Log::error('Rider registration failed:', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Rider registration failed'], 500);
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

    public function destroy(User $user)
    {
        if ($user->trashed()) {
            return response()->json(['message' => 'User already deleted'], 404);
        }

        $user->delete();
        Cache::tags(['users'])->flush();

        return response()->json(['message' => 'User deleted successfully']);
    }

    public function restore($id)
    {
        $user = User::withTrashed()->findOrFail($id);

        if (!$user->trashed()) {
            return response()->json(['message' => 'User is not deleted'], 404);
        }

        $user->restore();
        Cache::tags(['users'])->flush();

        return response()->json(['message' => 'User restored successfully']);
    }

    public function forceDelete($id)
    {
        $user = User::withTrashed()->findOrFail($id);

        if (!$user->trashed()) {
            return response()->json(['message' => 'User must be deleted first'], 400);
        }

        $user->forceDelete();
        Cache::tags(['users'])->flush();

        return response()->json(['message' => 'User permanently deleted']);
    }

    /**
     *
     * @param User $rider
     * @return JsonResponse
     */
    public function rider_verification(User $rider): JsonResponse
    {
        if ($rider->role !== RoleEnum::Rider) {
            return $this->errorResponse('User is not a rider.', 400);
        }

        try {
            if($rider->userProfile !== null) {
                $rider->profile_verified = 1;
                $rider->save();

            $this->flushUserCache($rider->id);
            Cache::tags(['users_list'])->flush();

            $rider->refresh();

            return $this->successResponse(new UserResource($rider), 'Verification Successful.', 200);
            }
        } catch (Exception $e) {
            Log::error("Error verifying rider {$rider->id}: " . $e->getMessage());
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
