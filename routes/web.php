<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SalesReportController;
use App\Http\Controllers\ItemSalesReportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VoidReportController;

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

        // Category routes
        Route::get('/categories', [MenuController::class, 'indexCategories'])->name('categories.index');
        Route::post('/categories', [MenuController::class, 'storeCategory'])->name('categories.store');
        Route::put('/categories/{category}', [MenuController::class, 'updateCategory'])->name('categories.update');
        Route::delete('/categories/{category}', [MenuController::class, 'destroyCategory'])->name('categories.destroy');

        // Item routes
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

        // Sales by Item Summary Report
        Route::get('/item-sales', [ItemSalesReportController::class, 'index'])->name('item-sales');
        Route::post('/item-sales/filter', [ItemSalesReportController::class, 'filter'])->name('item-sales.filter');
        Route::post('/item-sales/details', [ItemSalesReportController::class, 'getItemDetails'])->name('item-sales.details');
        Route::get('/item-sales/export', [ItemSalesReportController::class, 'exportSummary'])->name('item-sales.export');
        Route::get('/item-sales/export-details', [ItemSalesReportController::class, 'exportItemDetails'])->name('item-sales.export-details');

        Route::get('/staff-performance', function () {
            return view('dashboard');
        })->name('staff-performance');
        Route::get('/export', function () {
            return redirect()->route('dashboard');
        })->name('export');
    });

    // Sales Report (Admin only)
    Route::middleware(['role:admin'])->prefix('sales-report')->name('sales-report.')->group(function () {
        Route::get('/', [SalesReportController::class, 'index'])->name('index');
        Route::get('/sale-details/{order}', [SalesReportController::class, 'getSaleDetails'])->name('sale-details');
        Route::get('/receipt/{order}', [SalesReportController::class, 'receipt'])->name('receipt');
        Route::get('/export', [SalesReportController::class, 'exportExcel'])->name('export');
        Route::delete('/order/{order}', [SalesReportController::class, 'softDelete'])->name('order.delete');
    });

    // Void Report (Admin only)
    Route::middleware(['role:admin'])->prefix('void-report')->name('void-report.')->group(function () {
        Route::get('/', [VoidReportController::class, 'index'])->name('index');
        Route::get('/export', [VoidReportController::class, 'export'])->name('export');
        Route::get('/{voidRecord}', [VoidReportController::class, 'getDetails'])->name('details');
    });

    // User Management (Admin only)
    Route::middleware(['role:admin'])->prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{user}', [UserController::class, 'show'])->name('show');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
    });

    // POS (Cashier only)
    Route::middleware(['role:cashier'])->prefix('pos')->name('pos.')->group(function () {
        Route::get('/', [App\Http\Controllers\POSController::class, 'index'])->name('index');
        Route::get('/item/{id}', [App\Http\Controllers\POSController::class, 'getItem'])->name('getItem');
        Route::get('/tables', [App\Http\Controllers\POSController::class, 'getAvailableTables'])->name('tables');
        Route::get('/open-checks', [App\Http\Controllers\POSController::class, 'getOpenChecks'])->name('openChecks');
        Route::get('/closed-orders', [App\Http\Controllers\POSController::class, 'getClosedOrders'])->name('closedOrders');
        Route::get('/order/{orderId}', [App\Http\Controllers\POSController::class, 'getOrder'])->name('getOrder');
        Route::post('/place-order', [App\Http\Controllers\POSController::class, 'placeOrder'])->name('placeOrder');
        Route::post('/payment', [App\Http\Controllers\POSController::class, 'processPayment'])->name('payment');
        Route::get('/receipt/{orderId}', [App\Http\Controllers\POSController::class, 'printReceipt'])->name('receipt');
        Route::post('/verify-supervisor-pin', [App\Http\Controllers\POSController::class, 'verifySupervisorPin'])->name('verifySupervisorPin');
        Route::post('/void-items', [App\Http\Controllers\POSController::class, 'voidItems'])->name('voidItems');
    });

    // Stock Management Routes
    Route::prefix('stock')->name('stock.')->group(function () {
        // Cashier routes
        Route::middleware(['role:cashier'])->group(function () {
            Route::get('/cashier', [App\Http\Controllers\StockRequestController::class, 'cashierIndex'])->name('cashier.index');
            Route::get('/my-stock', [App\Http\Controllers\StockRequestController::class, 'cashierStock'])->name('cashier.stock');
            Route::post('/', [App\Http\Controllers\StockRequestController::class, 'store'])->name('store');
            Route::get('/my-requests', [App\Http\Controllers\StockRequestController::class, 'getCashierRequests'])->name('my-requests');
            Route::post('/{stockRequest}/cashier-respond', [App\Http\Controllers\StockRequestController::class, 'cashierRespond'])->name('cashier.respond');
        });

        // Supervisor routes (only supervisors can approve/reject stock requests)
        Route::middleware(['role:supervisor'])->group(function () {
            Route::get('/supervisor', [App\Http\Controllers\StockRequestController::class, 'supervisorIndex'])->name('supervisor.index');
            Route::post('/{stockRequest}/supervisor-respond', [App\Http\Controllers\StockRequestController::class, 'supervisorRespond'])->name('supervisor.respond');
        });

        // Shared routes (accessible by both)
        Route::get('/{stockRequest}', [App\Http\Controllers\StockRequestController::class, 'show'])->name('show');
        Route::get('/items/list', [App\Http\Controllers\StockRequestController::class, 'getItems'])->name('items');
    });

    // Main Stock Management Routes (Supervisor only)
    Route::middleware(['role:supervisor'])->prefix('main-stock')->name('main-stock.')->group(function () {
        // Stock Items CRUD
        Route::get('/', [App\Http\Controllers\MainStockController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\MainStockController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\MainStockController::class, 'store'])->name('store');
        Route::get('/{mainStock}/edit', [App\Http\Controllers\MainStockController::class, 'edit'])->name('edit');
        Route::put('/{mainStock}', [App\Http\Controllers\MainStockController::class, 'update'])->name('update');
        Route::delete('/{mainStock}', [App\Http\Controllers\MainStockController::class, 'destroy'])->name('destroy');

        // Stock Operations
        Route::get('/stock-update', [App\Http\Controllers\MainStockController::class, 'showStockUpdate'])->name('stock-update');
        Route::post('/stock-update', [App\Http\Controllers\MainStockController::class, 'processStockUpdate'])->name('process-stock-update');

        // AJAX endpoints
        Route::get('/item/{mainStock}', [App\Http\Controllers\MainStockController::class, 'getItem'])->name('get-item');
        Route::get('/generate-code', [App\Http\Controllers\MainStockController::class, 'generateCode'])->name('generate-code');
    });

    // QZ Tray Signature Route (for thermal printing)
    Route::post('/qz/sign', [App\Http\Controllers\QZTrayController::class, 'signQzRequest'])->name('qz.sign');
});
