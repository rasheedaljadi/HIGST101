<?php

namespace Webkul\Procurement\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Webkul\Procurement\DataGrids\ExternalPlatformOrderDataGrid;
use Webkul\Procurement\Http\Controllers\Admin\Concerns\AuthorizesProcurementActions;
use Webkul\Procurement\Models\ExternalPlatformOrder;
use Webkul\Procurement\Security\ProcurementAcl;
use Webkul\Procurement\Services\AliExpressPollingService;

class ExternalPlatformOrderController extends Controller
{
    use AuthorizesProcurementActions;

    public function __construct(
        protected AliExpressPollingService $pollingService
    ) {}

    public function index(Request $request)
    {
        $this->authorizeProcurementAction(ProcurementAcl::PERMISSION_VIEW);

        if ($request->ajax()) {
            return datagrid(ExternalPlatformOrderDataGrid::class)->process();
        }

        return view('procurement::admin.platform_orders.index');
    }

    public function sync(int $id)
    {
        $this->authorizeProcurementAction(ProcurementAcl::PERMISSION_SUBMIT);

        try {
            $order = ExternalPlatformOrder::findOrFail($id);
            $this->pollingService->syncOrder($order);

            $message = trans('procurement::app.messages.platform-order-synced-success');

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'message' => $message,
                ]);
            }

            session()->flash('success', $message);

            return redirect()->back();
        } catch (Exception $e) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 400);
            }

            session()->flash('error', $e->getMessage());

            return redirect()->back();
        }
    }
}
