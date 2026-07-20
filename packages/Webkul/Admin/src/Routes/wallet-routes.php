<?php

use Illuminate\Support\Facades\Route;
use Webkul\Admin\Http\Controllers\Wallet\WalletController;

/**
 * Wallet routes.
 */
Route::controller(WalletController::class)->prefix('wallet')->group(function () {
    Route::get('deposits', 'deposits')->name('admin.wallet.deposits.index');
    Route::get('withdrawals', 'withdrawals')->name('admin.wallet.withdrawals.index');
    Route::get('settings', 'settings')->name('admin.wallet.settings.index');
});
