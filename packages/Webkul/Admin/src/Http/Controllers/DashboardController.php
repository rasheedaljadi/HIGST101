<?php

namespace Webkul\Admin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Webkul\Admin\Helpers\Dashboard;
use Webkul\Inventory\Services\InventoryReportingService;

class DashboardController extends Controller
{
    /**
     * Request param functions
     *
     * @var array
     */
    protected $typeFunctions = [
        'over-all' => 'getOverAllStats',
        'today' => 'getTodayStats',
        'stock-threshold-products' => 'getStockThresholdProducts',
        'total-sales' => 'getSalesStats',
        'top-selling-products' => 'getTopSellingProducts',
        'top-customers' => 'getTopCustomers',
    ];

    /**
     * Create a controller instance.
     *
     * @return void
     */
    public function __construct(protected Dashboard $dashboardHelper) {}

    /**
     * Dashboard page.
     *
     * @return View|JsonResponse
     */
    public function index()
    {
        $admin = auth()->guard('admin')->user();

        // Resolve requested or stored view preference; default is strictly 'simple'
        $viewMode = request()->query('view');

        if (! in_array($viewMode, ['simple', 'advanced'])) {
            $viewMode = session('admin_dashboard_view') ?? ($admin?->dashboard_view ?? 'simple');
        }

        if (! in_array($viewMode, ['simple', 'advanced'])) {
            $viewMode = 'simple';
        }

        session(['admin_dashboard_view' => $viewMode]);

        $data = [
            'startDate' => $this->dashboardHelper->getStartDate(),
            'endDate' => $this->dashboardHelper->getEndDate(),
            'viewMode' => $viewMode,
        ];

        if ($viewMode === 'advanced') {
            $data['advancedData'] = $this->getAdvancedDashboardData();
        }

        return view('admin::dashboard.index')->with($data);
    }

    /**
     * Toggle dashboard view preference via AJAX.
     */
    public function toggleView(): JsonResponse
    {
        $viewMode = request()->input('view');

        if (! in_array($viewMode, ['simple', 'advanced'])) {
            $viewMode = 'simple';
        }

        session(['admin_dashboard_view' => $viewMode]);

        $admin = auth()->guard('admin')->user();
        if ($admin && Schema::hasColumn('admins', 'dashboard_view')) {
            $admin->update(['dashboard_view' => $viewMode]);
        }

        return response()->json([
            'success' => true,
            'view_mode' => $viewMode,
            'message' => trans('admin::app.dashboard.index.view-updated', ['view' => $viewMode]),
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function stats()
    {
        $stats = $this->dashboardHelper->{$this->typeFunctions[request()->query('type')]}();

        return response()->json([
            'statistics' => $stats,
            'date_range' => $this->dashboardHelper->getDateRange(),
        ]);
    }

    /**
     * Gather read-only data for the Advanced Dashboard view.
     */
    protected function getAdvancedDashboardData(): array
    {
        $reportingService = app(InventoryReportingService::class);

        // 1. Executive Summary & Sales Stats
        $salesStats = $this->dashboardHelper->getOverAllStats();
        $totalOrders = DB::table('orders')->count();
        $totalCustomers = DB::table('customers')->count();

        // 2. Supply Chain & Owned Inventory
        $sourcesBalanceReport = $reportingService->getSourcesBalanceReport();
        $ownedStockSum = $sourcesBalanceReport->sum('total_quantity');
        $ownedSkusSum = $sourcesBalanceReport->sum('total_skus');

        $defaultSource = DB::table('inventory_sources')->where('code', 'default')->first();
        $legacyCount = $defaultSource ? DB::table('product_inventories')->where('inventory_source_id', $defaultSource->id)->count() : 0;
        $legacyQty = $defaultSource ? DB::table('product_inventories')->where('inventory_source_id', $defaultSource->id)->sum('qty') : 0;

        $aeSource = DB::table('inventory_sources')->where('code', 'aliexpress_source')->first();
        $aeProjectionCount = $aeSource ? DB::table('product_inventories')->where('inventory_source_id', $aeSource->id)->count() : 0;
        $aeProjectionQty = $aeSource ? DB::table('product_inventories')->where('inventory_source_id', $aeSource->id)->sum('qty') : 0;

        $externalSnapshotsCount = Schema::hasTable('external_availability_snapshots')
            ? DB::table('external_availability_snapshots')->count()
            : 0;

        $staleSnapshotsCount = Schema::hasTable('external_availability_snapshots')
            ? DB::table('external_availability_snapshots')->where('sync_status', 'stale')->count()
            : 0;

        // 3. Order Lifecycle Pipeline
        $ordersPendingProcurement = DB::table('orders')->whereIn('status', ['pending', 'processing'])->count();
        $purchaseOrdersCount = Schema::hasTable('purchase_orders') ? DB::table('purchase_orders')->count() : 0;
        $inboundReceiptsCount = Schema::hasTable('inbound_receipt_manifests') ? DB::table('inbound_receipt_manifests')->count() : 0;
        $transferManifestsCount = Schema::hasTable('inventory_transfer_manifests') ? DB::table('inventory_transfer_manifests')->count() : 0;

        // 4. Delivery Metrics
        $deliveryAssignmentsCount = Schema::hasTable('delivery_assignments') ? DB::table('delivery_assignments')->count() : 0;
        $deliveryPendingCount = Schema::hasTable('delivery_assignments') ? DB::table('delivery_assignments')->where('status', 'assigned')->count() : 0;
        $deliveryInTransitCount = Schema::hasTable('delivery_assignments') ? DB::table('delivery_assignments')->where('status', 'in_transit')->count() : 0;
        $deliveryCompletedCount = Schema::hasTable('delivery_assignments') ? DB::table('delivery_assignments')->where('status', 'delivered')->count() : 0;

        // 5. Financial Ledger & Cash Collections
        $cashCollectionsCount = Schema::hasTable('delivery_cash_collections') ? DB::table('delivery_cash_collections')->count() : 0;
        $cashCollectedSum = Schema::hasTable('delivery_cash_collections') ? DB::table('delivery_cash_collections')->sum('amount') : 0;

        // 6. Quarantine Inventory
        $quarantineYeSource = DB::table('inventory_sources')->where('code', 'hayest_quarantine_ye')->first();
        $quarantineSaSource = DB::table('inventory_sources')->where('code', 'hayest_quarantine_sa')->first();
        $quarantineQtyYe = $quarantineYeSource ? DB::table('product_inventories')->where('inventory_source_id', $quarantineYeSource->id)->sum('qty') : 0;
        $quarantineQtySa = $quarantineSaSource ? DB::table('product_inventories')->where('inventory_source_id', $quarantineSaSource->id)->sum('qty') : 0;

        $salesVal = 0.0;
        if (isset($salesStats['total_sales'])) {
            if (is_array($salesStats['total_sales'])) {
                $salesVal = (float) ($salesStats['total_sales']['total'] ?? 0);
            } else {
                $salesVal = (float) $salesStats['total_sales'];
            }
        }

        return [
            'executive' => [
                'total_sales' => $salesVal,
                'total_orders' => $totalOrders,
                'total_customers' => $totalCustomers,
                'owned_stock_qty' => (float) $ownedStockSum,
            ],
            'pipeline' => [
                'pending_procurement' => $ordersPendingProcurement,
                'purchase_orders' => $purchaseOrdersCount,
                'inbound_receipts' => $inboundReceiptsCount,
                'transfers' => $transferManifestsCount,
                'delivered' => $deliveryCompletedCount,
            ],
            'supply_chain' => [
                'owned_stock_qty' => $ownedStockSum,
                'owned_skus_count' => $ownedSkusSum,
                'legacy_external_count' => $legacyCount,
                'legacy_external_qty' => $legacyQty,
                'virtual_projection_count' => $aeProjectionCount,
                'virtual_projection_qty' => $aeProjectionQty,
                'external_snapshots_count' => $externalSnapshotsCount,
                'stale_snapshots_count' => $staleSnapshotsCount,
                'sources_report' => $sourcesBalanceReport,
            ],
            'delivery' => [
                'total_assignments' => $deliveryAssignmentsCount,
                'pending' => $deliveryPendingCount,
                'in_transit' => $deliveryInTransitCount,
                'completed' => $deliveryCompletedCount,
                'cash_collections_count' => $cashCollectionsCount,
                'cash_collected_sum' => (float) $cashCollectedSum,
            ],
            'financial' => [
                'cash_collected_sum' => (float) $cashCollectedSum,
                'total_sales' => $salesVal,
            ],
            'exceptions' => [
                'stale_snapshots' => $staleSnapshotsCount,
                'quarantine_qty_ye' => $quarantineQtyYe,
                'quarantine_qty_sa' => $quarantineQtySa,
            ],
        ];
    }
}
