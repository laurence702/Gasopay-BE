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

class AuthController extends Controller
{
    public function register(RegisterUserRequest $request)
    {
        try {
            $validatedData = $request->validated();
            $plainPassword = $validatedData['password'];
            $validatedData['password'] = Hash::make($validatedData['password']);

            // Create user
            $user = User::create([
                'fullname' => $validatedData['fullname'],
                'email' => $validatedData['email'],
                'phone' => $validatedData['phone'],
                'password' => $validatedData['password'],
                'role' => $validatedData['role'],
                'branch_id' => $validatedData['branch_id'] ?? null,
            ]);

            // Create user profile for regular users and riders
            if (in_array($user->role->value, ['regular', 'rider'])) {
                UserProfile::create([
                    'user_id' => $user->id,
                    'phone' => $validatedData['phone'],
                    'address' => $validatedData['address'],
                    'vehicle_type_id' => $validatedData['vehicle_type_id'] ?? null,
                    'nin' => $validatedData['nin'],
                    'guarantors_name' => $validatedData['guarantors_name'],
                    'photo' => $validatedData['photo'] ?? null,
                    'barcode' => $validatedData['barcode'] ?? null,
                ]);
            }

            // Generate token
            $token = $user->createToken('auth_token')->plainTextToken;

            // Send welcome email
            Mail::to($user->email)->send(new WelcomeEmail($user, $plainPassword));

            return response()->json([
                'message' => 'User registered successfully',
                'user' => $user->load('userProfile'),
                'token' => $token
            ], 201);
        } catch (\Exception $e) {
            Log::error('Registration error:', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        $token = $user->createToken('token')->plainTextToken;

        return response()->json([
            'message' => 'Logged in successfully',
            'user' => $user->load('userProfile'),
            'token' => $token
        ]);
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
