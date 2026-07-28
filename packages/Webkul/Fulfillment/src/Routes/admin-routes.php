<?php

use Illuminate\Support\Facades\Route;
use Webkul\Core\Http\Middleware\NoCacheMiddleware;
use Webkul\Fulfillment\Http\Controllers\Admin\FulfillmentController;
use Webkul\Fulfillment\Http\Controllers\WebhookController;

Route::group(['middleware' => ['web', 'admin', NoCacheMiddleware::class], 'prefix' => config('app.admin_url')], function () {
    Route::group(['middleware' => ['theme', 'locale', 'currency']], function () {
        Route::get('dropshipping/finance', [FulfillmentController::class, 'financeIndex'])->name('admin.dropshipping.finance.index');
        Route::get('dropshipping/monitoring', [FulfillmentController::class, 'monitoringIndex'])->name('admin.dropshipping.monitoring.index');
        Route::post('dropshipping/monitoring/reset-circuit', [FulfillmentController::class, 'resetCircuitBreaker'])->name('admin.dropshipping.monitoring.reset-circuit');
    });

    Route::group(['prefix' => 'dropshipping/fulfillment', 'middleware' => ['theme', 'locale', 'currency']], function () {
        // Main view and grids
        Route::get('', [FulfillmentController::class, 'index'])->name('admin.dropshipping.fulfillment.index');
        Route::get('view/{id}', [FulfillmentController::class, 'view'])->name('admin.dropshipping.fulfillment.view');

        // Actions
        Route::post('retry/{id}', [FulfillmentController::class, 'retry'])->name('admin.dropshipping.fulfillment.retry');
        Route::post('cancel/{id}', [FulfillmentController::class, 'cancel'])->name('admin.dropshipping.fulfillment.cancel');
        Route::post('override/{id}', [FulfillmentController::class, 'overrideState'])->name('admin.dropshipping.fulfillment.override');
        Route::post('edit/{id}', [FulfillmentController::class, 'editPo'])->name('admin.dropshipping.fulfillment.edit');
        Route::post('refresh/{id}', [FulfillmentController::class, 'refreshStatus'])->name('admin.dropshipping.fulfillment.refresh');
        Route::post('clear-alert/{id}', [FulfillmentController::class, 'dismissAlert'])->name('admin.dropshipping.fulfillment.clear-alert');

        // Approvals workflow
        Route::post('approve/{id}', [FulfillmentController::class, 'approveRequest'])->name('admin.dropshipping.fulfillment.approve');
        Route::post('reject/{id}', [FulfillmentController::class, 'rejectRequest'])->name('admin.dropshipping.fulfillment.reject');
    });
});

Route::post('api/fulfillment/webhook/{provider}', [WebhookController::class, 'handleWebhook'])
    ->name('fulfillment.webhook');
