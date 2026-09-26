<?php

use Illuminate\Support\Facades\Route;
use Webkul\MobileApi\Http\Controllers\Admin\KeyController;

Route::group(['middleware' => ['web', 'admin'], 'prefix' => config('app.admin_url')], function () {
    Route::controller(KeyController::class)->prefix('settings/mobile-api-keys')->group(function () {
        Route::get('', 'index')->name('admin.settings.mobile_api_keys.index');
        Route::post('create', 'store')->name('admin.settings.mobile_api_keys.store');
        Route::delete('{id}', 'destroy')->name('admin.settings.mobile_api_keys.destroy');
    });
});
