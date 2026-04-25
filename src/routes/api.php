<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\CafeController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CityController;
use App\Http\Controllers\Api\OtpController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\WalletController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
|
*/

// Public routes - Auth (stricter rate limit: 5 requests/minute)
Route::middleware('throttle:auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/login/verify-otp', [AuthController::class, 'verifyLoginOtp']);

    // OTP routes
    Route::post('/otp/send', [OtpController::class, 'send']);
    Route::post('/otp/verify', [OtpController::class, 'verify']);
    Route::post('/otp/resend', [OtpController::class, 'resend']);

    // Password reset routes
    Route::post('/password/forgot', [PasswordResetController::class, 'forgotPassword']);
    Route::post('/password/reset', [PasswordResetController::class, 'resetPassword']);
});

// Public routes with standard API rate limit (60 requests/minute)
Route::middleware('throttle:api')->group(function () {
    // Categories
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{identifier}', [CategoryController::class, 'show']);

    // Cities
    Route::get('/cities', [CityController::class, 'index']);
    Route::get('/cities/{identifier}', [CityController::class, 'show']);

    // Cafes
    Route::get('/cafes', [CafeController::class, 'index']);
    Route::get('/cafes/popular', [CafeController::class, 'popular']);
    Route::get('/cafes/newest', [CafeController::class, 'newest']);
    Route::get('/cafes/{id}', [CafeController::class, 'show']);
    Route::get('/cities/{cityId}/cafes', [CafeController::class, 'byCity']);

    Route::get('/branches', [BranchController::class, 'index']);
    Route::get('/branches/popular', [BranchController::class, 'popular']);
    Route::get('/branches/newest', [BranchController::class, 'newest']);
    Route::get('/branches/{id}', [BranchController::class, 'show']);
    Route::get('/cities/{cityId}/branches', [BranchController::class, 'byCity']);

    // Products
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/popular', [ProductController::class, 'popular']);
    Route::get('/products/top-rated', [ProductController::class, 'topRated']);
    Route::get('/products/featured', [ProductController::class, 'featured']);
    Route::get('/products/category/{categorySlug}', [ProductController::class, 'byCategorySlug']);
    Route::get('/products/{identifier}', [ProductController::class, 'show']);
    Route::post('/products/{identifier}/click', [ProductController::class, 'click']);
    Route::get('/categories/{categoryId}/products', [ProductController::class, 'byCategory']);
});

// Protected routes with standard API rate limit (60 requests/minute)
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::get('/user/stats', [AuthController::class, 'stats']);
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);
    Route::post('/user/change-password', [AuthController::class, 'changePassword']);

    // Transaction routes
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::get('/transactions/active', [TransactionController::class, 'active']);
    Route::get('/transactions/{id}', [TransactionController::class, 'show']);
    Route::post('/transactions', [TransactionController::class, 'store']);
    Route::post('/transactions/{id}/reorder', [TransactionController::class, 'reorder']);
    Route::post('/transactions/{id}/cancel', [TransactionController::class, 'cancel']);

    // Wallet routes
    Route::get('/wallet', [WalletController::class, 'index']);
    Route::get('/wallet/transactions', [WalletController::class, 'transactions']);
    Route::get('/wallet/transactions/{id}', [WalletController::class, 'showTransaction']);
    Route::get('/wallet/topup-info', [WalletController::class, 'topupInfo']);
    Route::post('/wallet/topup', [WalletController::class, 'requestTopup']);
    Route::post('/wallet/topup/{id}/upload-proof', [WalletController::class, 'uploadProof']);
    Route::post('/wallet/topup/{id}/cancel', [WalletController::class, 'cancelTopup']);
});
