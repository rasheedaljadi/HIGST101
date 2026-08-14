<?php

use Illuminate\Support\Facades\Route;
use Webkul\Admin\Http\Controllers\CacheManagementController;
use Webkul\Admin\Http\Controllers\ConfigurationController;

/**
 * Configuration routes.
 */
Route::get('configuration/search', [ConfigurationController::class, 'search'])->name('admin.configuration.search');

Route::post('configuration/cache-management/execute', [CacheManagementController::class, 'execute'])
    ->name('admin.configuration.cache-management.execute');

Route::controller(ConfigurationController::class)->group(function () {
    Route::get('configuration/{slug}/{slug2}/{path}', 'download')->name('admin.configuration.download');

    Route::get('configuration/{slug?}/{slug2?}', 'index')->name('admin.configuration.index');

    Route::post('configuration/{slug?}/{slug2?}', 'store')->name('admin.configuration.store');
});
