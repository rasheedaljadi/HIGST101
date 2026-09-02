<?php

namespace Webkul\Procurement\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Webkul\Inventory\Models\InventorySource;
use Webkul\Procurement\DataGrids\SupplierPurchaseOrderDataGrid;
use Webkul\Procurement\Http\Controllers\Admin\Concerns\AuthorizesProcurementActions;
use Webkul\Procurement\Models\SupplierPurchaseOrder;
use Webkul\Procurement\Security\ProcurementAcl;
use Webkul\Procurement\Services\ProcurementInboundReceiptService;
use Webkul\Procurement\Services\ProcurementOrderCancellationService;
use Webkul\Procurement\Services\ProcurementSubmitService;

class SupplierOrderController extends Controller
{
    use AuthorizesProcurementActions;

    public function __construct(
        protected ProcurementInboundReceiptService $receiptService,
        protected ProcurementOrderCancellationService $cancellationService
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

        $inventorySources = InventorySource::whereIn('code', [
            'hayest_dropship_sa',
            'hayest_quarantine_sa',
        ])->get();

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
                $this->resolveAdminActorId(),
                $request->input('target_source', 'hayest_dropship_sa')
            );

            session()->flash('success', trans('procurement::app.messages.receipt-processed-success'));

            return redirect()->route('admin.procurement.supplier_orders.view', $id);
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->back();
        }
    }

    public function cancel(Request $request, int $id)
    {
        $this->authorizeProcurementAction(ProcurementAcl::PERMISSION_SUBMIT);

        try {
            $this->cancellationService->cancelSupplierOrder(
                $id,
                $this->resolveAdminActorId(),
                $request->input('reason', 'Cancelled by administrator from dashboard')
            );

            $message = trans('procurement::app.messages.order-cancelled-success');

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'message' => $message,
                ]);
            }

            session()->flash('success', $message);

            return redirect()->back();
        } catch (Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 400);
            }

            session()->flash('error', $e->getMessage());

            return redirect()->back();
        }
    }

    public function submit(int $id)
    {
        $this->authorizeProcurementAction(ProcurementAcl::PERMISSION_SUBMIT);

        try {
            $submitService = app(ProcurementSubmitService::class);
            $submitService->submitSupplierPurchaseOrder($id, $this->resolveAdminActorId());

            session()->flash('success', trans('procurement::app.messages.batch-submitted-success') ?: 'تم إرسال أمر الشراء بنجاح إلى علي إكسبرس.');

            return redirect()->route('admin.procurement.supplier_orders.view', $id);
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->back();
        }
    }
}
