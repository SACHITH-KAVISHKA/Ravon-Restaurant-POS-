<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\PaymentController;

Route::get('/', function () {
    return redirect()->route('login');
});

// Authentication routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [App\Http\Controllers\Auth\LoginController::class, 'login']);
});

Route::post('/logout', [App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout');

// Protected routes
Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Tables
    Route::get('/tables', [TableController::class, 'index'])->name('tables.index');
    Route::get('/tables/{table}', [TableController::class, 'show'])->name('tables.show');
    Route::post('/tables/{table}/select', [TableController::class, 'select'])->name('tables.select');
    Route::post('/tables/merge', [TableController::class, 'merge'])->name('tables.merge');
    Route::post('/tables/{table}/split', [TableController::class, 'split'])->name('tables.split');
    Route::post('/tables/{table}/transfer', [TableController::class, 'transfer'])->name('tables.transfer');

    // Orders
    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
    Route::put('/orders/{order}', [OrderController::class, 'update'])->name('orders.update');
    Route::delete('/orders/items/{item}', [OrderController::class, 'destroy'])->name('orders.items.destroy');
    Route::post('/orders/{order}/items', [OrderController::class, 'addItem'])->name('orders.addItem');
    Route::post('/orders/{order}/discount', [OrderController::class, 'applyDiscount'])->name('orders.discount');
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');

    // Menu Management (Admin only)
    Route::middleware(['role:admin'])->prefix('menu')->name('menu.')->group(function () {
        Route::get('/', [MenuController::class, 'index'])->name('index');
        Route::get('/categories/create', [MenuController::class, 'createCategory'])->name('categories.create');
        Route::post('/categories', [MenuController::class, 'storeCategory'])->name('categories.store');
        Route::get('/items/create', [MenuController::class, 'createItem'])->name('items.create');
        Route::post('/items', [MenuController::class, 'storeItem'])->name('items.store');
        Route::get('/items/{item}/edit', [MenuController::class, 'editItem'])->name('items.edit');
        Route::put('/items/{item}', [MenuController::class, 'updateItem'])->name('items.update');
        Route::delete('/items/{item}', [MenuController::class, 'destroyItem'])->name('items.destroy');

        // Modifier routes
        Route::post('/items/{item}/modifiers', [MenuController::class, 'storeModifier'])->name('modifiers.store');
        Route::put('/modifiers/{modifier}', [MenuController::class, 'updateModifier'])->name('modifiers.update');
        Route::delete('/modifiers/{modifier}', [MenuController::class, 'destroyModifier'])->name('modifiers.destroy');
    });

    // Kitchen Display (Kitchen staff) - Placeholder routes
    Route::middleware(['role:kitchen|admin'])->prefix('kitchen')->name('kitchen.')->group(function () {
        Route::get('/', function () {
            return view('dashboard');
        })->name('index');
    });

    // Payments (Cashier)
    Route::middleware(['role:cashier|admin'])->prefix('payments')->name('payments.')->group(function () {
        Route::get('/', [PaymentController::class, 'index'])->name('index');
        Route::get('/orders/{order}', [PaymentController::class, 'show'])->name('show');
        Route::post('/orders/{order}', [PaymentController::class, 'process'])->name('process');
        Route::post('/refund/{payment}', [PaymentController::class, 'refund'])->name('refund');
        Route::get('/daily-summary', [PaymentController::class, 'dailySummary'])->name('daily-summary');
    });

    // Reports (Admin & Cashier) - Placeholder routes
    Route::middleware(['role:admin|cashier'])->prefix('reports')->name('reports.')->group(function () {
        Route::get('/', function () {
            return view('dashboard');
        })->name('index');
        Route::get('/daily-sales', function () {
            return view('dashboard');
        })->name('daily-sales');
        Route::get('/item-sales', function () {
            return view('dashboard');
        })->name('item-sales');
        Route::get('/staff-performance', function () {
            return view('dashboard');
        })->name('staff-performance');
        Route::get('/export', function () {
            return redirect()->route('dashboard');
        })->name('export');
    });

    // POS (Cashier & Admin)
    Route::middleware(['role:cashier|admin'])->prefix('pos')->name('pos.')->group(function () {
        Route::get('/', [App\Http\Controllers\POSController::class, 'index'])->name('index');
        Route::get('/item/{id}', [App\Http\Controllers\POSController::class, 'getItem'])->name('getItem');
        Route::get('/tables', [App\Http\Controllers\POSController::class, 'getAvailableTables'])->name('tables');
        Route::get('/open-checks', [App\Http\Controllers\POSController::class, 'getOpenChecks'])->name('openChecks');
        Route::get('/order/{orderId}', [App\Http\Controllers\POSController::class, 'getOrder'])->name('getOrder');
        Route::post('/place-order', [App\Http\Controllers\POSController::class, 'placeOrder'])->name('placeOrder');
        Route::post('/payment', [App\Http\Controllers\POSController::class, 'processPayment'])->name('payment');
        Route::get('/receipt/{orderId}', [App\Http\Controllers\POSController::class, 'printReceipt'])->name('receipt');
    });
});
