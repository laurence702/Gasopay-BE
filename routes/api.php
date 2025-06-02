<?php

use Illuminate\Http\Request;
use App\Http\Middleware\SuperAdmin;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\ProductController;
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
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum')->name('logout');
Route::get('/me', [AuthController::class, 'loggedInUser'])->middleware('auth:sanctum')->name('user');

Route::post('/register-rider', [UserController::class, 'register_rider'])->name('users.register_rider');
Route::get('/branches', [BranchController::class, 'index'])->name('branches.index');

Route::middleware(['auth:sanctum'])->group(function () {
    Route::middleware(['throttle:60,1'])->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
       // Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update'); // This will be for user self-update later
      //  Route::post('/register-rider', [UserController::class, 'register_rider'])->name('users.register_rider');
       
    });

    // User routes with different rate limiting
    Route::middleware(['throttle:10,1'])->group(function () {
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::post('/users/{id}/restore', [UserController::class, 'restore'])->withTrashed()->name('users.restore');
        Route::delete('/users/{id}/force', [UserController::class, 'forceDelete'])->withTrashed()->name('users.forceDelete');
        Route::post('/users/{user}/ban', [UserController::class, 'ban'])
            ->name('users.ban');
        Route::post('/users/{user}/unban', [UserController::class, 'unban'])
            ->name('users.unban');
    });

    Route::put('/riders/verification', [UserController::class, 'updateVerificationStatus'])
        ->name('users.update_verification_status');

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
    // Route::get('/branches', [BranchController::class, 'index'])->name('branches.index');
    Route::get('/branches/{branch}', [BranchController::class, 'show'])
        ->missing(function () {
            return response()->json(['message' => 'Branch not found.'], 404);
        });

    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');

    // Payment History routes (moved to general auth group)
    Route::apiResource('payment-histories', PaymentHistoryController::class);
    Route::post('payment-histories/{paymentHistory}/mark-cash', [PaymentHistoryController::class, 'markCashPayment'])->name('payment-histories.mark-cash');
    Route::post('payment-histories/{paymentHistory}/proof', [PaymentProofController::class, 'submit'])->name('payment-histories.proof.submit');
});
Route::middleware(['auth:sanctum'])->group(function () {
    // Order management routes
    // Route::post('/orders', [OrderController::class, 'createOrder'])->name('branch-admin.create-order');
    Route::put('/orders/{order}', [OrderController::class, 'update'])->name('orders.update');
    Route::delete('/orders/{order}', [OrderController::class, 'destroy'])->name('orders.destroy');

    // Payment routes (REMOVED from here - MOVED to general auth:sanctum group)
    // Route::apiResource('payment-histories', PaymentHistoryController::class);
    // Route::post('payment-histories/{paymentHistory}/mark-cash', [PaymentHistoryController::class, 'markCashPayment']);

    // Route::post('payment-histories/{paymentHistory}/proof', [PaymentProofController::class, 'submit']); // Also moved
    Route::post('payment-proofs/{paymentProof}/approve', [PaymentProofController::class, 'approve']);

    // Delete product
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
});

// Super Admin routes
Route::middleware(['auth:sanctum', SuperAdmin::class])->prefix('super-admin')->group(function () {
    // User management - Super Admin can update any user
    Route::put('/users/{user}', [UserController::class, 'superAdminUpdate'])->name('super-admin.users.update');

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
    // Route::apiResource('payment-histories', PaymentHistoryController::class);
    // Route::post('payment-histories/{paymentHistory}/mark-cash', [PaymentHistoryController::class, 'markCashPayment']);

    // Route::post('payment-histories/{paymentHistory}/proof', [PaymentProofController::class, 'submit']); // Also moved
    Route::post('payment-proofs/{paymentProof}/approve', [PaymentProofController::class, 'approve']);
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
    
    // Riders management
    Route::get('/riders', [App\Http\Controllers\Api\BranchRiderController::class, 'getRiders'])
        ->name('branch-admin.riders');
    
    // Pending approvals
    Route::get('/pending-approvals', [App\Http\Controllers\Api\BranchRiderController::class, 'getPendingApprovals'])
        ->name('branch-admin.pending-approvals');
    
    // Update rider verification status
    Route::put('/riders/{id}/verification', [App\Http\Controllers\Api\BranchRiderController::class, 'updateVerificationStatus'])
        ->name('branch-admin.update-verification');

    // Update rider information by Branch Admin
    Route::put('/riders/{user}', [UserController::class, 'branchAdminUpdateRider'])->name('branch-admin.riders.update');

    // Product Management
    Route::get('/products', [App\Http\Controllers\Api\BranchProductController::class, 'getProducts'])
        ->name('branch-admin.products');
    Route::get('/products/{id}', [App\Http\Controllers\Api\BranchProductController::class, 'getProduct'])
        ->name('branch-admin.product');
    Route::post('/price-quote', [App\Http\Controllers\Api\BranchProductController::class, 'createPriceQuote'])
        ->name('branch-admin.price-quote');

    // QR Scanner
    Route::post('/scan', [App\Http\Controllers\Api\QRScannerController::class, 'processScan'])
        ->name('branch-admin.scan');
    
    // create Order
    Route::post('/orders', [App\Http\Controllers\Api\OrderController::class, 'createOrder'])
        ->name('branch-admin.create-order');
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
