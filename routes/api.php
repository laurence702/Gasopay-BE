<?php

use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\UserProfileController;
use App\Http\Controllers\Api\VehicleTypeController;
use App\Http\Controllers\Api\RiderController;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;
use App\Models\Branch;
use App\Enums\RoleEnum;
use App\Http\Middleware\CheckRole;

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
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    // Product routes
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');


    // User Profile routes
    Route::get('/user-profiles', [UserProfileController::class, 'index'])->name('user-profiles.index');
    Route::post('/user-profiles', [UserProfileController::class, 'store'])->name('user-profiles.store');
    Route::get('/user-profiles/{userProfile}', [UserProfileController::class, 'show'])->name('user-profiles.show');
    Route::put('/user-profiles/{userProfile}', [UserProfileController::class, 'update'])->name('user-profiles.update');
    Route::delete('/user-profiles/{userProfile}', [UserProfileController::class, 'destroy'])->name('user-profiles.destroy');

    // Vehicle Type routes
    Route::get('/vehicle-types', [VehicleTypeController::class, 'index'])->name('vehicle-types.index');
    Route::get('/vehicle-types/{vehicleType}', [VehicleTypeController::class, 'show'])->name('vehicle-types.show');

    // Admin routes
    Route::middleware([CheckRole::class . ':' . RoleEnum::Admin->value])->group(function () {
        //Admin create rider Profile
        Route::post('/register-rider', [UserController::class, 'register_rider'])->name('users.registerRider');
        //create Branch Admin
        Route::post('/create-admin', [UserController::class, 'create_admin'])->name('users.createAdmin');
        //get all users 
        Route::get('/users', [UserController::class, 'allUsers'])->name('users.showAll');

        Route::get('/branches', [BranchController::class, 'index'])->name('branches.index');
        Route::post('/branches', [BranchController::class, 'store'])->name('branches.store');
        Route::get('/branches/{branch}', [BranchController::class, 'show'])
            ->missing(function () {
                return response()->json(['message' => 'Branch not found.'], 404);
            });
        Route::put('/branches/{branch}', [BranchController::class, 'update'])
            ->missing(function () {
                return response()->json(['message' => 'Branch not found.'], 404);
            });
        Route::delete('/branches/{branch}', [BranchController::class, 'destroy'])
            ->missing(function () {
                return response()->json(['message' => 'Branch not found.'], 404);
            });
        Route::post('/vehicle-types', [VehicleTypeController::class, 'store'])->name('vehicle-types.store');
        Route::put('/vehicle-types/{vehicleType}', [VehicleTypeController::class, 'update'])->name('vehicle-types.update');
        Route::delete('/vehicle-types/{vehicleType}', [VehicleTypeController::class, 'destroy'])->name('vehicle-types.destroy');
    });

    Route::middleware([CheckRole::class . ':' . RoleEnum::Rider->value])->prefix('rider')->group(function () {
        // Add rider specific routes here
    });
    
});
