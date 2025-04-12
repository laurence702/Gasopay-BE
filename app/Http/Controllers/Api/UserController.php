<?php

namespace App\Http\Controllers\Api;

use App\Enums\RoleEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateAdminRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function index(Request $request): UserCollection
    {
        $cacheKey = 'users:index:' . md5(json_encode($request->all()));
        
        $users = cache()->remember($cacheKey, 300, function () use ($request) {
            return User::query()
                ->with(['branch', 'userProfile'])
                ->when($request->search, function ($query, $search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('fullname', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
                })
                ->paginate(10);
        });

        return new UserCollection($users);
    }

    public function store(StoreUserRequest $request): UserResource|JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['password'] = Hash::make($validated['password']);

            $user = User::create($validated);
            cache()->tags(['users'])->flush();

            return new UserResource($user->load(['branch', 'userProfile']));
        } catch (\Exception $e) {
            Log::error('User creation failed:', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'User creation failed'], 500);
        }
    }

    public function register_rider(StoreUserRequest $request): UserResource|JsonResponse
    {
        try {
            $validated = $request->validated();
            unset($validated['role']); //protection against role manipulation
            $validated['password'] = Hash::make($validated['password']);
            $validated['role'] = RoleEnum::Rider;

            $user = User::create($validated);
            cache()->tags(['users'])->flush();

            return new UserResource($user->load(['branch', 'userProfile']));
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
            cache()->tags(['users'])->flush();

            return new UserResource($user->load(['branch', 'userProfile']));
        } catch (\Exception $e) {
            Log::error('Admin creation failed:', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Admin creation failed'], 500);
        }
    }
    

    public function show(User $user): UserResource
    {
        $user = User::findCached($user->id);
        return new UserResource($user->load(['branch', 'userProfile']));
    }

    public function allUsers(Request $request)
    {
        $cacheKey = 'users:all:' . $request->query('role');
        
        $users = cache()->remember($cacheKey, 300, function () use ($request) {
            return User::query()
                ->with(['branch', 'userProfile'])
                ->where('role', '=', $request?->query('role'))
                ->paginate();
        });

        return new UserCollection($users);
    }

    public function update(UpdateUserRequest $request, User $user): UserResource|JsonResponse
    {
        try {
            $validated = $request->validated();

            if (isset($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            }

            $user->update($validated);
            cache()->tags(['users'])->flush();

            return new UserResource($user->load(['branch', 'userProfile']));
        } catch (\Exception $e) {
            Log::error('User update failed:', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'User update failed'], 500);
        }
    }

    public function destroy(User $user): JsonResponse
    {
        try {
            $user->delete();
            cache()->tags(['users'])->flush();
            
            return response()->json([
                'message' => 'User deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('User deletion failed:', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'User deletion failed'], 500);
        }
    }
}
