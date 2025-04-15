<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\PaymentProofController;
use App\Http\Controllers\PaymentHistoryController;
use App\Http\Controllers\Api\UserProfileController;
use App\Http\Controllers\Api\OrderController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
| These routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
*/

// Auth routes
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum')->name('logout');
Route::get('/me', [AuthController::class, 'loggedInUser'])->middleware('auth:sanctum')->name('user');

// Protected routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::post('/register-rider', [UserController::class, 'register_rider'])->name('users.register_rider');
    Route::post('/users/{id}/restore', [UserController::class, 'restore']);
    Route::delete('/users/{id}/force', [UserController::class, 'forceDelete']);

    // Product routes
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');

    // User Profile routes
    Route::get('/user-profiles', [UserProfileController::class, 'index'])->name('user-profiles.index');
    Route::post('/user-profiles', [UserProfileController::class, 'store'])->name('user-profiles.store');
    Route::get('/user-profiles/{userProfile}', [UserProfileController::class, 'show'])->name('user-profiles.show');
    Route::put('/user-profiles/{userProfile}', [UserProfileController::class, 'update'])->name('user-profiles.update');
    Route::delete('/user-profiles/{userProfile}', [UserProfileController::class, 'destroy'])->name('user-profiles.destroy');

    // Branch routes - accessible to all authenticated users
    Route::get('/branches', [BranchController::class, 'index'])->name('branches.index');
    Route::get('/branches/{branch}', [BranchController::class, 'show'])
        ->missing(function () {
            return response()->json(['message' => 'Branch not found.'], 404);
        });

    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
});

// Super Admin routes
Route::middleware(['auth:sanctum', \App\Http\Middleware\SuperAdmin::class])->group(function () {
    Route::post('/create-admin', [UserController::class, 'createAdmin'])->name('users.createAdmin');
    // Branch management routes
    Route::post('/branches', [BranchController::class, 'store'])->name('branches.store');
    Route::put('/branches/{branch}', [BranchController::class, 'update'])
        ->missing(function () {
            return response()->json(['message' => 'Branch not found.'], 404);
        });
    Route::delete('/branches/{branch}', [BranchController::class, 'destroy'])
        ->missing(function () {
            return response()->json(['message' => 'Branch not found.'], 404);
        });

    // Product management routes
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

    //Payment routes
    Route::apiResource('payment-histories', PaymentHistoryController::class);
    Route::post('payment-histories/{paymentHistory}/mark-cash', [PaymentHistoryController::class, 'markCashPayment']);

    Route::post('payment-histories/{paymentHistory}/proof', [PaymentProofController::class, 'submit']);
    Route::post('payment-proofs/{paymentProof}/approve', [PaymentProofController::class, 'approve']);
    Route::post('payment-proofs/{paymentProof}/reject', [PaymentProofController::class, 'reject']);

    // Order management routes
    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
    Route::put('/orders/{order}', [OrderController::class, 'update'])->name('orders.update');
    Route::delete('/orders/{order}', [OrderController::class, 'destroy'])->name('orders.destroy');
});
