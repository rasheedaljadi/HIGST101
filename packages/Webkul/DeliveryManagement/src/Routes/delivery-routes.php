<?php

use Illuminate\Support\Facades\Route;
use Webkul\DeliveryManagement\Http\Controllers\Admin\DeliveryAssignmentController;
use Webkul\DeliveryManagement\Http\Controllers\Admin\DeliveryAuditLogController;
use Webkul\DeliveryManagement\Http\Controllers\Admin\DeliveryCourierController;
use Webkul\DeliveryManagement\Http\Controllers\Admin\DeliveryDashboardController;
use Webkul\DeliveryManagement\Http\Controllers\Admin\DeliveryFailureController;
use Webkul\DeliveryManagement\Http\Controllers\Admin\DeliveryGovernorateRuleController;
use Webkul\DeliveryManagement\Http\Controllers\Admin\DeliveryPointController;
use Webkul\DeliveryManagement\Http\Controllers\Admin\DeliverySettlementController;
use Webkul\DeliveryManagement\Http\Controllers\DeliveryAgentController;

Route::group(['middleware' => ['web', 'admin']], function () {
    // Route alias for /admin/delivery
    Route::get(config('app.admin_url').'/delivery', function () {
        $user = auth()->guard('admin')->user();
        if ($user && in_array($user->role?->name, ['Courier', 'PointAgent'])) {
            return redirect()->route('delivery.index');
        }

        return redirect()->route('admin.delivery.dashboard.index');
    })->name('admin.delivery.root');

    // Courier & Point Agent Responsive Interface (available at /delivery and /admin/courier)
    Route::prefix('delivery')->as('delivery.')->group(function () {
        Route::get('/', [DeliveryAgentController::class, 'index'])->name('index');
        Route::get('/assignments/{id}', [DeliveryAgentController::class, 'show'])->name('show');
        Route::post('/assignments/{id}/start', [DeliveryAgentController::class, 'startDelivery'])->name('start');
        Route::post('/assignments/{id}/arrived-point', [DeliveryAgentController::class, 'confirmArrivalAtPoint'])->name('arrived_point');
        Route::post('/assignments/{id}/delivered', [DeliveryAgentController::class, 'confirmCustomerDelivery'])->name('delivered');
        Route::post('/assignments/{id}/fail', [DeliveryAgentController::class, 'recordFailure'])->name('fail');
    });

    Route::prefix(config('app.admin_url').'/courier')->as('admin.courier.')->group(function () {
        Route::get('/', [DeliveryAgentController::class, 'index'])->name('index');
        Route::get('assigned', [DeliveryAgentController::class, 'statusIndex'])->defaults('status', 'assigned')->name('assigned');
        Route::get('picked-up', [DeliveryAgentController::class, 'statusIndex'])->defaults('status', 'picked_up')->name('picked_up');
        Route::get('out-for-delivery', [DeliveryAgentController::class, 'statusIndex'])->defaults('status', 'out_for_delivery')->name('out_for_delivery');
        Route::get('arrived-point', [DeliveryAgentController::class, 'statusIndex'])->defaults('status', 'arrived_at_point')->name('arrived_at_point');
        Route::get('delivered', [DeliveryAgentController::class, 'statusIndex'])->defaults('status', 'delivered')->name('delivered_tasks');
        Route::get('failed', [DeliveryAgentController::class, 'statusIndex'])->defaults('status', 'delivery_failed')->name('failed_tasks');

        Route::get('/assignments/{id}', [DeliveryAgentController::class, 'show'])->name('show');
        Route::post('/assignments/{id}/start', [DeliveryAgentController::class, 'startDelivery'])->name('start');
        Route::post('/assignments/{id}/arrived-point', [DeliveryAgentController::class, 'confirmArrivalAtPoint'])->name('arrived_point');
        Route::post('/assignments/{id}/delivered', [DeliveryAgentController::class, 'confirmCustomerDelivery'])->name('delivered');
        Route::post('/assignments/{id}/fail', [DeliveryAgentController::class, 'recordFailure'])->name('fail');
    });

    // Supervisor & Operations Admin Endpoints
    Route::prefix(config('app.admin_url').'/delivery')->as('admin.delivery.')->group(function () {
        // 1. Dashboard
        Route::get('dashboard', [DeliveryDashboardController::class, 'index'])->name('dashboard.index');

        // 2. Delivery Assignments
        Route::get('assignments', [DeliveryAssignmentController::class, 'index'])->name('assignments.index');
        Route::get('assignments/{id}', [DeliveryAssignmentController::class, 'show'])->name('assignments.show');
        Route::post('assignments/{id}/assign', [DeliveryAssignmentController::class, 'assign'])->name('assignments.assign');
        Route::post('assignments/{id}/handoff', [DeliveryAssignmentController::class, 'handoff'])->name('assignments.handoff');
        Route::post('assignments/{id}/return', [DeliveryAssignmentController::class, 'returnToHayest'])->name('assignments.return');

        // 3. Couriers
        Route::get('couriers', [DeliveryCourierController::class, 'index'])->name('couriers.index');
        Route::get('couriers/create', [DeliveryCourierController::class, 'create'])->name('couriers.create');
        Route::post('couriers', [DeliveryCourierController::class, 'store'])->name('couriers.store');
        Route::get('couriers/{id}/edit', [DeliveryCourierController::class, 'edit'])->name('couriers.edit');
        Route::put('couriers/{id}', [DeliveryCourierController::class, 'update'])->name('couriers.update');
        Route::post('couriers/{id}/toggle', [DeliveryCourierController::class, 'toggle'])->name('couriers.toggle');

        // 4. Delivery Points
        Route::get('points', [DeliveryPointController::class, 'index'])->name('points.index');
        Route::get('points/create', [DeliveryPointController::class, 'create'])->name('points.create');
        Route::post('points', [DeliveryPointController::class, 'store'])->name('points.store');
        Route::get('points/{id}/edit', [DeliveryPointController::class, 'edit'])->name('points.edit');
        Route::put('points/{id}', [DeliveryPointController::class, 'update'])->name('points.update');
        Route::post('points/{id}/toggle', [DeliveryPointController::class, 'toggle'])->name('points.toggle');

        // 5. Governorate Rules
        Route::get('rules', [DeliveryGovernorateRuleController::class, 'index'])->name('rules.index');
        Route::get('rules/{id}/edit', [DeliveryGovernorateRuleController::class, 'edit'])->name('rules.edit');
        Route::put('rules/{id}', [DeliveryGovernorateRuleController::class, 'update'])->name('rules.update');

        // 6. Failures & Returns
        Route::get('failures', [DeliveryFailureController::class, 'index'])->name('failures.index');

        // 7. Settlements
        Route::get('settlements', [DeliverySettlementController::class, 'index'])->name('settlements.index');
        Route::post('settlements/process', [DeliverySettlementController::class, 'process'])->name('settlements.process');

        // 8. Audit Logs
        Route::get('audit-logs', [DeliveryAuditLogController::class, 'index'])->name('audit_logs.index');
    });
});
