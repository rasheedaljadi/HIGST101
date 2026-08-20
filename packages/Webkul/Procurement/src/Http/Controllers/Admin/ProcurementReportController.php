<?php

namespace Webkul\Procurement\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Webkul\Procurement\Models\ExternalPlatformOrder;
use Webkul\Procurement\Models\ProcurementBatch;
use Webkul\Procurement\Models\ProcurementDemand;
use Webkul\Procurement\Models\SupplierPurchaseOrder;

class ProcurementReportController extends Controller
{
    public function index()
    {
        $metrics = [
            'open_demands_count' => ProcurementDemand::where('state', 'open_for_batching')->count(),
            'open_demands_qty' => ProcurementDemand::where('state', 'open_for_batching')->sum('qty_required_external'),
            'batches_count' => ProcurementBatch::count(),
            'batches_by_state' => ProcurementBatch::select('state', DB::raw('count(*) as count'))
                ->groupBy('state')
                ->pluck('count', 'state')
                ->toArray(),
            'total_expected_cost' => (float) SupplierPurchaseOrder::sum('expected_total'),
            'total_actual_cost' => (float) SupplierPurchaseOrder::sum('actual_total'),
            'total_cost_variance' => (float) SupplierPurchaseOrder::sum('cost_variance_amount'),
            'delayed_orders_count' => ExternalPlatformOrder::where('normalized_status', 'processing')
                ->where('created_at', '<', now()->subDays(3))
                ->count(),
            'uncollected_cod_total' => (float) DB::table('orders')
                ->leftJoin('order_payment', 'orders.id', '=', 'order_payment.order_id')
                ->where('order_payment.method', 'cashondelivery')
                ->where('orders.status', '!=', 'canceled')
                ->whereDoesntHave('invoices', function ($q) {
                    $q->where('state', 'paid');
                })
                ->sum('orders.grand_total'),
        ];

        return view('procurement::admin.reports.index', compact('metrics'));
    }
}
