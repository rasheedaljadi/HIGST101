<?php

use Illuminate\Support\Facades\Route;
use Webkul\Admin\Http\Controllers\Dropshipping\DropshippingController;
use Webkul\Admin\Http\Controllers\Dropshipping\PricingController;

/**
 * Dropshipping routes.
 */
Route::controller(DropshippingController::class)->prefix('dropshipping')->group(function () {
    Route::get('imports', 'imports')->name('admin.dropshipping.imports.index');
    Route::get('fulfillment', 'fulfillment')->name('admin.dropshipping.fulfillment.index');
    Route::get('api-keys', 'apiKeys')->name('admin.dropshipping.api-keys.index');
});

/**
 * Dropshipping Pricing Hub routes.
 */
Route::controller(PricingController::class)->prefix('dropshipping/pricing')->group(function () {
    Route::get('/', 'index')->name('admin.dropshipping.pricing.index');
    Route::post('/rules', 'storeRule')->name('admin.dropshipping.pricing.rules.store');
    Route::match(['put', 'post'], '/rules/{id}', 'updateRule')->name('admin.dropshipping.pricing.rules.update');
    Route::match(['delete', 'post'], '/rules/{id}', 'destroyRule')->name('admin.dropshipping.pricing.rules.destroy');
    Route::match(['delete', 'post'], '/rules/{id}/delete', 'destroyRule')->name('admin.dropshipping.pricing.rules.destroy.alias');
    Route::get('/history', fn () => redirect()->route('admin.audit-logs.pricing.index'))->name('admin.dropshipping.pricing.history');
    Route::post('/recalculate', 'recalculate')->name('admin.dropshipping.pricing.recalculate');
    Route::post('/override', 'toggleOverride')->name('admin.dropshipping.pricing.override.store');
});

/**
 * Audit Logs routes.
 */
Route::controller(PricingController::class)->prefix('audit-logs')->group(function () {
    Route::get('/pricing', 'history')->name('admin.audit-logs.pricing.index');
    Route::get('/products-import', 'productImportHistory')->name('admin.audit-logs.products-import.index');
});
