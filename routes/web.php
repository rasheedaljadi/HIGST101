<?php

use App\Http\Controllers\AliExpress\AliExpressImportController;
use App\Http\Controllers\AliExpress\AliExpressKeysController;
use App\Http\Controllers\AliExpress\AliExpressOAuthController;
use App\Http\Controllers\AliExpress\AliExpressSyncController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| AliExpress Open Platform OAuth Routes
|--------------------------------------------------------------------------
|
| AliExpress only accepts HTTPS callback URLs. The callback route below is
| the value that must be registered (byte-for-byte) as the "Callback URL"
| in the AliExpress Open Platform console.
|
|   Connect : GET /aliexpress/connect
|   Callback: GET /aliexpress/callback   (name: aliexpress.oauth.callback)
|
*/
Route::prefix('aliexpress')->group(function () {
    Route::get('connect', [AliExpressOAuthController::class, 'connect'])
        ->name('aliexpress.oauth.connect');

    Route::get('callback', [AliExpressOAuthController::class, 'callback'])
        ->name('aliexpress.oauth.callback');
});

/*
|--------------------------------------------------------------------------
| AliExpress Drop Shipping Admin Routes
|--------------------------------------------------------------------------
|
| Admin-only screen for importing a single AliExpress product into the
| Bagisto catalog. Sits behind the standard admin auth guard.
|
|   Index: GET  {admin}/dropshipping/import (name: admin.dropshipping.import.index)
|   Store: POST {admin}/dropshipping/import (name: admin.dropshipping.import.store)
|
*/
Route::prefix(config('app.admin_url'))->middleware(['web', 'admin'])->group(function () {
    Route::get('dropshipping/import', [AliExpressImportController::class, 'index'])
        ->name('admin.dropshipping.import.index');

    Route::post('dropshipping/import', [AliExpressImportController::class, 'store'])
        ->name('admin.dropshipping.import.store');

    // Server-Sent Events stream that reports live import progress for the
    // progress bar on the import page.
    Route::get('dropshipping/import/stream', [AliExpressImportController::class, 'stream'])
        ->name('admin.dropshipping.import.stream');

    // Key Management: store/show AliExpress Open Platform credentials.
    Route::get('dropshipping/keys', [AliExpressKeysController::class, 'index'])
        ->name('admin.dropshipping.keys.index');

    Route::post('dropshipping/keys', [AliExpressKeysController::class, 'store'])
        ->name('admin.dropshipping.keys.store');

    // Synchronization Management (User request)
    Route::get('dropshipping/sync', [AliExpressSyncController::class, 'index'])
        ->name('admin.dropshipping.sync.index');

    Route::post('dropshipping/sync/run-single/{id}', [AliExpressSyncController::class, 'runSingle'])
        ->name('admin.dropshipping.sync.run_single');

    Route::post('dropshipping/sync/get-all-syncable', [AliExpressSyncController::class, 'getAllSyncable'])
        ->name('admin.dropshipping.sync.get_all_syncable');

    Route::post('dropshipping/sync/outbox/replay/{id}', [AliExpressSyncController::class, 'replayOutbox'])
        ->name('admin.dropshipping.sync.outbox.replay');

    Route::post('dropshipping/sync/inbox/replay/{id}', [AliExpressSyncController::class, 'replayInbox'])
        ->name('admin.dropshipping.sync.inbox.replay');
});
