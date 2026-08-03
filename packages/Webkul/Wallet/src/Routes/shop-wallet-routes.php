<?php

use Illuminate\Support\Facades\Route;
use Webkul\Wallet\Http\Controllers\Shop\WalletController;
use Webkul\Wallet\Http\Controllers\Shop\WalletCustomerWithdrawalController;
use Webkul\Wallet\Http\Controllers\Shop\WalletTopUpController;
use Webkul\Wallet\Http\Controllers\Shop\WalletTopUpWebhookController;
use Webkul\Wallet\Http\Controllers\Shop\WalletWithdrawalController;

Route::group([
    'middleware' => ['customer'],
    'prefix' => 'customer/wallet',
], function () {

    Route::get('/', [WalletController::class, 'index'])
        ->name('shop.wallet.index');

    Route::get('/withdraw', [WalletCustomerWithdrawalController::class, 'create'])
        ->name('shop.wallet.withdraw.create');

    Route::post('/withdraw', [WalletCustomerWithdrawalController::class, 'store'])
        ->name('shop.wallet.withdraw.store');

    Route::get('/transactions', [WalletController::class, 'transactions'])
        ->name('shop.customer.wallet.transactions');

    // Statement Generator
    Route::get('/statement', [WalletController::class, 'statement'])
        ->name('shop.customer.wallet.statement');

    Route::get('/statement/download', [WalletController::class, 'downloadStatement'])
        ->name('shop.customer.wallet.statement.download');

    Route::get('/statement/export-csv', [WalletController::class, 'exportCsvStatement'])
        ->name('shop.customer.wallet.statement.csv');

    // Top-Up
    Route::get('/topup', [WalletTopUpController::class, 'create'])
        ->name('shop.wallet.topup.create');

    Route::post('/topup', [WalletTopUpController::class, 'store'])
        ->name('shop.wallet.topup.store');

    Route::post('/topup/initiate', [WalletTopUpController::class, 'initiate'])
        ->name('shop.customer.wallet.topup.initiate');

    Route::get('/topup/callback', [WalletTopUpController::class, 'callback'])
        ->name('shop.customer.wallet.topup.callback');

    Route::get('/topup/cancel', [WalletTopUpController::class, 'cancel'])
        ->name('shop.customer.wallet.topup.cancel');

    // Withdrawal
    Route::get('/withdrawals', [WalletWithdrawalController::class, 'index'])
        ->name('shop.customer.wallet.withdrawal.index');

    Route::get('/withdrawals/create', [WalletWithdrawalController::class, 'create'])
        ->name('shop.customer.wallet.withdrawal.create');

    Route::post('/withdrawals', [WalletWithdrawalController::class, 'store'])
        ->name('shop.customer.wallet.withdrawal.store');
});

/**
 * Public Gateway Webhook Route for Automated Wallet Top-Ups.
 */
Route::post('wallet/topup/webhook/{gateway}', [WalletTopUpWebhookController::class, 'handleWebhook'])
    ->name('shop.wallet.topup.webhook');
