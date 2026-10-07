<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;

// Public
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);

// Midtrans callback - harus public, jangan pakai auth
Route::post('/payment/callback', [PaymentController::class, 'callback']);

// Butuh login (user)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    
    Route::get('/carts', [CartController::class, 'index']);
    Route::post('/carts', [CartController::class, 'store']);
    Route::put('/carts/{cart}', [CartController::class, 'update']);
    Route::delete('/carts/{cart}', [CartController::class, 'destroy']);
    Route::delete('/carts', [CartController::class, 'clear']);

    Route::post('/checkout', [OrderController::class, 'checkout']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel']);
    
    // Admin - untuk sekarang pakai auth dulu, nanti bisa tambah is_admin
    Route::middleware('is_admin')->prefix('admin')->group(function () {
        Route::get('/orders', [AdminOrderController::class, 'index']);
        Route::get('/orders/{id}', [AdminOrderController::class, 'show']);
        
        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{product}', [ProductController::class, 'update']);
        Route::delete('/products/{product}', [ProductController::class, 'destroy']);
    });
});