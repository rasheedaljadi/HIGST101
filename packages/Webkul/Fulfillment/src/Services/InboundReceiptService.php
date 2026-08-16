<?php

namespace Webkul\Fulfillment\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Webkul\Fulfillment\Events\HayestStockReceived;
use Webkul\Fulfillment\Models\OrderAllocation;
use Webkul\Fulfillment\Models\PurchaseOrder;
use Webkul\Inventory\Services\InventoryMovementService;

class InboundReceiptService
{
    public function __construct(
        protected InventoryMovementService $inventoryMovementService
    ) {}

    /**
     * Mark an inbound shipment as pending physical receipt/inspection at Hayest warehouse.
     * Does NOT alter physical inventory balances or emit stock events.
     * Idempotent: Does NOT reopen confirmed receipts or overwrite discrepancy reports.
     */
    public function markInboundPending(int $purchaseOrderId, ?int $procurementSessionId = null, ?string $correlationId = null): PurchaseOrder
    {
        $po = PurchaseOrder::findOrFail($purchaseOrderId);

        if ($po->state === PurchaseOrder::STATE_CANCELED) {
            throw new \DomainException("Cannot mark canceled purchase order #{$purchaseOrderId} as inbound pending.");
        }

        // Idempotency: only transition if still not received
        if ($po->receipt_status === 'not_received' || empty($po->receipt_status)) {
            $po->receipt_status = 'inbound_receipt_pending';
            $po->save();

            Log::channel('fulfillment')->info("Purchase order #{$purchaseOrderId} marked as inbound_receipt_pending. Session: {$procurementSessionId}, Correlation: {$correlationId}");
        }

        return $po;
    }

    /**
     * Confirm full physical receipt of goods into Hayest central inventory.
     * 1. Records 'source_receipt' audit trail (no balance change).
     * 2. Executes 'hayest_stock_in' (sole incrementer of product_inventories for hayest_central).
     * 3. Rebinds order_allocations from supplier to warehouse:hayest_central with optimistic locking.
     * 4. Emits HayestStockReceived domain event ONLY AFTER successful transaction commit.
     *
     * @return array{purchase_order: PurchaseOrder, movements: array, allocations: array, already_processed: bool}
     *
     * @throws \Throwable
     */
    public function confirmFullReceipt(
        int $purchaseOrderId,
        int $actorId,
        ?string $notes = null,
        ?string $idempotencyKey = null,
        ?string $correlationId = null
    ): array {
        $idempotencyKey = $idempotencyKey ?: 'inbound_receipt_po_'.$purchaseOrderId.'_'.Str::random(12);

        return DB::transaction(function () use ($purchaseOrderId, $actorId, $notes, $idempotencyKey, $correlationId) {
            /** @var PurchaseOrder $po */
            $po = PurchaseOrder::with(['items.orderItem', 'order'])->lockForUpdate()->findOrFail($purchaseOrderId);

            if ($po->receipt_status === 'full_receipt_confirmed') {
                Log::channel('fulfillment')->warning("Purchase order #{$purchaseOrderId} already confirmed received. Skipping duplicate processing.");

                return [
                    'purchase_order' => $po,
                    'movements' => [],
                    'allocations' => [],
                    'already_processed' => true,
                ];
            }

            if ($po->state === PurchaseOrder::STATE_CANCELED) {
                throw new \DomainException("Cannot confirm receipt for canceled purchase order #{$purchaseOrderId}.");
            }

            $hayestSource = $this->inventoryMovementService->getSourceByCode('hayest_central');
            $recordedMovements = [];
            $reboundAllocations = [];
            $eventsToDispatch = [];

            foreach ($po->items as $poItem) {
                $orderItemId = $poItem->order_item_id;
                $orderItem = $poItem->orderItem;
                $productId = (int) ($orderItem?->product_id ?? $poItem->product_id ?? 0);
                $sku = (string) ($orderItem?->sku ?? $poItem->sku ?? ('PO-ITEM-'.$poItem->id));
                $qty = (int) ($poItem->qty ?? $poItem->quantity ?? 0);

                if ($qty <= 0) {
                    continue;
                }

                // 1. Record 'source_receipt' audit movement (quantity trace only, no stock balance modification)
                $auditMovement = $this->inventoryMovementService->recordMovement([
                    'product_id' => $productId,
                    'sku' => $sku,
                    'movement_type' => 'source_receipt',
                    'quantity' => $qty,
                    'source_inventory_source_id' => null,
                    'target_inventory_source_id' => $hayestSource->id,
                    'order_id' => $po->order_id,
                    'order_item_id' => $orderItemId,
                    'purchase_order_id' => $po->id,
                    'purchase_order_item_id' => $poItem->id,
                    'actor_id' => $actorId,
                    'actor_type' => 'admin',
                    'notes' => $notes ?: "Source receipt audit confirmed for PO #{$po->id}",
                    'idempotency_key' => $idempotencyKey.'_source_receipt_'.$poItem->id,
                    'reference_event' => 'SourceReceiptConfirmed',
                    'job_class' => self::class,
                ]);
                $recordedMovements[] = $auditMovement;

                // 2. Execute 'hayest_stock_in' - physical stock increase in product_inventories
                $stockInMovement = $this->inventoryMovementService->recordHayestStockIn(
                    productId: $productId,
                    sku: $sku,
                    quantity: $qty,
                    targetSourceId: $hayestSource->id,
                    orderId: $po->order_id,
                    orderItemId: $orderItemId,
                    purchaseOrderId: $po->id,
                    purchaseOrderItemId: $poItem->id,
                    idempotencyKey: $idempotencyKey.'_hayest_stock_in_'.$poItem->id,
                    actorId: $actorId,
                    actorType: 'admin',
                    referenceEvent: 'HayestStockReceived',
                    jobClass: self::class,
                    notes: $notes ?: "Hayest central stock received for PO #{$po->id}"
                );
                $recordedMovements[] = $stockInMovement;

                // 3. Rebind order_allocation from supplier to warehouse (hayest_central)
                if ($orderItemId) {
                    $allocations = OrderAllocation::where('order_item_id', $orderItemId)
                        ->where('state', 'reserved')
                        ->lockForUpdate()
                        ->get();

                    foreach ($allocations as $allocation) {
                        $allocation->rebindToWarehouse('hayest_central');
                        $reboundAllocations[] = $allocation;
                    }
                }

                // 4. Prepare HayestStockReceived domain event for post-commit broadcast
                $eventsToDispatch[] = new HayestStockReceived(
                    orderId: (int) $po->order_id,
                    orderItemId: (int) ($orderItemId ?? 0),
                    productId: (int) $productId,
                    quantity: $qty,
                    inventorySourceCode: 'hayest_central',
                    purchaseOrderId: (int) $po->id,
                    purchaseOrderItemId: (int) $poItem->id,
                    idempotencyKey: $idempotencyKey.'_event_'.$poItem->id,
                    correlationId: $correlationId
                );
            }

            // 5. Update purchase order state
            $po->receipt_status = 'full_receipt_confirmed';
            $po->receipt_confirmed_at = now();
            $po->receipt_confirmed_by = $actorId;
            $po->receipt_notes = $notes;
            $po->markSupplierDelivered();

            // 6. Register afterCommit callback so events ONLY dispatch after successful DB commit
            DB::afterCommit(function () use ($eventsToDispatch) {
                foreach ($eventsToDispatch as $event) {
                    Event::dispatch($event);
                }
            });

            Log::channel('fulfillment')->info("Purchase order #{$purchaseOrderId} full receipt confirmed into hayest_central. Movements: ".count($recordedMovements).', Allocations rebound: '.count($reboundAllocations));

            return [
                'purchase_order' => $po,
                'movements' => $recordedMovements,
                'allocations' => $reboundAllocations,
                'already_processed' => false,
            ];
        });
    }

    /**
     * Record a discrepancy, missing quantity, or damaged goods during physical inspection.
     * - Does NOT increase physical stock.
     * - Does NOT emit HayestStockReceived.
     * - Stores structured discrepancy data in receipt_discrepancy_data.
     * - Transitions purchase order to 'needs_manual_review'.
     * - Prevents customer fulfillment dispatch.
     */
    public function recordReceiptDiscrepancy(
        int $purchaseOrderId,
        int $actorId,
        string $reason = 'Damaged or incomplete parcel received',
        int $receivedQty = 0,
        int $missingQty = 0,
        int $damagedQty = 0
    ): PurchaseOrder {
        return DB::transaction(function () use ($purchaseOrderId, $actorId, $reason, $receivedQty, $missingQty, $damagedQty) {
            /** @var PurchaseOrder $po */
            $po = PurchaseOrder::lockForUpdate()->findOrFail($purchaseOrderId);

            if ($po->receipt_status === 'full_receipt_confirmed') {
                throw new \DomainException("Cannot report discrepancy on already fully confirmed receipt for PO #{$purchaseOrderId}.");
            }

            $orderedQty = (int) ($po->items()->sum('qty') ?: 0);

            $po->receipt_status = 'discrepancy_reported';
            $po->receipt_confirmed_by = $actorId;
            $po->receipt_confirmed_at = now();
            $po->receipt_notes = "Discrepancy noted. Ordered: {$orderedQty}, Received: {$receivedQty}, Missing: {$missingQty}, Damaged: {$damagedQty}. Reason: {$reason}";
            $po->receipt_discrepancy_data = [
                'ordered_qty' => $orderedQty,
                'inspected_qty' => $receivedQty + $damagedQty,
                'received_qty' => $receivedQty,
                'missing_qty' => $missingQty,
                'damaged_qty' => $damagedQty,
                'reason' => $reason,
                'actor_id' => $actorId,
                'reported_at' => now()->toIso8601String(),
            ];
            $po->markNeedsReview($reason);

            Log::channel('fulfillment')->warning("Discrepancy recorded for PO #{$purchaseOrderId}: Ordered={$orderedQty}, Received={$receivedQty}, Missing={$missingQty}, Damaged={$damagedQty}. Reason: {$reason}");

            return $po;
        });
    }
}
