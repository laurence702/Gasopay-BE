<?php

use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\UserProfileController;
use App\Http\Controllers\Api\VehicleTypeController;
use App\Http\Controllers\Api\RiderController;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

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
Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum')->name('user');

// Protected routes
Route::middleware(['auth:sanctum'])->group(function () {
    // User routes
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    // Product routes
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

    // Branch routes
    Route::get('/branches', [BranchController::class, 'index'])->name('branches.index');
    Route::post('/branches', [BranchController::class, 'store'])->name('branches.store');
    Route::get('/branches/{branch}', [BranchController::class, 'show'])->name('branches.show');
    Route::put('/branches/{branch}', [BranchController::class, 'update'])->name('branches.update');
    Route::delete('/branches/{branch}', [BranchController::class, 'destroy'])->name('branches.destroy');

    // User Profile routes
    Route::get('/user-profiles', [UserProfileController::class, 'index'])->name('user-profiles.index');
    Route::post('/user-profiles', [UserProfileController::class, 'store'])->name('user-profiles.store');
    Route::get('/user-profiles/{userProfile}', [UserProfileController::class, 'show'])->name('user-profiles.show');
    Route::put('/user-profiles/{userProfile}', [UserProfileController::class, 'update'])->name('user-profiles.update');
    Route::delete('/user-profiles/{userProfile}', [UserProfileController::class, 'destroy'])->name('user-profiles.destroy');

    // Vehicle Type routes
    Route::get('/vehicle-types', [VehicleTypeController::class, 'index'])->name('vehicle-types.index');
    Route::post('/vehicle-types', [VehicleTypeController::class, 'store'])->name('vehicle-types.store');
    Route::get('/vehicle-types/{vehicleType}', [VehicleTypeController::class, 'show'])->name('vehicle-types.show');
    Route::put('/vehicle-types/{vehicleType}', [VehicleTypeController::class, 'update'])->name('vehicle-types.update');
    Route::delete('/vehicle-types/{vehicleType}', [VehicleTypeController::class, 'destroy'])->name('vehicle-types.destroy');

    // Admin routes
    Route::middleware(['role:admin'])->prefix('admin')->group(function () {
        //create account for Rider function
        //Add rider payment
        Route::post('/rider', [RiderController::class, 'store'])->name('admin.rider.store');
    });
    
    // Rider routes
    Route::middleware(['role:rider'])->prefix('rider')->group(function () {
        // Add rider specific routes here
    });
    
    // Regular user routes
    Route::middleware(['role:regular'])->prefix('user')->group(function () {
        // Add regular user specific routes here
    });
});
