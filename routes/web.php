<?php

use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\InventoryItemController;
use App\Http\Controllers\Admin\LaundryOrderController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ScanController;
use App\Http\Controllers\Admin\ServicePackageController;
use App\Http\Controllers\Admin\StockMovementController;
use App\Http\Controllers\Admin\StockOpnameController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerTrackingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SuperAdmin\SuperAdminController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CustomerTrackingController::class, 'index'])->name('tracking.index');
Route::post('/track', [CustomerTrackingController::class, 'track'])->name('tracking.track');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])
            ->middleware('role:owner,admin,kasir,operator')
            ->name('dashboard');

        Route::get('reports/monthly', [ReportController::class, 'monthly'])
            ->middleware('role:owner,admin,kasir')
            ->name('reports.monthly');

        Route::middleware('role:owner,admin,kasir')->group(function () {
            Route::resource('customers', CustomerController::class);
        });

        Route::middleware('role:owner,admin')->group(function () {
            Route::resource('service-packages', ServicePackageController::class)
                ->parameters(['service-packages' => 'servicePackage']);
        });

        Route::middleware('role:owner,admin,operator')->group(function () {
            Route::resource('inventory-items', InventoryItemController::class)
                ->parameters(['inventory-items' => 'inventoryItem']);
            Route::resource('stock-movements', StockMovementController::class)
                ->only(['index', 'create', 'store']);
            Route::resource('stock-opnames', StockOpnameController::class)
                ->parameters(['stock-opnames' => 'stockOpname']);
            Route::post('stock-opnames/{stockOpname}/post', [StockOpnameController::class, 'post'])
                ->name('stock-opnames.post');
        });

        Route::middleware('role:owner,admin,kasir,operator')->group(function () {
            Route::resource('laundry-orders', LaundryOrderController::class)
                ->parameters(['laundry-orders' => 'laundryOrder']);
            Route::get('laundry-orders/{laundryOrder}/invoice', [LaundryOrderController::class, 'invoicePdf'])
                ->name('laundry-orders.invoice');
            Route::get('laundry-orders/{laundryOrder}/receipt-customer', [LaundryOrderController::class, 'receiptCustomerPdf'])
                ->name('laundry-orders.receipt-customer');
            Route::get('laundry-orders/{laundryOrder}/receipt-internal', [LaundryOrderController::class, 'receiptInternalPdf'])
                ->name('laundry-orders.receipt-internal');
            Route::post('laundry-orders/{laundryOrder}/status', [LaundryOrderController::class, 'updateStatus'])
                ->name('laundry-orders.status');

            Route::get('scan', [ScanController::class, 'index'])->name('scan.index');
            Route::post('scan/update', [ScanController::class, 'update'])->name('scan.update');
        });

        Route::post('laundry-orders/{laundryOrder}/payments', [LaundryOrderController::class, 'addPayment'])
            ->middleware('role:owner,admin,kasir')
            ->name('laundry-orders.payments');
    });
});

Route::prefix('superadmin')->name('superadmin.')->middleware(['auth', 'role:superadmin'])->group(function () {
    Route::get('/', [SuperAdminController::class, 'dashboard'])->name('dashboard');
    Route::post('/impersonate', [SuperAdminController::class, 'impersonate'])->name('impersonate');
    Route::post('/tenants', [SuperAdminController::class, 'storeTenant'])->name('tenants.store');
    Route::post('/users', [SuperAdminController::class, 'storeUser'])->name('users.store');
});
