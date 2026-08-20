<?php

namespace Webkul\Procurement\Services;

use DomainException;
use Illuminate\Support\Facades\DB;
use Webkul\Procurement\Models\ProcurementAuditLog;
use Webkul\Procurement\Models\ProcurementBatch;
use Webkul\Procurement\Models\SupplierPurchaseOrder;

class ProcurementVarianceApprovalService
{
    /**
     * Approve a detected cost variance for a Supplier Purchase Order.
     *
     * @throws DomainException
     */
    public function approveVariance(int $spoId, int $actorId, ?string $notes = null): SupplierPurchaseOrder
    {
        return DB::transaction(function () use ($spoId, $actorId, $notes) {
            /** @var SupplierPurchaseOrder $spo */
            $spo = SupplierPurchaseOrder::where('id', $spoId)->lockForUpdate()->firstOrFail();

            if ($spo->state !== SupplierPurchaseOrder::STATE_COST_VARIANCE_REVIEW) {
                throw new DomainException("Cannot approve cost variance for order in '{$spo->state}' state.");
            }

            $spo->update([
                'state' => SupplierPurchaseOrder::STATE_SUPPLIER_PROCESSING,
                'payment_state' => 'variance_approved',
                'external_sync_state' => 'supplier_processing',
            ]);

            if ($spo->batch) {
                $hasOtherPendingVariances = SupplierPurchaseOrder::where('batch_id', $spo->batch_id)
                    ->where('state', SupplierPurchaseOrder::STATE_COST_VARIANCE_REVIEW)
                    ->where('id', '!=', $spo->id)
                    ->exists();

                if (! $hasOtherPendingVariances) {
                    $spo->batch->update([
                        'state' => ProcurementBatch::STATE_SUPPLIER_PROCESSING,
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
