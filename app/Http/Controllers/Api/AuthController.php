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
use App\Http\Requests\RegisterRiderRequest;
use App\Services\RiderRegistrationService;
use Illuminate\Http\Response;
use App\Http\Traits\ApiResponseTrait;
use App\Enums\ProfileVerificationStatusEnum;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    use ApiResponseTrait;

    protected $riderRegistrationService;

    public function __construct(RiderRegistrationService $riderRegistrationService)
    {
        $this->riderRegistrationService = $riderRegistrationService;
    }

    /**
     * Register a new general user (not a rider).
     */
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
                'branch_id' => $validated['branch_id'] ?? null,
            ]);

            // Create user profile for regular users
            if ($validated['role'] === RoleEnum::Regular->value) {
                $user->userProfile()->create([
                    'address' => $validated['address'] ?? null,
                    'profile_pic_url' => $validated['profilePicUrl'] ?? null,
                ]);
            }

            // Send welcome email
            // Note: Password is included here. Consider if this is secure for production.
            Mail::to($user->email)->send(new WelcomeEmail($user, $validated['password']));

            DB::commit();

            $user->load('userProfile');
            return response()->json([
                'status' => 'success',
                'message' => 'User created successfully',
                'user' => new UserResource($user),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create user: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create user: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Register a new rider (self-registration).
     */
    public function registerRider(RegisterRiderRequest $request): JsonResponse
    {
        Log::info('Rider self-registration attempt', $request->all());
        try {
            $validated = $request->validated();
            
            // Default role to Rider and set verification status to pending
            $validated['role'] = RoleEnum::Rider->value;
            $validated['verification_status'] = ProfileVerificationStatusEnum::PENDING->value;

            $rider = $this->riderRegistrationService->createRider($validated, false);

            // Send welcome SMS to the rider
            try {
                $smsService = app()->make(\App\Services\AfricasTalkingService::class);
                $smsService->send(
                    $rider->phone,
                    "Welcome to Gasopay! Your rider account has been created. Your verification status is pending. You will be notified once your account is verified."
                );
            } catch (\Exception $e) {
                Log::warning('Failed to send welcome SMS for rider self-registration: ' . $e->getMessage());
            }

            return $this->successResponse(
                new UserResource($rider->load(['branch', 'userProfile'])),
                'Rider registered successfully. Awaiting verification.',
                Response::HTTP_CREATED
            );
        } catch (\Throwable $e) {
            Log::error('Rider self-registration failed:', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return $this->errorResponse('Rider self-registration failed due to an internal error.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();
        $loginIdentifier = $credentials['login_identifier'];
        $password = $credentials['password'];

        $loginField = filter_var($loginIdentifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        $authCredentials = [
            $loginField => $loginIdentifier,
            'password' => $password
        ];

        if (!Auth::attempt($authCredentials)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid credentials',
            ], 401);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Check if the user is banned BEFORE issuing a token
        if ($user->banned_at !== null) {
            Auth::logout(); // Log the user out as Auth::attempt succeeded
            return response()->json([
                'status' => 'error',
                'message' => 'Your account has been suspended.', // User-friendly message
            ], 403); // Use 403 Forbidden
        }

        $user->load('userProfile');
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'user' => new UserResource($user),
            'token' => $token,
        ], 200);
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
