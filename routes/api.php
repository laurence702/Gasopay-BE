<?php

use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\UserProfileController;
use App\Http\Controllers\Api\VehicleTypeController;
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

// User routes
Route::get('/users', [UserController::class, 'index']);
Route::post('/users', [UserController::class, 'store']);
Route::get('/users/{user}', [UserController::class, 'show']);
Route::put('/users/{user}', [UserController::class, 'update']);
Route::delete('/users/{user}', [UserController::class, 'destroy']);

// Product routes
Route::get('/products', [ProductController::class, 'index']);
Route::post('/products', [ProductController::class, 'store']);
Route::get('/products/{product}', [ProductController::class, 'show']);
Route::put('/products/{product}', [ProductController::class, 'update']);
Route::delete('/products/{product}', [ProductController::class, 'destroy']);

// Branch routes
Route::get('/branches', [BranchController::class, 'index']);
Route::post('/branches', [BranchController::class, 'store']);
Route::get('/branches/{branch}', [BranchController::class, 'show']);
Route::put('/branches/{branch}', [BranchController::class, 'update']);
Route::delete('/branches/{branch}', [BranchController::class, 'destroy']);

// User Profile routes
Route::get('/user-profiles', [UserProfileController::class, 'index']);
Route::post('/user-profiles', [UserProfileController::class, 'store']);
Route::get('/user-profiles/{userProfile}', [UserProfileController::class, 'show']);
Route::put('/user-profiles/{userProfile}', [UserProfileController::class, 'update']);
Route::delete('/user-profiles/{userProfile}', [UserProfileController::class, 'destroy']);

// Vehicle Type routes
Route::get('/vehicle-types', [VehicleTypeController::class, 'index']);
Route::post('/vehicle-types', [VehicleTypeController::class, 'store']);
Route::get('/vehicle-types/{vehicleType}', [VehicleTypeController::class, 'show']);
Route::put('/vehicle-types/{vehicleType}', [VehicleTypeController::class, 'update']);
Route::delete('/vehicle-types/{vehicleType}', [VehicleTypeController::class, 'destroy']);
