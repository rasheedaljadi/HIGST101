<?php

namespace Webkul\Procurement\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Webkul\Procurement\DataGrids\CostVarianceDataGrid;
use Webkul\Procurement\Http\Controllers\Admin\Concerns\AuthorizesProcurementActions;
use Webkul\Procurement\Security\ProcurementAcl;
use Webkul\Procurement\Services\ProcurementVarianceApprovalService;

class CostVarianceController extends Controller
{
    use AuthorizesProcurementActions;

    public function __construct(
        protected ProcurementVarianceApprovalService $varianceService
    ) {}

    public function index(Request $request)
    {
        $this->authorizeProcurementAction(ProcurementAcl::PERMISSION_COST_VIEW);

        if ($request->ajax()) {
            return datagrid(CostVarianceDataGrid::class)->process();
        }

        return view('procurement::admin.cost_variances.index');
    }

    public function approve(int $id, Request $request)
    {
        $this->authorizeProcurementAction(ProcurementAcl::PERMISSION_VARIANCE_APPROVE);

        try {
            $this->varianceService->approveVariance($id, (int) Auth::id(), $request->input('notes'));

            session()->flash('success', trans('procurement::app.messages.variance-approved-success'));

            return redirect()->back();
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->back();
        }
    }

    public function reject(int $id, Request $request)
    {
        $this->authorizeProcurementAction(ProcurementAcl::PERMISSION_VARIANCE_APPROVE);

        $request->validate([
            'reason' => 'required|string|min:3',
        ]);

        try {
            $this->varianceService->rejectVariance($id, (int) Auth::id(), $request->input('reason'));

            session()->flash('success', trans('procurement::app.messages.variance-rejected-success'));

            return redirect()->back();
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->back();
        }
    }
}
