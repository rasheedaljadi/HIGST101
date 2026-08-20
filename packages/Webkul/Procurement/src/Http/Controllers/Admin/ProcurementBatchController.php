<?php

namespace Webkul\Procurement\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Webkul\Procurement\DataGrids\ProcurementBatchDataGrid;
use Webkul\Procurement\Models\ProcurementBatch;
use Webkul\Procurement\Services\ProcurementBatchService;
use Webkul\Procurement\Services\ProcurementSubmitService;

class ProcurementBatchController extends Controller
{
    public function __construct(
        protected ProcurementBatchService $batchService,
        protected ProcurementSubmitService $submitService
    ) {}

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return datagrid(ProcurementBatchDataGrid::class)->process();
        }

        $counts = [
            'all' => ProcurementBatch::count(),
            'ready_for_review' => ProcurementBatch::where('state', 'ready_for_review')->count(),
            'approved' => ProcurementBatch::where('state', 'approved')->count(),
            'awaiting_manual_payment' => ProcurementBatch::where('state', 'awaiting_manual_payment')->count(),
            'cost_variance_review' => ProcurementBatch::where('state', 'cost_variance_review')->count(),
            'completed' => ProcurementBatch::where('state', 'completed')->count(),
        ];

        return view('procurement::admin.batches.index', compact('counts'));
    }

    public function create()
    {
        $openDemands = $this->batchService->getOpenDemandsQuery()->with(['order', 'orderItem'])->get();

        return view('procurement::admin.batches.create', compact('openDemands'));
    }

    public function preview(Request $request): JsonResponse
    {
        $demandIds = $request->input('demand_ids', []);
        $preview = $this->batchService->previewBatch($demandIds);

        return response()->json($preview);
    }

    public function store(Request $request)
    {
        $request->validate([
            'demand_ids' => 'required|array|min:1',
            'demand_ids.*' => 'integer|exists:procurement_demands,id',
        ]);

        try {
            $batch = $this->batchService->createBatch($request->input('demand_ids'), Auth::id());

            session()->flash('success', trans('procurement::app.messages.batch-created-success', ['number' => $batch->batch_number]));

            return redirect()->route('admin.procurement.batches.view', $batch->id);
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->back()->withInput();
        }
    }

    public function view(int $id)
    {
        $batch = ProcurementBatch::with([
            'demands.order',
            'supplierOrders.items.allocations.demand.order',
            'supplierOrders.platformOrders',
            'costSnapshots',
            'creator',
            'reviewer',
            'approver',
        ])->findOrFail($id);

        return view('procurement::admin.batches.view', compact('batch'));
    }

    public function approve(int $id, Request $request)
    {
        try {
            $batch = $this->batchService->approveBatch($id, (int) Auth::id(), $request->input('notes'));

            session()->flash('success', trans('procurement::app.messages.batch-approved-success'));

            return redirect()->route('admin.procurement.batches.view', $batch->id);
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
            $batch = $this->batchService->rejectBatch($id, (int) Auth::id(), $request->input('reason'));

            session()->flash('success', trans('procurement::app.messages.batch-rejected-success'));

            return redirect()->route('admin.procurement.batches.view', $batch->id);
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->back();
        }
    }

    public function submit(int $id)
    {
        try {
            $batch = $this->submitService->submitBatch($id, (int) Auth::id());

            session()->flash('success', trans('procurement::app.messages.batch-submitted-success'));

            return redirect()->route('admin.procurement.batches.view', $batch->id);
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->back();
        }
    }
}
