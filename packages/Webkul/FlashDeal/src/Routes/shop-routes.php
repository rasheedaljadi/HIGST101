<?php

use Illuminate\Support\Facades\Route;
use Webkul\FlashDeal\Http\Controllers\Shop\FlashDealController;

Route::group(['middleware' => ['web', 'locale', 'theme', 'currency']], function () {
    Route::get('api/shop/flash-deals/active', [FlashDealController::class, 'getActiveDealsJson'])
        ->name('shop.api.flash_deals.active');
});
