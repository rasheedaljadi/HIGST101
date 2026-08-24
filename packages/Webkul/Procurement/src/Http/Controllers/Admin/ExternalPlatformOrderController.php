<?php

namespace Webkul\Procurement\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Webkul\Procurement\DataGrids\ExternalPlatformOrderDataGrid;
use Webkul\Procurement\Http\Controllers\Admin\Concerns\AuthorizesProcurementActions;
use Webkul\Procurement\Models\ExternalPlatformOrder;
use Webkul\Procurement\Models\ProcurementBatchDemand;
use Webkul\Procurement\Models\ProcurementDemand;
use Webkul\Procurement\Models\ProcurementDemandAllocation;
use Webkul\Procurement\Security\ProcurementAcl;
use Webkul\Procurement\Services\AliExpressPollingService;
use Webkul\Procurement\Services\ProcurementBatchService;
use Webkul\Procurement\Services\ProcurementOrderCancellationService;
use Webkul\Procurement\Services\ProcurementSubmitService;

class ExternalPlatformOrderController extends Controller
{
    use AuthorizesProcurementActions;

    public function __construct(
        protected AliExpressPollingService $pollingService,
        protected ProcurementOrderCancellationService $cancellationService,
        protected ProcurementBatchService $batchService,
        protected ProcurementSubmitService $submitService
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

    public function reorder(Request $request, int $id)
    {
        $this->authorizeProcurementAction(ProcurementAcl::PERMISSION_SUBMIT);

        try {
            $order = ExternalPlatformOrder::findOrFail($id);
            $spo = $order->supplierPurchaseOrder;

            $demandIds = [];
            if ($spo) {
                // 1. Check allocations
                $spoItemIds = $spo->items()->pluck('id');
                $allocations = ProcurementDemandAllocation::whereIn('supplier_purchase_order_item_id', $spoItemIds)->get();
                foreach ($allocations as $alloc) {
                    $demand = $alloc->demand;
                    if ($demand) {
                        $demandIds[] = $demand->id;
                    }
                }

                // 2. Check batchDemands
                if (empty($demandIds) && $spo->batch_id) {
                    $bDemands = ProcurementBatchDemand::where('batch_id', $spo->batch_id)->get();
                    foreach ($bDemands as $bd) {
                        $demand = $bd->demand;
                        if ($demand) {
                            $demandIds[] = $demand->id;
                        }
                    }
                }

                // 3. Fallback: match open demands for same product/SKU
                if (empty($demandIds)) {
                    foreach ($spo->items as $item) {
                        $matched = ProcurementDemand::where('supplier_product_id', $item->supplier_product_id)
                            ->where('supplier_sku_id', $item->supplier_sku_id)
                            ->pluck('id')
                            ->toArray();
                        $demandIds = array_merge($demandIds, $matched);
                    }
                }
            }

            $demandIds = array_values(array_unique($demandIds));

            if (empty($demandIds)) {
                throw new \DomainException(trans('procurement::app.messages.reorder-no-demands-available'));
            }

            // Ensure any cancelled/stale demand state is reset to open_for_batching
            ProcurementDemand::whereIn('id', $demandIds)->update([
                'qty_batched' => 0,
                'state' => ProcurementDemand::STATE_OPEN_FOR_BATCHING,
            ]);

            $actorId = (int) (auth()->guard('admin')->id() ?: auth()->id()) ?: null;

            // 1. Create a fresh batch for these demands
            $batch = $this->batchService->createBatch($demandIds, $actorId);

            // 2. Approve the batch for submission
            $batch = $this->batchService->approveBatch($batch->id, $actorId);

            // 3. Submit directly to AliExpress API (generates new live unpaid order)
            $submittedBatch = $this->submitService->submitBatch($batch->id, $actorId);

            $newSpo = $submittedBatch->supplierOrders()->first();
            $newPlatformOrder = $newSpo ? ExternalPlatformOrder::where('supplier_purchase_order_id', $newSpo->id)->first() : null;

            if (! $newPlatformOrder || empty($newPlatformOrder->external_order_id) || $newPlatformOrder->normalized_status === ExternalPlatformOrder::STATUS_SUBMISSION_FAILED) {
                $err = $newPlatformOrder?->failure_message ?: 'فشل إنشاء أمر الشراء على علي إكسبرس.';
                throw new \DomainException("فشل إرسال أمر الشراء إلى علي إكسبرس: {$err}");
            }

            $newExtId = $newPlatformOrder->external_order_id;

            // Mark the old order as reordered so reorder button disappears
            $order->update([
                'snapshots' => array_merge($order->snapshots ?? [], [
                    'is_reordered' => true,
                    'reordered_at' => now()->toIso8601String(),
                    'reordered_into_order_id' => $newExtId,
                    'reordered_into_epo_id' => $newPlatformOrder->id,
                ]),
            ]);

            $message = trans('procurement::app.messages.reorder-success-with-id', ['id' => $newExtId]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'message' => $message,
                ]);
            }

            session()->flash('success', $message);

            return redirect()->route('admin.procurement.platform_orders.index');
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

    public function destroy(Request $request, int $id)
    {
        $this->authorizeProcurementAction(ProcurementAcl::PERMISSION_SUBMIT);

        try {
            $order = ExternalPlatformOrder::findOrFail($id);

            DB::transaction(function () use ($order) {
                $order->items()->delete();
                $order->delete();
            });

            $message = trans('procurement::app.messages.platform-order-deleted-success');

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
