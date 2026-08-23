<?php

namespace Webkul\Procurement\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Webkul\Procurement\DataGrids\ExternalPlatformOrderDataGrid;
use Webkul\Procurement\Http\Controllers\Admin\Concerns\AuthorizesProcurementActions;
use Webkul\Procurement\Models\ExternalPlatformOrder;
use Webkul\Procurement\Security\ProcurementAcl;
use Webkul\Procurement\Services\AliExpressPollingService;
use Webkul\Procurement\Services\ProcurementOrderCancellationService;

class ExternalPlatformOrderController extends Controller
{
    use AuthorizesProcurementActions;

    public function __construct(
        protected AliExpressPollingService $pollingService,
        protected ProcurementOrderCancellationService $cancellationService
    ) {}

    public function index(Request $request)
    {
        $this->authorizeProcurementAction(ProcurementAcl::PERMISSION_VIEW);

        if ($request->ajax()) {
            return datagrid(ExternalPlatformOrderDataGrid::class)->process();
        }

        $counts = [
            'all' => ExternalPlatformOrder::count(),
            'wait_buyer_pay' => ExternalPlatformOrder::where('normalized_status', ExternalPlatformOrder::STATUS_WAIT_BUYER_PAY)->count(),
            'processing' => ExternalPlatformOrder::whereIn('normalized_status', [
                ExternalPlatformOrder::STATUS_PROCESSING,
                ExternalPlatformOrder::STATUS_PAYMENT_CONFIRMED,
            ])->count(),
            'shipped' => ExternalPlatformOrder::where('normalized_status', ExternalPlatformOrder::STATUS_SHIPPED)->count(),
            'completed' => ExternalPlatformOrder::whereIn('normalized_status', [
                ExternalPlatformOrder::STATUS_COMPLETED,
                ExternalPlatformOrder::STATUS_DELIVERED,
            ])->count(),
            'cancelled' => ExternalPlatformOrder::where('normalized_status', ExternalPlatformOrder::STATUS_CANCELLED)->count(),
        ];

        return view('procurement::admin.platform_orders.index', compact('counts'));
    }

    public function sync(Request $request, int $id)
    {
        $this->authorizeProcurementAction(ProcurementAcl::PERMISSION_SUBMIT);

        try {
            $order = ExternalPlatformOrder::findOrFail($id);
            $this->pollingService->syncOrder($order);

            $message = trans('procurement::app.messages.platform-order-synced-success');

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

    public function cancel(Request $request, int $id)
    {
        $this->authorizeProcurementAction(ProcurementAcl::PERMISSION_SUBMIT);

        try {
            $this->cancellationService->cancelPlatformOrder(
                $id,
                (int) Auth::id(),
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
}
