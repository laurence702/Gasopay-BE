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
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request): UserCollection
    {
        $users = User::query()
            ->when($request->search, function ($query, $search) {
                $query->where('fullname', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('role', 'like', "%{$search}%");
            })
            ->paginate();

        return new UserCollection($users);
    }

    public function store(StoreUserRequest $request): UserResource
    {
        $validated = $request->validated();
        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        return new UserResource($user);
    }

    public function register_rider(StoreUserRequest $request): UserResource
    {
        $validated = $request->validated();
        $validated['password'] = Hash::make($validated['password']);
        $validated['role'] = RoleEnum::Rider;

        $user = User::create($validated);

        return new UserResource($user);
    }

    public function createAdmin(CreateAdminRequest $request): UserResource
    {
        $validated = $request->validated();
        $validated['password'] = Hash::make($validated['password']);
        $validated['role'] = RoleEnum::Admin;

        $user = User::create($validated);

        return new UserResource($user);
    }
    

    public function show(User $user): UserResource
    {
        return new UserResource($user->load(['branch', 'userProfile']));
    }

    public function allUsers(Request $request)
    {
        $users = User::query()->Where('role', '=', $request?->query('role'))
            ->paginate();

        return new UserCollection($users);
    }

    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $validated = $request->validated();

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return new UserResource($user);
    }

    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully'
        ]);
    }
}
