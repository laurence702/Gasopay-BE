<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterUserRequest;
use App\Mail\WelcomeEmail;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use App\Enums\RoleEnum;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\UserResource;
use App\Http\Requests\LoginRequest;

class AuthController extends Controller
{
    public function register(RegisterUserRequest $request)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();
            $user = User::create([
                'fullname' => $validated['fullname'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
            ]);

            // Create user profile for regular users and riders
            if (in_array($validated['role'], [RoleEnum::Regular->value, RoleEnum::Rider->value])) {
                $user->userProfile()->create([
                    'address' => $validated['address'] ?? null,
                    'nin' => $validated['nin'] ?? null,
                    'guarantors_name' => $validated['guarantors_name'] ?? null,
                    'vehicle_type' => $validated['vehicle_type'] ?? null,
                ]);
            }

            // Send welcome email
            Mail::to($user->email)->send(new WelcomeEmail($user, $validated['password']));

            DB::commit();

            $user->load('userProfile');
            return response()->json([
                'status' => 'success',
                'message' => 'User created successfully',
                'user' => new UserResource($user),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create user: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();
        $loginField = filter_var($credentials['email'], FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        
        if (!Auth::attempt([
            $loginField => $credentials['email'],
            'password' => $credentials['password']
        ])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid credentials',
            ], 401);
        }

        $user = Auth::user();
        $user->load('userProfile');
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'user' => new UserResource($user),
            'token' => $token,
        ],200);
    }

    public function loggedInUser(Request $request)
    {
        return response()->json([
            'user' => $request->user()->load('userProfile')
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    public function user(Request $request)
    {
        return response()->json([
            'user' => $request->user()
        ]);
    }
}
