<?php

use Illuminate\Support\Facades\Route;
use Webkul\Core\Http\Middleware\NoCacheMiddleware;
use Webkul\Procurement\Http\Controllers\Admin\CostVarianceController;
use Webkul\Procurement\Http\Controllers\Admin\ExceptionController;
use Webkul\Procurement\Http\Controllers\Admin\ExternalPlatformOrderController;
use Webkul\Procurement\Http\Controllers\Admin\ManualPaymentController;
use Webkul\Procurement\Http\Controllers\Admin\ProcurementBatchController;
use Webkul\Procurement\Http\Controllers\Admin\ProcurementDemandController;
use Webkul\Procurement\Http\Controllers\Admin\ProcurementReportController;
use Webkul\Procurement\Http\Controllers\Admin\SupplierOrderController;

Route::group(['middleware' => ['web', 'admin', NoCacheMiddleware::class], 'prefix' => config('app.admin_url').'/dropshipping/procurement-v2'], function () {
    Route::group(['middleware' => ['theme', 'locale', 'currency']], function () {
        // 1. Eligible Demands
        Route::get('demands', [ProcurementDemandController::class, 'index'])->name('admin.procurement.demands.index');

        // 2. Batches
        Route::get('batches', [ProcurementBatchController::class, 'index'])->name('admin.procurement.batches.index');
        Route::get('batches/create', [ProcurementBatchController::class, 'create'])->name('admin.procurement.batches.create');
        Route::post('batches/preview', [ProcurementBatchController::class, 'preview'])->name('admin.procurement.batches.preview');
        Route::post('batches', [ProcurementBatchController::class, 'store'])->name('admin.procurement.batches.store');
        Route::get('batches/view/{id}', [ProcurementBatchController::class, 'view'])->name('admin.procurement.batches.view');
        Route::post('batches/approve/{id}', [ProcurementBatchController::class, 'approve'])->name('admin.procurement.batches.approve');
        Route::post('batches/reject/{id}', [ProcurementBatchController::class, 'reject'])->name('admin.procurement.batches.reject');
        Route::post('batches/submit/{id}', [ProcurementBatchController::class, 'submit'])->name('admin.procurement.batches.submit');

        // 3. Supplier POs
        Route::get('supplier-orders', [SupplierOrderController::class, 'index'])->name('admin.procurement.supplier_orders.index');
        Route::get('supplier-orders/view/{id}', [SupplierOrderController::class, 'view'])->name('admin.procurement.supplier_orders.view');
        Route::post('supplier-orders/receive/{id}', [SupplierOrderController::class, 'receive'])->name('admin.procurement.supplier_orders.receive');

        // 4. AliExpress Platform Orders
        Route::get('platform-orders', [ExternalPlatformOrderController::class, 'index'])->name('admin.procurement.platform_orders.index');
        Route::post('platform-orders/sync/{id}', [ExternalPlatformOrderController::class, 'sync'])->name('admin.procurement.platform_orders.sync');

        // 5. Manual Payment Confirmations
        Route::get('manual-payments', [ManualPaymentController::class, 'index'])->name('admin.procurement.manual_payments.index');
        Route::post('manual-payments', [ManualPaymentController::class, 'store'])->name('admin.procurement.manual_payments.store');

        // 6. Cost Variances & Approvals
        Route::get('cost-variances', [CostVarianceController::class, 'index'])->name('admin.procurement.cost_variances.index');
        Route::post('cost-variances/approve/{id}', [CostVarianceController::class, 'approve'])->name('admin.procurement.cost_variances.approve');
        Route::post('cost-variances/reject/{id}', [CostVarianceController::class, 'reject'])->name('admin.procurement.cost_variances.reject');

        // 7. Exceptions & Reconciliation
        Route::get('exceptions', [ExceptionController::class, 'index'])->name('admin.procurement.exceptions.index');

        // 8. Reports
        Route::get('reports', [ProcurementReportController::class, 'index'])->name('admin.procurement.reports.index');
    });
});
