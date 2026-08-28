<?php

use Illuminate\Support\Facades\Route;
use Webkul\Admin\Http\Controllers\Reporting\DetailedReportController;

/**
 * Detailed Reports routes (وحدة التقارير التفصيلية).
 */
Route::controller(DetailedReportController::class)->prefix('detailed-reports')->group(function () {
    /**
     * Product Report routes (تقرير المنتجات).
     */
    Route::prefix('products')->group(function () {
        Route::get('', 'products')->name('admin.detailed_reports.products.index');
        Route::get('export', 'exportProducts')->name('admin.detailed_reports.products.export');
        Route::get('{id}/variants', 'productVariants')->name('admin.detailed_reports.products.variants');
    });

    /**
     * Customer Report routes (تقرير العملاء).
     */
    Route::prefix('customers')->group(function () {
        Route::get('', 'customers')->name('admin.detailed_reports.customers.index');
        Route::get('export', 'exportCustomers')->name('admin.detailed_reports.customers.export');
        Route::get('{id}/orders', 'customerOrders')->name('admin.detailed_reports.customers.orders');
    });
});
