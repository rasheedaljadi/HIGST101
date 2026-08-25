<?php

namespace Webkul\Procurement\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Webkul\Procurement\DataGrids\ProcurementManualPaymentDataGrid;
use Webkul\Procurement\Http\Controllers\Admin\Concerns\AuthorizesProcurementActions;
use Webkul\Procurement\Models\ExternalPlatformOrder;
use Webkul\Procurement\Security\ProcurementAcl;
use Webkul\Procurement\Services\AliExpressPollingService;
use Webkul\Procurement\Services\ProcurementManualPaymentService;

class ManualPaymentController extends Controller
{
    use AuthorizesProcurementActions;

    public function __construct(
        protected ProcurementManualPaymentService $paymentService,
        protected AliExpressPollingService $pollingService
    ) {}

    public function index(Request $request)
    {
        $this->authorizeProcurementAction(ProcurementAcl::PERMISSION_VIEW);

        if ($request->ajax()) {
            return datagrid(ProcurementManualPaymentDataGrid::class)->process();
        }

        $canViewCost = ProcurementAcl::canViewCost();

        return view('procurement::admin.manual_payments.index', compact('canViewCost'));
    }

    public function store(Request $request)
    {
        $this->authorizeProcurementAction(ProcurementAcl::PERMISSION_PAYMENT_CONFIRM);

        $request->validate([
            'supplier_purchase_order_id' => 'required|integer|exists:supplier_purchase_orders,id',
            'external_reference' => 'required|string|min:3',
            'declared_total' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|in:USD',
            'notes' => 'nullable|string',
        ]);

        try {
            $spoId = (int) $request->input('supplier_purchase_order_id');

            $this->paymentService->declarePayment(
                $spoId,
                $this->resolveAdminActorId(),
                $request->input('external_reference'),
                (float) $request->input('declared_total'),
                $request->input('currency', 'USD'),
                null,
                $request->input('notes')
            );

            // Automatically sync the platform order with AliExpress
            try {
                $platformOrder = ExternalPlatformOrder::where('supplier_purchase_order_id', $spoId)->first();
                if ($platformOrder && ! empty($platformOrder->external_order_id)) {
                    $this->pollingService->syncOrder($platformOrder);
                }
            } catch (\Throwable $syncEx) {
                Log::warning("[ManualPayment AutoSync] Failed to sync order for SPO #{$spoId}: {$syncEx->getMessage()}");
            }

            session()->flash('success', trans('procurement::app.messages.payment-declared-success'));

            return redirect()->back();
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->back()->withInput();
        }
    }
}
