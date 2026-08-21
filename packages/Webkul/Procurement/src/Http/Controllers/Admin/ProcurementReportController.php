<?php

namespace Webkul\Procurement\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Webkul\Procurement\Http\Controllers\Admin\Concerns\AuthorizesProcurementActions;
use Webkul\Procurement\Models\ExternalPlatformOrder;
use Webkul\Procurement\Models\ProcurementBatch;
use Webkul\Procurement\Models\ProcurementDemand;
use Webkul\Procurement\Models\SupplierPurchaseOrder;
use Webkul\Procurement\Security\ProcurementAcl;
use Webkul\Sales\Models\Order;

class ProcurementReportController extends Controller
{
    use AuthorizesProcurementActions;

    public function index()
    {
        $this->authorizeProcurementAction(ProcurementAcl::PERMISSION_REPORTS_VIEW);

        $canViewCost = ProcurementAcl::canViewCost();

        $metrics = [
            'open_demands_count' => ProcurementDemand::where('state', 'open_for_batching')->count(),
            'open_demands_qty' => (int) ProcurementDemand::where('state', 'open_for_batching')->sum('qty_required_external'),
            'batches_count' => ProcurementBatch::count(),
            'batches_by_state' => ProcurementBatch::select('state', DB::raw('count(*) as count'))
                ->groupBy('state')
                ->pluck('count', 'state')
                ->toArray(),
            'total_expected_cost' => $canViewCost ? (float) SupplierPurchaseOrder::sum('expected_total') : null,
            'total_actual_cost' => $canViewCost ? (float) SupplierPurchaseOrder::sum('actual_total') : null,
            'total_cost_variance' => $canViewCost ? (float) SupplierPurchaseOrder::sum('cost_variance_amount') : null,
            'delayed_orders_count' => ExternalPlatformOrder::where('normalized_status', 'processing')
                ->where('created_at', '<', now()->subDays(3))
                ->count(),
            'uncollected_cod_total' => $canViewCost ? (float) Order::query()
                ->whereHas('payment', function ($q) {
                    $q->where('method', 'cashondelivery');
                })
                ->where('status', '!=', 'canceled')
                ->whereDoesntHave('invoices', function ($q) {
                    $q->where('state', 'paid');
                })
                ->sum('grand_total') : null,
            'cost_view_permitted' => $canViewCost,
        ];

        return view('procurement::admin.reports.index', compact('metrics'));
    }
}
