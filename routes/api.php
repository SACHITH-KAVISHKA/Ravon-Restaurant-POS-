<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TableController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\KOTController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ReportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    
    // Authentication
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
        Route::post('/revoke-all', [AuthController::class, 'revokeAll']);
    });

    // Tables
    Route::prefix('tables')->group(function () {
        Route::get('/', [TableController::class, 'index']);
        Route::get('/statistics', [TableController::class, 'statistics']);
        Route::get('/{table}', [TableController::class, 'show']);
        Route::patch('/{table}/status', [TableController::class, 'updateStatus']);
        Route::post('/merge', [TableController::class, 'merge']);
        Route::post('/{table}/split', [TableController::class, 'split']);
        Route::post('/{table}/transfer', [TableController::class, 'transfer']);
    });

    // Orders
    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::post('/', [OrderController::class, 'store']);
        Route::get('/{order}', [OrderController::class, 'show']);
        Route::patch('/{order}', [OrderController::class, 'update']);
        Route::post('/{order}/items', [OrderController::class, 'addItem']);
        Route::patch('/{order}/status', [OrderController::class, 'updateStatus']);
        Route::post('/{order}/discount', [OrderController::class, 'applyDiscount']);
        Route::post('/{order}/cancel', [OrderController::class, 'cancel']);
    });

    // Menu
    Route::prefix('menu')->group(function () {
        Route::get('/categories', [MenuController::class, 'categories']);
        Route::get('/items', [MenuController::class, 'items']);
        Route::get('/items/{item}', [MenuController::class, 'show']);
        
        // Admin only routes
        Route::middleware('permission:create-menu')->group(function () {
            Route::post('/categories', [MenuController::class, 'storeCategory']);
            Route::post('/items', [MenuController::class, 'storeItem']);
        });
        
        Route::middleware('permission:edit-menu')->group(function () {
            Route::patch('/items/{item}', [MenuController::class, 'updateItem']);
        });
        
        Route::middleware('permission:delete-menu')->group(function () {
            Route::delete('/items/{item}', [MenuController::class, 'destroyItem']);
        });
    });

    // KOT (Kitchen Order Tickets)
    Route::prefix('kot')->group(function () {
        Route::get('/pending', [KOTController::class, 'pending']);
        Route::get('/stations', [KOTController::class, 'stations']);
        Route::get('/station/{station}', [KOTController::class, 'forStation']);
        Route::get('/{kot}', [KOTController::class, 'show']);
        Route::patch('/{kot}/status', [KOTController::class, 'updateStatus']);
        Route::post('/{kot}/print', [KOTController::class, 'print']);
        Route::post('/{kot}/reprint', [KOTController::class, 'reprint']);
    });

    // Payments
    Route::prefix('payments')->group(function () {
        Route::get('/', [PaymentController::class, 'index']);
        Route::post('/', [PaymentController::class, 'store']);
        Route::get('/summary', [PaymentController::class, 'dailySummary']);
        Route::get('/statistics', [PaymentController::class, 'statistics']);
        Route::get('/{payment}', [PaymentController::class, 'show']);
        Route::get('/bill/{order}', [PaymentController::class, 'getBill']);
        Route::post('/bill/{order}/print', [PaymentController::class, 'printBill']);
        
        Route::middleware('permission:refund-payments')->group(function () {
            Route::post('/{payment}/refund', [PaymentController::class, 'refund']);
        });
    });

    // Reports
    Route::prefix('reports')->middleware('permission:view-reports')->group(function () {
        Route::get('/daily-sales', [ReportController::class, 'dailySales']);
        Route::get('/item-sales', [ReportController::class, 'itemSales']);
        Route::get('/category-sales', [ReportController::class, 'categorySales']);
        Route::get('/staff-performance', [ReportController::class, 'staffPerformance']);
        Route::get('/table-turnover', [ReportController::class, 'tableTurnover']);
        Route::get('/peak-hours', [ReportController::class, 'peakHours']);
        Route::get('/monthly-summary', [ReportController::class, 'monthlySummary']);
        
        Route::middleware('permission:export-reports')->group(function () {
            Route::get('/export', [ReportController::class, 'export']);
        });
    });
});
