<?php

use Illuminate\Support\Facades\Route;
use Webkul\FlashDeal\Http\Controllers\Admin\FlashDealController;

Route::group(['middleware' => ['web', 'admin'], 'prefix' => config('app.admin_url').'/marketing/promotions/flash-deals'], function () {
    Route::controller(FlashDealController::class)->group(function () {
        Route::get('', 'index')->name('admin.marketing.promotions.flash_deals.index');
        Route::get('create', 'create')->name('admin.marketing.promotions.flash_deals.create');
        Route::post('create', 'store')->name('admin.marketing.promotions.flash_deals.store');
        Route::get('edit/{id}', 'edit')->name('admin.marketing.promotions.flash_deals.edit');
        Route::put('edit/{id}', 'update')->name('admin.marketing.promotions.flash_deals.update');
        Route::delete('edit/{id}', 'destroy')->name('admin.marketing.promotions.flash_deals.delete');
    });
});
