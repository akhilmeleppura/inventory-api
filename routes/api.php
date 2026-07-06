<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\WarehouseController;
use Illuminate\Support\Facades\Route;

// Public Endpoints
Route::post('/login', [AuthController::class, 'login']);

// Fallback GET route for redirects when Accept header is missing
Route::get('/login', function () {
    return response()->json([
        'error' => 'Unauthorized',
        'message' => 'Unauthenticated or invalid token. Please login first.'
    ], 401);
})->name('login');

// Protected Endpoints
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::post('/stock', [StockController::class, 'store'])->name('stock.store');
    Route::get('/warehouses/{id}/report', [WarehouseController::class, 'report'])->name('warehouses.report');
});
