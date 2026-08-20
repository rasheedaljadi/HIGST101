<?php

namespace Webkul\Procurement\Services;

use DomainException;
use Illuminate\Support\Facades\DB;
use Webkul\Procurement\Models\ExternalPlatformOrder;
use Webkul\Procurement\Models\ExternalPlatformOrderItem;
use Webkul\Procurement\Models\ProcurementAuditLog;
use Webkul\Procurement\Models\ProcurementBatch;
use Webkul\Procurement\Models\ProcurementCostSnapshot;
use Webkul\Procurement\Models\SupplierPurchaseOrder;

class ProcurementSubmitService
{
    /**
     * Submit an approved batch and its supplier purchase orders to AliExpress.
     *
     * @throws DomainException
     */
    public function submitBatch(int $batchId, int $actorId): ProcurementBatch
    {
        return DB::transaction(function () use ($batchId, $actorId) {
            /** @var ProcurementBatch $batch */
            $batch = ProcurementBatch::where('id', $batchId)->lockForUpdate()->firstOrFail();

            if ($batch->state !== ProcurementBatch::STATE_APPROVED && $batch->state !== ProcurementBatch::STATE_READY_FOR_REVIEW) {
                throw new DomainException("Cannot submit batch in '{$batch->state}' state. Must be approved first.");
            }

            if (strtoupper((string) $batch->currency_code) !== 'USD') {
                throw new DomainException("Batch currency is '{$batch->currency_code}'. V2 strictly allows USD only. Submission halted for manual review.");
            }

            $batch->update([
                'state' => ProcurementBatch::STATE_SUBMITTED_TO_PROVIDER,
            ]);

            foreach ($batch->supplierOrders as $spo) {
                $this->submitSupplierPurchaseOrder($spo->id, $actorId);
            }

            $batch->update([
                'state' => ProcurementBatch::STATE_AWAITING_MANUAL_PAYMENT,
            ]);

            ProcurementAuditLog::create([
                'auditable_type' => ProcurementBatch::class,
                'auditable_id' => $batch->id,
                'action' => 'batch_submitted',
                'actor_id' => $actorId,
                'actor_type' => 'admin',
                'old_state' => ProcurementBatch::STATE_APPROVED,
                'new_state' => ProcurementBatch::STATE_AWAITING_MANUAL_PAYMENT,
                'details' => ['submitted_orders_count' => $batch->supplierOrders->count()],
                'correlation_id' => "batch-{$batch->id}",
            ]);

            return $batch->fresh(['supplierOrders.platformOrders']);
        });
    }

    /**
     * Submit a single Supplier Purchase Order to AliExpress.
     */
    public function submitSupplierPurchaseOrder(int $spoId, int $actorId): SupplierPurchaseOrder
    {
        /** @var SupplierPurchaseOrder $spo */
        $spo = SupplierPurchaseOrder::where('id', $spoId)->lockForUpdate()->firstOrFail();

        // 1. Re-verify price and currency
        if (strtoupper((string) $spo->currency_code) !== 'USD') {
            $spo->update(['state' => SupplierPurchaseOrder::STATE_SUPPLIER_EXCEPTION]);
            throw new DomainException("Supplier order #{$spo->id} has non-USD currency. Flagged for review.");
        }

        // 2. Mock / Provider order dispatch
        $externalOrderId = 'AE-'.now()->format('Ymd').'-'.rand(100000, 999999);

        $platformOrder = ExternalPlatformOrder::create([
            'supplier_purchase_order_id' => $spo->id,
            'provider' => $spo->provider,
            'provider_account_id' => $spo->provider_account_id,
            'supplier_store_id' => $spo->supplier_store_id,
            'external_order_id' => $externalOrderId,
            'raw_status' => 'WAIT_BUYER_PAY',
            'normalized_status' => ExternalPlatformOrder::STATUS_WAIT_BUYER_PAY,
            'currency_code' => 'USD',
            'last_synced_at' => now(),
            'snapshots' => [
                'created_via' => 'ProcurementSubmitService',
                'submitted_at' => now()->toIso8601String(),
                'expected_total' => (float) $spo->expected_total,
            ],
        ]);

        foreach ($spo->items as $item) {
            ExternalPlatformOrderItem::create([
                'external_platform_order_id' => $platformOrder->id,
                'supplier_purchase_order_item_id' => $item->id,
                'external_sku_id' => $item->supplier_sku_id,
                'quantity' => $item->qty_ordered,
                'actual_item_amount' => $item->qty_ordered * $item->expected_unit_cost,
                'actual_shipping_amount' => 0.0000,
                'actual_tax_amount' => 0.0000,
            ]);
        }

        $spo->update([
            'state' => SupplierPurchaseOrder::STATE_AWAITING_MANUAL_PAYMENT,
            'payment_state' => 'awaiting_manual_payment',
            'external_sync_state' => 'order_created_wait_buyer_pay',
        ]);

        // Create immutable pre-submit snapshot
        ProcurementCostSnapshot::create([
            'snapshotable_type' => SupplierPurchaseOrder::class,
            'snapshotable_id' => $spo->id,
            'snapshot_type' => ProcurementCostSnapshot::TYPE_EXPECTED_BEFORE_SUBMIT,
            'items_subtotal' => $spo->expected_items_total,
            'shipping_amount' => $spo->expected_shipping_total,
            'discount_amount' => $spo->expected_discount_total,
            'tax_fee_amount' => 0.0000,
            'total_amount' => $spo->expected_total,
            'currency_code' => 'USD',
            'exchange_rate' => 1.000000,
            'allocation_basis' => 'proportionate_subtotal',
            'breakdown' => [
                'external_order_id' => $externalOrderId,
                'items_count' => $spo->items->count(),
            ],
            'external_reference' => $externalOrderId,
            'actor_id' => $actorId,
            'actor_type' => 'admin',
            'correlation_id' => "spo-{$spo->id}-submit",
            'snapshot_hash' => hash('sha256', "submit-{$spo->id}-{$externalOrderId}-{$spo->expected_total}"),
            'created_at' => now(),
        ]);

        ProcurementAuditLog::create([
            'auditable_type' => SupplierPurchaseOrder::class,
            'auditable_id' => $spo->id,
            'action' => 'supplier_order_submitted',
            'actor_id' => $actorId,
            'actor_type' => 'admin',
            'old_state' => SupplierPurchaseOrder::STATE_DRAFT,
            'new_state' => SupplierPurchaseOrder::STATE_AWAITING_MANUAL_PAYMENT,
            'details' => [
                'external_order_id' => $externalOrderId,
                'expected_total' => $spo->expected_total,
            ],
            'correlation_id' => "spo-{$spo->id}",
        ]);

        return $spo->fresh(['platformOrders.items']);
    }
}
