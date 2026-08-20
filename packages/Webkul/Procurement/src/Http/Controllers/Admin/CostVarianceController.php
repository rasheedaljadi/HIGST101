<?php

namespace Webkul\Procurement\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Webkul\Procurement\DataGrids\CostVarianceDataGrid;
use Webkul\Procurement\Services\ProcurementVarianceApprovalService;

class CostVarianceController extends Controller
{
    public function __construct(
        protected ProcurementVarianceApprovalService $varianceService
    ) {}

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return datagrid(CostVarianceDataGrid::class)->process();
        }

        return view('procurement::admin.cost_variances.index');
    }

    public function approve(int $id, Request $request)
    {
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
