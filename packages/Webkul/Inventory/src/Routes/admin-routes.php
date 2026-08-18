<?php

use Illuminate\Support\Facades\Route;
use Webkul\Inventory\Http\Controllers\Admin\InboundReceiptController;
use Webkul\Inventory\Http\Controllers\Admin\InventoryDashboardController;
use Webkul\Inventory\Http\Controllers\Admin\InventoryMovementLedgerController;
use Webkul\Inventory\Http\Controllers\Admin\InventoryProductCardController;
use Webkul\Inventory\Http\Controllers\Admin\InventoryQuarantineController;
use Webkul\Inventory\Http\Controllers\Admin\InventoryReportController;
use Webkul\Inventory\Http\Controllers\Admin\InventorySourceBalanceController;
use Webkul\Inventory\Http\Controllers\Admin\InventoryTransferController;

Route::group(['middleware' => ['web', 'admin'], 'prefix' => config('app.admin_url')], function () {
    Route::prefix('inventory')->name('admin.inventory.')->group(function () {
        // 1. Dashboard
        Route::controller(InventoryDashboardController::class)->prefix('dashboard')->group(function () {
            Route::get('', 'index')->name('dashboard.index');
        });

        // 2. Sources & Balances
        Route::controller(InventorySourceBalanceController::class)->prefix('sources')->group(function () {
            Route::get('', 'index')->name('sources.index');
        });

        // 3. Product Stock by Source
        Route::controller(InventoryProductCardController::class)->prefix('products')->group(function () {
            Route::get('', 'index')->name('products.index');
            Route::get('view/{id}', 'show')->name('products.show');
        });

        // 4. Movements Ledger (Read-Only)
        Route::controller(InventoryMovementLedgerController::class)->prefix('movements')->group(function () {
            Route::get('', 'index')->name('movements.index');
        });

        // 5. Transfer Manifests
        Route::controller(InventoryTransferController::class)->prefix('transfers')->group(function () {
            Route::get('', 'index')->name('transfers.index');
            Route::get('create', 'create')->name('transfers.create');
            Route::post('create', 'store')->name('transfers.store');
            Route::get('view/{id}', 'show')->name('transfers.show');
            Route::post('dispatch/{id}', 'dispatchManifest')->name('transfers.dispatch');
        });

        // 6. Inbound Receipts & Discrepancies
        Route::controller(InboundReceiptController::class)->prefix('receipts')->group(function () {
            Route::get('', 'index')->name('receipts.index');
            Route::get('create', 'create')->name('receipts.create');
            Route::post('preview', 'preview')->name('receipts.preview');
            Route::post('create', 'store')->name('receipts.store');
            Route::get('view/{id}', 'show')->name('receipts.show');
        });

        // 7. Quarantine & Adjustments
        Route::controller(InventoryQuarantineController::class)->prefix('quarantine')->group(function () {
            Route::get('', 'index')->name('quarantine.index');
            Route::post('release/{id}', 'release')->name('quarantine.release');
        });

        // 8. Reports
        Route::controller(InventoryReportController::class)->prefix('reports')->group(function () {
            Route::get('', 'index')->name('reports.index');
            Route::get('export/{type}', 'export')->name('reports.export');
        });
    });
});
