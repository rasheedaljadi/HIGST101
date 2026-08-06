<?php

use Illuminate\Support\Facades\Route;
use Webkul\Wallet\Http\Controllers\Admin\WalletAccountController;
use Webkul\Wallet\Http\Controllers\Admin\WalletAdjustmentController;
use Webkul\Wallet\Http\Controllers\Admin\WalletDashboardController;
use Webkul\Wallet\Http\Controllers\Admin\WalletReportController;
use Webkul\Wallet\Http\Controllers\Admin\WalletTopUpController;
use Webkul\Wallet\Http\Controllers\Admin\WalletWithdrawalController;
use Webkul\Wallet\Http\Controllers\Admin\WalletWithdrawalMethodController;

Route::group(['prefix' => 'wallet'], function () {

    /**
     * Dashboard.
     */
    Route::get('dashboard', [WalletDashboardController::class, 'index'])
        ->name('admin.wallet.dashboard.index');

    /**
     * Wallet Accounts.
     */
    Route::get('accounts', [WalletAccountController::class, 'index'])
        ->name('admin.wallet.accounts.index');

    Route::get('accounts/{id}', [WalletAccountController::class, 'show'])
        ->name('admin.wallet.accounts.show');

    Route::get('accounts/{id}/adjust', [WalletAdjustmentController::class, 'create'])
        ->name('admin.wallet.accounts.adjust.create');

    Route::post('accounts/{id}/adjust', [WalletAdjustmentController::class, 'store'])
        ->name('admin.wallet.accounts.adjust');

    Route::post('accounts/{id}/suspend', [WalletAccountController::class, 'suspend'])
        ->name('admin.wallet.accounts.suspend');

    Route::post('accounts/{id}/reactivate', [WalletAccountController::class, 'reactivate'])
        ->name('admin.wallet.accounts.reactivate');

    /**
     * Top-Ups (Deposits).
     */
    Route::get('deposits', [WalletTopUpController::class, 'index'])
        ->name('admin.wallet.deposits.index');

    Route::post('deposits/{id}/approve', [WalletTopUpController::class, 'approve'])
        ->name('admin.wallet.deposits.approve');

    Route::post('deposits/{id}/reject', [WalletTopUpController::class, 'reject'])
        ->name('admin.wallet.deposits.reject');

    /**
     * Withdrawals.
     */
    Route::get('withdrawals', [WalletWithdrawalController::class, 'index'])
        ->name('admin.wallet.withdrawals.index');

    Route::get('withdrawals/{id}/process', [WalletWithdrawalController::class, 'edit'])
        ->name('admin.wallet.withdrawals.edit');

    Route::post('withdrawals/{id}/complete', [WalletWithdrawalController::class, 'complete'])
        ->name('admin.wallet.withdrawals.complete');

    Route::post('withdrawals/{id}/reject', [WalletWithdrawalController::class, 'reject'])
        ->name('admin.wallet.withdrawals.reject');

    /**
     * Reports & Governance Dashboard.
     */
    Route::get('reports', [WalletReportController::class, 'index'])
        ->name('admin.wallet.reports.index');

    /**
     * Withdrawal Methods Management.
     */
    Route::get('withdrawal-methods', [WalletWithdrawalMethodController::class, 'index'])
        ->name('admin.wallet.withdrawal_methods.index');

    Route::post('withdrawal-methods', [WalletWithdrawalMethodController::class, 'store'])
        ->name('admin.wallet.withdrawal_methods.store');

    Route::put('withdrawal-methods/{id}', [WalletWithdrawalMethodController::class, 'update'])
        ->name('admin.wallet.withdrawal_methods.update');

    Route::post('withdrawal-methods/{id}/toggle', [WalletWithdrawalMethodController::class, 'toggle'])
        ->name('admin.wallet.withdrawal_methods.toggle');

    Route::delete('withdrawal-methods/{id}', [WalletWithdrawalMethodController::class, 'destroy'])
        ->name('admin.wallet.withdrawal_methods.destroy');

    /**
     * Settings.
     */
    Route::get('settings', function () {
        return redirect()->route('admin.configuration.index', ['slug' => 'sales', 'slug2' => 'wallet']);
    })->name('admin.wallet.settings.index');
});
