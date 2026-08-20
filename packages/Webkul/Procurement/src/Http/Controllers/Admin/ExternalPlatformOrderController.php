<?php

namespace Webkul\Procurement\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Webkul\Procurement\DataGrids\ExternalPlatformOrderDataGrid;
use Webkul\Procurement\Models\ExternalPlatformOrder;
use Webkul\Procurement\Services\AliExpressPollingService;

class ExternalPlatformOrderController extends Controller
{
    public function __construct(
        protected AliExpressPollingService $pollingService
    ) {}

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return datagrid(ExternalPlatformOrderDataGrid::class)->process();
        }

        return view('procurement::admin.platform_orders.index');
    }

    public function sync(int $id)
    {
        try {
            $order = ExternalPlatformOrder::findOrFail($id);
            $this->pollingService->syncOrder($order);

            session()->flash('success', trans('procurement::app.messages.platform-order-synced-success'));

            return redirect()->back();
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->back();
        }
    }
}
