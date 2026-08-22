<?php

namespace Webkul\Admin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Webkul\Admin\Helpers\Dashboard;
use Webkul\Admin\Services\HayestDashboardAggregationService;

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
            $data['advancedData'] = $this->getAdvancedDashboardData([
                'start_date' => $data['startDate'],
                'end_date' => $data['endDate'],
            ]);
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
    protected function getAdvancedDashboardData(array $filters = []): array
    {
        return app(HayestDashboardAggregationService::class)->getAdvancedData($filters);
    }
}
