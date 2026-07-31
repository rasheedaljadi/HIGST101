<?php

use Illuminate\Support\Facades\Route;
use Webkul\OfflinePayments\Http\Controllers\Admin\OfflinePaymentAccountController;

Route::group(['middleware' => ['web', 'admin'], 'prefix' => config('app.admin_url')], function () {
    Route::prefix('settings/offline-accounts')->group(function () {
        Route::get('', [OfflinePaymentAccountController::class, 'index'])->name('admin.settings.offline_accounts.index');
        Route::get('create', [OfflinePaymentAccountController::class, 'create'])->name('admin.settings.offline_accounts.create');
        Route::post('create', [OfflinePaymentAccountController::class, 'store'])->name('admin.settings.offline_accounts.store');
        Route::get('edit/{id}', [OfflinePaymentAccountController::class, 'edit'])->name('admin.settings.offline_accounts.edit');
        Route::put('edit/{id}', [OfflinePaymentAccountController::class, 'update'])->name('admin.settings.offline_accounts.update');
        Route::delete('delete/{id}', [OfflinePaymentAccountController::class, 'destroy'])->name('admin.settings.offline_accounts.delete');
        Route::post('update-status/{id}', [OfflinePaymentAccountController::class, 'updateStatus'])->name('admin.settings.offline_accounts.update_status');
    });
});
