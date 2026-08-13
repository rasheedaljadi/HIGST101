<?php

namespace Webkul\Wallet\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use Webkul\Wallet\DataGrids\WalletPromoDebtsDataGrid;
use Webkul\Wallet\DataGrids\WalletPromotionGrantsDataGrid;
use Webkul\Wallet\DataGrids\WalletPromotionOutboxDataGrid;
use Webkul\Wallet\DataGrids\WalletPromotionUsagesDataGrid;

class WalletPromotionMonitoringController extends Controller
{
    /**
     * Display monitoring overview.
     */
    public function index()
    {
        if (function_exists('bouncer') && ! bouncer()->hasPermission('wallet.promotions.monitoring')) {
            abort(403);
        }

        return view('wallet::admin.monitoring.index');
    }

    /**
     * Display promotion usages DataGrid.
     */
    public function usages()
    {
        if (function_exists('bouncer') && ! bouncer()->hasPermission('wallet.promotions.monitoring')) {
            abort(403);
        }

        if (request()->ajax()) {
            return app(WalletPromotionUsagesDataGrid::class)->toJson();
        }

        return view('wallet::admin.monitoring.usages');
    }

    /**
     * Display promotion grants DataGrid.
     */
    public function grants()
    {
        if (function_exists('bouncer') && ! bouncer()->hasPermission('wallet.promotions.monitoring')) {
            abort(403);
        }

        if (request()->ajax()) {
            return app(WalletPromotionGrantsDataGrid::class)->toJson();
        }

        return view('wallet::admin.monitoring.grants');
    }

    /**
     * Display promo debts DataGrid.
     */
    public function debts()
    {
        if (function_exists('bouncer') && ! bouncer()->hasPermission('wallet.promotions.monitoring')) {
            abort(403);
        }

        if (request()->ajax()) {
            return app(WalletPromoDebtsDataGrid::class)->toJson();
        }

        return view('wallet::admin.monitoring.debts');
    }

    /**
     * Display promotion outbox DataGrid.
     */
    public function outbox()
    {
        if (function_exists('bouncer') && ! bouncer()->hasPermission('wallet.promotions.monitoring')) {
            abort(403);
        }

        if (request()->ajax()) {
            return app(WalletPromotionOutboxDataGrid::class)->toJson();
        }

        return view('wallet::admin.monitoring.outbox');
    }
}
