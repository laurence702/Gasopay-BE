<?php

use Illuminate\Http\Request;
use App\Http\Middleware\SuperAdmin;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductTypeController;
use App\Http\Controllers\Api\PaymentProofController;
use App\Http\Controllers\PaymentHistoryController;
use App\Http\Controllers\Api\UserProfileController;
use App\Http\Middleware\BranchAdmin;
use App\Http\Middleware\CheckAdminOrSuperAdmin;

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
Route::post('/auth/register-rider', [AuthController::class, 'registerRider'])->name('auth.register_rider');
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum')->name('logout');
Route::get('/me', [AuthController::class, 'loggedInUser'])->middleware('auth:sanctum')->name('user');

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/branches', [BranchController::class, 'index'])->name('branches.index');
    Route::get('/branches/{branch}', [BranchController::class, 'show'])
        ->missing(function () {
            return response()->json(['message' => 'Branch not found.'], 404);
        });

    // Branch admin routes
    Route::middleware([BranchAdmin::class])->group(function () {
        Route::put('/branches/{branch}', [BranchController::class, 'update'])
            ->missing(function () {
                return response()->json(['message' => 'Branch not found.'], 404);
            });
        Route::delete('/branches/{branch}', [BranchController::class, 'destroy'])
            ->missing(function () {
                return response()->json(['message' => 'Branch not found.'], 404);
            });
    });

    // Removed Riders management from here and moved to branch-admin prefixed group
    // Route::get('/riders', [App\Http\Controllers\Api\BranchRiderController::class, 'getRiders'])
    //     ->name('branch-admin.riders');
    // Route::post('/riders', [App\Http\Controllers\Api\BranchRiderController::class, 'store'])
    //     ->name('branch-admin.riders.store');
    
    // Removed Pending approvals from here and moved to branch-admin prefixed group
    // Route::get('/pending-approvals', [App\Http\Controllers\Api\BranchRiderController::class, 'getPendingApprovals'])
    //     ->name('branch-admin.pending-approvals');
    
    // Removed Update rider verification status from here and moved to branch-admin prefixed group
    // Route::put('/riders/{id}/verification', [App\Http\Controllers\Api\BranchRiderController::class, 'updateVerificationStatus'])
    //     ->name('branch-admin.update-verification');

    // Update rider information by Branch Admin (This seems to be handled by UserController, not BranchRiderController, keep it here for now)
    Route::put('/riders/{user}', [UserController::class, 'branchAdminUpdateRider'])->name('branch-admin.riders.update');

    // Product Management
    Route::get('/products', [App\Http\Controllers\Api\BranchProductController::class, 'getProducts'])
        ->name('branch-admin.products');
    Route::get('/products/{id}', [App\Http\Controllers\Api\BranchProductController::class, 'getProduct'])
        ->name('branch-admin.product');
    
    // QR Scanner
    Route::post('/scan', [App\Http\Controllers\Api\QRScannerController::class, 'processScan'])
        ->name('branch-admin.scan');
    
    // create Order
    Route::post('/orders', [App\Http\Controllers\Api\OrderController::class, 'createOrder'])
        ->name('branch-admin.create-order');
});

// Super Admin routes
Route::middleware(['auth:sanctum', SuperAdmin::class])->prefix('super-admin')->group(function () {
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
    Route::delete('/branches/{branch}/force', [BranchController::class, 'forceDelete'])
        ->missing(function () {
            return response()->json(['message' => 'Branch not found.'], 404);
        });
});

// User routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::middleware(['throttle:60,1'])->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    });

    // User routes with different rate limiting
    Route::middleware(['throttle:10,1'])->group(function () {
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::post('/users/{user}/restore', [UserController::class, 'restore'])->name('users.restore');
        Route::delete('/users/{user}/force', [UserController::class, 'forceDelete'])->name('users.forceDelete');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::post('/users/{user}/ban', [UserController::class, 'ban'])->middleware([CheckAdminOrSuperAdmin::class])->name('users.ban');
        Route::post('/users/{user}/verify', [UserController::class, 'verifyRider'])->middleware([CheckAdminOrSuperAdmin::class])->name('users.verify');
    });

    Route::put('/riders/verification', [UserController::class, 'updateVerificationStatus'])
        ->name('users.update_verification_status');

    // Product routes
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
    Route::post('/products', [ProductController::class, 'store'])->middleware([CheckAdminOrSuperAdmin::class])->name('products.store');
    Route::put('/products/{product}', [ProductController::class, 'update'])->middleware([CheckAdminOrSuperAdmin::class])->name('products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->middleware([CheckAdminOrSuperAdmin::class])->name('products.destroy');
    
    // Product Type routes (using enum-based approach)
    Route::get('/product-types', [ProductTypeController::class, 'index'])->name('product-types.index');
    Route::get('/product-types/{type}', [ProductTypeController::class, 'show'])->name('product-types.show');

    // User Profile routes
    Route::get('/user-profiles', [UserProfileController::class, 'index'])->name('user-profiles.index');
    Route::post('/user-profiles', [UserProfileController::class, 'store'])->name('user-profiles.store');
    Route::get('/user-profiles/{userProfile}', [UserProfileController::class, 'show'])->name('user-profiles.show');
    Route::put('/user-profiles/{userProfile}', [UserProfileController::class, 'update'])->name('user-profiles.update');
    Route::delete('/user-profiles/{userProfile}', [UserProfileController::class, 'destroy'])->name('user-profiles.destroy');

    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');

    // Payment History routes (moved to general auth group)
    Route::apiResource('payment-histories', PaymentHistoryController::class);
    Route::post('payment-histories/{paymentHistory}/mark-cash', [PaymentHistoryController::class, 'markCashPayment'])->name('payment-histories.mark-cash');
    Route::post('payment-histories/{paymentHistory}/proof', [PaymentProofController::class, 'submit'])->name('payment-histories.proof.submit');
});

// Branch Admin Dashboard routes
Route::middleware(['auth:sanctum', \App\Http\Middleware\BranchAdmin::class])->prefix('branch-admin')->group(function () {
    // Dashboard statistics
    Route::get('/statistics', [App\Http\Controllers\Api\BranchDashboardController::class, 'getStatistics'])
        ->name('branch-admin.statistics');
    
    // Branch information
    Route::get('/branch-info', [App\Http\Controllers\Api\BranchDashboardController::class, 'getBranchInfo'])
        ->name('branch-admin.branch-info');
    
    // Recent activities
    Route::get('/activities', [App\Http\Controllers\Api\BranchActivityController::class, 'getRecentActivities'])
        ->name('branch-admin.activities');
    
    // Order history - accessible to all authenticated users
    Route::get('/orders/history', [App\Http\Controllers\Api\BranchActivityController::class, 'getOrderHistory'])
        ->name('orders.history');

    // Riders management (Moved from general auth group)
    Route::get('/riders', [App\Http\Controllers\Api\BranchRiderController::class, 'getRiders'])
        ->name('branch-admin.riders');
    Route::post('/riders', [App\Http\Controllers\Api\BranchRiderController::class, 'store'])
        ->name('branch-admin.riders.store');
    
    // Pending approvals (Moved from general auth group)
    Route::get('/pending-approvals', [App\Http\Controllers\Api\BranchRiderController::class, 'getPendingApprovals'])
        ->name('branch-admin.pending-approvals');
    
    // Update rider verification status (Moved from general auth group)
    Route::put('/riders/{id}/verification', [App\Http\Controllers\Api\BranchRiderController::class, 'updateVerificationStatus'])
        ->name('branch-admin.update-verification');
});

// Payment proof routes
Route::middleware('auth:sanctum')->group(function () {
    // Upload payment proof - available to all authenticated users
    Route::post('/payment-proofs', [App\Http\Controllers\Api\PaymentProofController::class, 'store'])
        ->name('payment-proofs.store');
    
    // Admin/SuperAdmin only routes
    Route::middleware(['auth:sanctum', CheckAdminOrSuperAdmin::class])->group(function () {
        Route::get('/payment-proofs', [App\Http\Controllers\Api\PaymentProofController::class, 'index'])
            ->name('payment-proofs.index');
        Route::post('/payment-proofs/{paymentProof}/approve', [App\Http\Controllers\Api\PaymentProofController::class, 'approve'])
            ->name('payment-proofs.approve');
        Route::post('/payment-proofs/{paymentProof}/reject', [App\Http\Controllers\Api\PaymentProofController::class, 'reject'])
            ->name('payment-proofs.reject');
    });
});
