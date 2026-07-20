<?php

use Illuminate\Support\Facades\Route;
use Webkul\Admin\Http\Controllers\Dropshipping\DropshippingController;

/**
 * Dropshipping routes.
 */
Route::controller(DropshippingController::class)->prefix('dropshipping')->group(function () {
    Route::get('imports', 'imports')->name('admin.dropshipping.imports.index');
    Route::get('fulfillment', 'fulfillment')->name('admin.dropshipping.fulfillment.index');
    Route::get('api-keys', 'apiKeys')->name('admin.dropshipping.api-keys.index');
});
