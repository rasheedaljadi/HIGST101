<?php

namespace Webkul\Procurement\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Webkul\Inventory\Models\InventorySource;
use Webkul\Procurement\DataGrids\SupplierPurchaseOrderDataGrid;
use Webkul\Procurement\Http\Controllers\Admin\Concerns\AuthorizesProcurementActions;
use Webkul\Procurement\Models\SupplierPurchaseOrder;
use Webkul\Procurement\Security\ProcurementAcl;
use Webkul\Procurement\Services\ProcurementInboundReceiptService;

class SupplierOrderController extends Controller
{
    use AuthorizesProcurementActions;

    public function __construct(
        protected ProcurementInboundReceiptService $receiptService
    ) {}

    public function index(Request $request)
    {
        $this->authorizeProcurementAction(ProcurementAcl::PERMISSION_VIEW);

        if ($request->ajax()) {
            return datagrid(SupplierPurchaseOrderDataGrid::class)->process();
        }

        return view('procurement::admin.supplier_orders.index');
    }

    public function view(int $id)
    {
        $this->authorizeProcurementAction(ProcurementAcl::PERMISSION_VIEW);

        $order = SupplierPurchaseOrder::with([
            'batch',
            'items.product',
            'items.allocations.demand.order',
            'platformOrders.items',
            'costSnapshots',
            'manualPaymentConfirmations.admin',
        ])->findOrFail($id);

        $inventorySources = InventorySource::all();

        return view('procurement::admin.supplier_orders.view', compact('order', 'inventorySources'));
    }

    public function receive(int $id, Request $request)
    {
        $this->authorizeProcurementAction(ProcurementAcl::PERMISSION_EXCEPTION_HANDLE);

        $request->validate([
            'lines' => 'required|array|min:1',
            'lines.*.item_id' => 'required|integer',
            'lines.*.qty_good' => 'required|integer|min:0',
            'target_source' => 'required|string',
        ]);

        try {
            $this->receiptService->receiveGoods(
                $id,
                $request->input('lines'),
                (int) Auth::id(),
                $request->input('target_source', 'hayest_dropship_sa')
            );

            session()->flash('success', trans('procurement::app.messages.receipt-processed-success'));

            return redirect()->route('admin.procurement.supplier_orders.view', $id);
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->back();
        }
    }
}
