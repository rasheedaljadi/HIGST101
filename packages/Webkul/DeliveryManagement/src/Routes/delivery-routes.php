<?php

use Illuminate\Support\Facades\Route;
use Webkul\DeliveryManagement\Http\Controllers\AdminDeliveryController;
use Webkul\DeliveryManagement\Http\Controllers\DeliveryAgentController;

Route::group(['middleware' => ['web', 'admin']], function () {
    // Courier & Point Agent Responsive Interface
    Route::prefix('delivery')->as('delivery.')->group(function () {
        Route::get('/', [DeliveryAgentController::class, 'index'])->name('index');
        Route::get('/assignments/{id}', [DeliveryAgentController::class, 'show'])->name('show');
        Route::post('/assignments/{id}/start', [DeliveryAgentController::class, 'startDelivery'])->name('start');
        Route::post('/assignments/{id}/arrived-point', [DeliveryAgentController::class, 'confirmArrivalAtPoint'])->name('arrived_point');
        Route::post('/assignments/{id}/delivered', [DeliveryAgentController::class, 'confirmCustomerDelivery'])->name('delivered');
        Route::post('/assignments/{id}/fail', [DeliveryAgentController::class, 'recordFailure'])->name('fail');
    });

    // Supervisor & Operations Admin Endpoints
    Route::prefix('admin/delivery')->as('admin.delivery.')->group(function () {
        Route::get('/assignments', [AdminDeliveryController::class, 'index'])->name('assignments.index');
        Route::post('/assignments/{id}/assign', [AdminDeliveryController::class, 'assign'])->name('assignments.assign');
        Route::post('/assignments/{id}/handoff', [AdminDeliveryController::class, 'handoff'])->name('assignments.handoff');
        Route::post('/assignments/{id}/return', [AdminDeliveryController::class, 'returnToHayest'])->name('assignments.return');
    });
});
