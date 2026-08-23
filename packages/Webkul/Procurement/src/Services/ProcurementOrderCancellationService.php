<?php

namespace Webkul\Procurement\Services;

use DomainException;
use Illuminate\Support\Facades\DB;
use Webkul\Procurement\Models\ExternalPlatformOrder;
use Webkul\Procurement\Models\ProcurementAuditLog;
use Webkul\Procurement\Models\ProcurementBatch;
use Webkul\Procurement\Models\ProcurementDemand;
use Webkul\Procurement\Models\ProcurementDemandAllocation;
use Webkul\Procurement\Models\SupplierPurchaseOrder;
use Webkul\Procurement\Security\ProcurementAcl;

class ProcurementOrderCancellationService
{
    /**
     * Cancel a Supplier Purchase Order and release allocated customer demands.
     *
     * @throws DomainException
     */
    public function cancelSupplierOrder(int $spoId, int $actorId, string $reason = 'Cancelled by administrator'): SupplierPurchaseOrder
    {
        ProcurementAcl::authorizeActor($actorId, ProcurementAcl::PERMISSION_SUBMIT);

        return DB::transaction(function () use ($spoId, $actorId, $reason) {
            /** @var SupplierPurchaseOrder $spo */
            $spo = SupplierPurchaseOrder::where('id', $spoId)->lockForUpdate()->firstOrFail();

            $nonCancellableStates = [
                SupplierPurchaseOrder::STATE_CANCELLED,
                SupplierPurchaseOrder::STATE_SUPPLIER_SHIPPED,
                SupplierPurchaseOrder::STATE_CLOSED,
            ];

            if (in_array($spo->state, $nonCancellableStates, true)) {
                throw new DomainException("لا يمكن إلغاء أمر الشراء وهو في حالة '{$spo->state}'.");
            }

            $oldState = $spo->state;

            // 1. Update SPO status
            $spo->update([
                'state' => SupplierPurchaseOrder::STATE_CANCELLED,
                'payment_state' => 'cancelled',
                'external_sync_state' => 'cancelled',
                'active_fingerprint' => null,
            ]);

            // 2. Update linked External Platform Orders
            foreach ($spo->platformOrders as $platformOrder) {
                $platformOrder->update([
                    'normalized_status' => ExternalPlatformOrder::STATUS_CANCELLED,
                ]);
            }

            // 3. Release item allocations and restore demands
            foreach ($spo->items as $item) {
                $allocations = ProcurementDemandAllocation::where('supplier_purchase_order_item_id', $item->id)->get();

                foreach ($allocations as $allocation) {
                    $releasedQty = (int) ($allocation->qty_allocated > 0 ? $allocation->qty_allocated : $allocation->qty_cancelled);

                    $allocation->update([
                        'state' => ProcurementDemandAllocation::STATE_CANCELLED,
                        'qty_cancelled' => $releasedQty,
                        'qty_allocated' => 0,
                    ]);

                    $demand = $allocation->demand;
                    if ($demand) {
                        $newQtyBatched = max(0, $demand->qty_batched - $releasedQty);
                        $demand->update([
                            'qty_batched' => $newQtyBatched,
                            'state' => $newQtyBatched == 0 ? ProcurementDemand::STATE_OPEN_FOR_BATCHING : $demand->state,
                        ]);
                    }
                }
            }

            // 4. Update Batch status if all SPOs in the batch are cancelled or closed
            if ($spo->batch) {
                $activeSpoCount = $spo->batch->supplierOrders()
                    ->whereNotIn('state', [
                        SupplierPurchaseOrder::STATE_CANCELLED,
                        SupplierPurchaseOrder::STATE_CLOSED,
                    ])
                    ->count();

                if ($activeSpoCount === 0) {
                    $spo->batch->update([
                        'state' => ProcurementBatch::STATE_CANCELLED,
                    ]);
                }
            }

            // 5. Audit Log
            ProcurementAuditLog::create([
                'auditable_type' => SupplierPurchaseOrder::class,
                'auditable_id' => $spo->id,
                'action' => 'supplier_order_cancelled_by_admin',
                'actor_id' => $actorId,
                'actor_type' => 'admin',
                'old_state' => $oldState,
                'new_state' => SupplierPurchaseOrder::STATE_CANCELLED,
                'details' => ['reason' => $reason],
                'correlation_id' => "spo-{$spo->id}-admin-cancel",
            ]);

            return $spo->fresh();
        });
    }

    /**
     * Cancel an External Platform Order (AliExpress) and its parent SPO.
     *
     * @throws DomainException
     */
    public function cancelPlatformOrder(int $platformOrderId, int $actorId, string $reason = 'Cancelled by administrator'): ExternalPlatformOrder
    {
        $platformOrder = ExternalPlatformOrder::findOrFail($platformOrderId);

        if ($platformOrder->supplier_purchase_order_id) {
            $this->cancelSupplierOrder($platformOrder->supplier_purchase_order_id, $actorId, $reason);
        } else {
            $platformOrder->update([
                'normalized_status' => ExternalPlatformOrder::STATUS_CANCELLED,
            ]);
        }

        return $platformOrder->fresh();
    }
}
