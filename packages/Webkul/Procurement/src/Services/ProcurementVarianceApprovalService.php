<?php

namespace Webkul\Procurement\Services;

use DomainException;
use Illuminate\Support\Facades\DB;
use Webkul\Procurement\Models\ProcurementAuditLog;
use Webkul\Procurement\Models\ProcurementBatch;
use Webkul\Procurement\Models\SupplierPurchaseOrder;
use Webkul\Procurement\Security\ProcurementAcl;

class ProcurementVarianceApprovalService
{
    /**
     * Approve a detected cost variance for a Supplier Purchase Order.
     *
     * @throws DomainException
     */
    public function approveVariance(int $spoId, int $actorId, ?string $notes = null): SupplierPurchaseOrder
    {
        ProcurementAcl::authorizeActor($actorId, ProcurementAcl::PERMISSION_VARIANCE_APPROVE);

        return DB::transaction(function () use ($spoId, $actorId, $notes) {
            /** @var SupplierPurchaseOrder $spo */
            $spo = SupplierPurchaseOrder::where('id', $spoId)->lockForUpdate()->firstOrFail();

            if ($spo->state !== SupplierPurchaseOrder::STATE_COST_VARIANCE_REVIEW) {
                throw new DomainException("Cannot approve cost variance for order in '{$spo->state}' state.");
            }

            $varianceAmount = (float) ($spo->cost_variance_amount ?? 0.0);
            $newExpectedTotal = (float) $spo->expected_total + $varianceAmount;

            // Update items unit costs to reflect the approved live cost
            $spo->load('items');
            /** @var ProcurementSubmitService $submitService */
            $submitService = app(ProcurementSubmitService::class);

            foreach ($spo->items as $item) {
                $liveCost = $submitService->fetchLiveSkuCost($spo, $item);
                if ($liveCost !== null && $liveCost > 0) {
                    $item->update(['expected_unit_cost' => $liveCost]);
                } elseif ($item->qty_ordered > 0 && $varianceAmount != 0) {
                    $itemVariancePerUnit = $varianceAmount / $item->qty_ordered;
                    $item->update([
                        'expected_unit_cost' => (float) $item->expected_unit_cost + $itemVariancePerUnit,
                    ]);
                }
            }

            // Determine correct post-approval state based on whether order was already submitted or pre-submission
            $hasLivePlatformOrder = $spo->platformOrders()
                ->whereNotNull('external_order_id')
                ->where('external_order_id', '!=', '')
                ->exists();

            $nextSpoState = $hasLivePlatformOrder
                ? SupplierPurchaseOrder::STATE_SUPPLIER_PROCESSING
                : SupplierPurchaseOrder::STATE_READY_TO_SUBMIT;

            $nextBatchState = $hasLivePlatformOrder
                ? ProcurementBatch::STATE_SUPPLIER_PROCESSING
                : ProcurementBatch::STATE_APPROVED;

            $spo->update([
                'state' => $nextSpoState,
                'expected_items_total' => $newExpectedTotal,
                'expected_total' => $newExpectedTotal,
                'cost_variance_amount' => 0.0000,
                'payment_state' => $hasLivePlatformOrder ? 'variance_approved' : $spo->payment_state,
                'external_sync_state' => $hasLivePlatformOrder ? 'supplier_processing' : $spo->external_sync_state,
            ]);

            if ($spo->batch) {
                $hasOtherPendingVariances = SupplierPurchaseOrder::where('batch_id', $spo->batch_id)
                    ->where('state', SupplierPurchaseOrder::STATE_COST_VARIANCE_REVIEW)
                    ->where('id', '!=', $spo->id)
                    ->exists();

                if (! $hasOtherPendingVariances) {
                    $spo->batch->update([
                        'state' => $nextBatchState,
                        'expected_total_cost' => (float) $spo->batch->expected_total_cost + $varianceAmount,
                    ]);
                }
            }

            ProcurementAuditLog::create([
                'auditable_type' => SupplierPurchaseOrder::class,
                'auditable_id' => $spo->id,
                'action' => 'cost_variance_approved',
                'actor_id' => $actorId,
                'actor_type' => 'admin',
                'old_state' => SupplierPurchaseOrder::STATE_COST_VARIANCE_REVIEW,
                'new_state' => SupplierPurchaseOrder::STATE_SUPPLIER_PROCESSING,
                'details' => [
                    'variance_amount' => $spo->cost_variance_amount,
                    'notes' => $notes,
                ],
                'correlation_id' => "spo-{$spo->id}-var-appr",
            ]);

            return $spo->fresh();
        });
    }

    /**
     * Reject a cost variance.
     *
     * @throws DomainException
     */
    public function rejectVariance(int $spoId, int $actorId, string $reason): SupplierPurchaseOrder
    {
        ProcurementAcl::authorizeActor($actorId, ProcurementAcl::PERMISSION_VARIANCE_APPROVE);

        return DB::transaction(function () use ($spoId, $actorId, $reason) {
            /** @var SupplierPurchaseOrder $spo */
            $spo = SupplierPurchaseOrder::where('id', $spoId)->lockForUpdate()->firstOrFail();

            if ($spo->state !== SupplierPurchaseOrder::STATE_COST_VARIANCE_REVIEW) {
                throw new DomainException("Cannot reject cost variance for order in '{$spo->state}' state.");
            }

            $spo->update([
                'state' => SupplierPurchaseOrder::STATE_SUPPLIER_EXCEPTION,
                'payment_state' => 'variance_rejected',
            ]);

            ProcurementAuditLog::create([
                'auditable_type' => SupplierPurchaseOrder::class,
                'auditable_id' => $spo->id,
                'action' => 'cost_variance_rejected',
                'actor_id' => $actorId,
                'actor_type' => 'admin',
                'old_state' => SupplierPurchaseOrder::STATE_COST_VARIANCE_REVIEW,
                'new_state' => SupplierPurchaseOrder::STATE_SUPPLIER_EXCEPTION,
                'details' => [
                    'variance_amount' => $spo->cost_variance_amount,
                    'reason' => $reason,
                ],
                'correlation_id' => "spo-{$spo->id}-var-rej",
            ]);

            return $spo->fresh();
        });
    }
}
