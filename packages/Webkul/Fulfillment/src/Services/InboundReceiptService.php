<?php

namespace Webkul\Fulfillment\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Webkul\Fulfillment\Enums\ReceiptItemCondition;
use Webkul\Fulfillment\Enums\TransferStatus;
use Webkul\Fulfillment\Events\HayestStockReceived;
use Webkul\Fulfillment\Models\InboundReceiptManifest;
use Webkul\Fulfillment\Models\InboundReceiptManifestItem;
use Webkul\Fulfillment\Models\InventoryTransferManifest;
use Webkul\Fulfillment\Models\InventoryTransferManifestItem;
use Webkul\Fulfillment\Models\OrderAllocation;
use Webkul\Fulfillment\Models\PurchaseOrder;
use Webkul\Inventory\Models\InventorySource;
use Webkul\Inventory\Services\InventoryMovementService;

class InboundReceiptService
{
    public function __construct(
        protected InventoryMovementService $inventoryMovementService
    ) {}

    /**
     * Process an official inbound physical receipt into Yemen warehouse.
     * Supports:
     * - Dynamic target source (e.g. hayest_dropship_ye or hayest_internal_ye).
     * - Routing damaged items to quarantine source (e.g. hayest_quarantine_ye).
     * - Recording missing items as shortage discrepancies without false stock increments.
     * - Linking with cross-border transfer manifest and updating transfer progress.
     * - Strict idempotency, pessimistic locking, and audit movements.
     *
     * @throws Exception
     */
    public function processInboundReceipt(array $data, int $actorId): InboundReceiptManifest
    {
        $idempotencyKey = $data['idempotency_key'] ?? ('REC_IDEMP_'.Str::upper(Str::random(16)));

        return DB::transaction(function () use ($data, $actorId, $idempotencyKey) {
            // 1. Check idempotency: Return existing receipt if already processed
            $existing = InboundReceiptManifest::where('idempotency_key', $idempotencyKey)
                ->with('items')
                ->first();

            if ($existing) {
                Log::channel('fulfillment')->info("Inbound receipt already exists for idempotency_key {$idempotencyKey}. Skipping duplicate.");

                return $existing;
            }

            // 2. Resolve Destination and Quarantine Sources
            $destinationSource = $this->resolveDestinationSource($data['destination_source_code'] ?? $data['destination_inventory_source_id'] ?? null);
            $quarantineSource = $this->resolveQuarantineSource($data['quarantine_source_code'] ?? $data['quarantine_inventory_source_id'] ?? null);

            $transferManifest = null;
            if (! empty($data['inventory_transfer_manifest_id'])) {
                $transferManifest = InventoryTransferManifest::lockForUpdate()->find($data['inventory_transfer_manifest_id']);
            }

            $receiptNumber = $data['receipt_number'] ?? $this->generateReceiptNumber();

            // 3. Create Inbound Receipt Manifest Header
            $receipt = InboundReceiptManifest::create([
                'receipt_number' => $receiptNumber,
                'idempotency_key' => $idempotencyKey,
                'inventory_transfer_manifest_id' => $transferManifest?->id,
                'external_reference' => $data['external_reference'] ?? null,
                'destination_inventory_source_id' => $destinationSource->id,
                'quarantine_inventory_source_id' => $quarantineSource?->id,
                'status' => 'completed',
                'received_by_admin_id' => $actorId,
                'total_received_good' => 0,
                'total_received_damaged' => 0,
                'total_received_missing' => 0,
                'notes' => $data['notes'] ?? null,
            ]);

            $totalGood = 0;
            $totalDamaged = 0;
            $totalMissing = 0;
            $eventsToDispatch = [];

            // 4. Process Receipt Items
            $items = $data['items'] ?? [];
            if (empty($items)) {
                throw new Exception('Inbound receipt must contain at least one item.');
            }

            foreach ($items as $itemData) {
                $productId = (int) $itemData['product_id'];
                $sku = (string) $itemData['sku'];
                $qtyGood = max(0, (int) ($itemData['qty_good'] ?? 0));
                $qtyDamaged = max(0, (int) ($itemData['qty_damaged'] ?? 0));
                $qtyMissing = max(0, (int) ($itemData['qty_missing'] ?? 0));
                $orderId = $itemData['order_id'] ?? null;
                $orderItemId = $itemData['order_item_id'] ?? null;
                $orderAllocationId = $itemData['order_allocation_id'] ?? null;
                $transferItemId = $itemData['inventory_transfer_manifest_item_id'] ?? null;

                if ($qtyGood === 0 && $qtyDamaged === 0 && $qtyMissing === 0) {
                    continue;
                }

                $totalGood += $qtyGood;
                $totalDamaged += $qtyDamaged;
                $totalMissing += $qtyMissing;

                $condition = ReceiptItemCondition::GOOD->value;
                if ($qtyDamaged > 0 && $qtyGood === 0) {
                    $condition = ReceiptItemCondition::DAMAGED->value;
                } elseif ($qtyMissing > 0 && $qtyGood === 0 && $qtyDamaged === 0) {
                    $condition = ReceiptItemCondition::MISSING->value;
                }

                InboundReceiptManifestItem::create([
                    'inbound_receipt_manifest_id' => $receipt->id,
                    'inventory_transfer_manifest_item_id' => $transferItemId,
                    'product_id' => $productId,
                    'order_id' => $orderId,
                    'order_item_id' => $orderItemId,
                    'order_allocation_id' => $orderAllocationId,
                    'sku' => $sku,
                    'qty_good' => $qtyGood,
                    'qty_damaged' => $qtyDamaged,
                    'qty_missing' => $qtyMissing,
                    'condition' => $condition,
                    'discrepancy_reason' => $itemData['discrepancy_reason'] ?? null,
                ]);

                // 4A. Stock-In Good condition items into destination delivery source
                if ($qtyGood > 0) {
                    $this->inventoryMovementService->recordHayestStockIn(
                        productId: $productId,
                        sku: $sku,
                        quantity: $qtyGood,
                        targetSourceId: $destinationSource->id,
                        orderId: $orderId,
                        orderItemId: $orderItemId,
                        purchaseOrderId: $itemData['purchase_order_id'] ?? null,
                        purchaseOrderItemId: $itemData['purchase_order_item_id'] ?? null,
                        idempotencyKey: $idempotencyKey.'_stock_in_'.$productId.'_'.$sku,
                        actorId: $actorId,
                        actorType: 'admin',
                        referenceEvent: 'InboundStockReceived',
                        jobClass: self::class,
                        notes: (! empty($data['notes'])) ? $data['notes'] : "Inbound stock received into {$destinationSource->code} under Receipt #{$receipt->receipt_number}"
                    );

                    // Rebind order_allocations to destination source
                    if ($orderAllocationId) {
                        $alloc = OrderAllocation::lockForUpdate()->find($orderAllocationId);
                        if ($alloc && $alloc->state === 'reserved') {
                            $alloc->rebindToWarehouse($destinationSource->code);
                        }
                    } elseif ($orderItemId) {
                        $allocations = OrderAllocation::where('order_item_id', $orderItemId)
                            ->where('state', 'reserved')
                            ->lockForUpdate()
                            ->get();

                        foreach ($allocations as $alloc) {
                            $alloc->rebindToWarehouse($destinationSource->code);
                        }
                    }

                    $eventsToDispatch[] = new HayestStockReceived(
                        orderId: (int) ($orderId ?? 0),
                        orderItemId: (int) ($orderItemId ?? 0),
                        productId: $productId,
                        quantity: $qtyGood,
                        inventorySourceCode: $destinationSource->code,
                        purchaseOrderId: (int) ($itemData['purchase_order_id'] ?? 0),
                        purchaseOrderItemId: (int) ($itemData['purchase_order_item_id'] ?? 0),
                        idempotencyKey: $idempotencyKey.'_event_'.$productId,
                        correlationId: $data['correlation_id'] ?? null
                    );
                }

                // 4B. Route Damaged items into Quarantine Source
                if ($qtyDamaged > 0 && $quarantineSource) {
                    $this->inventoryMovementService->recordHayestStockIn(
                        productId: $productId,
                        sku: $sku,
                        quantity: $qtyDamaged,
                        targetSourceId: $quarantineSource->id,
                        orderId: $orderId,
                        orderItemId: $orderItemId,
                        purchaseOrderId: $itemData['purchase_order_id'] ?? null,
                        purchaseOrderItemId: $itemData['purchase_order_item_id'] ?? null,
                        idempotencyKey: $idempotencyKey.'_quarantine_in_'.$productId.'_'.$sku,
                        actorId: $actorId,
                        actorType: 'admin',
                        referenceEvent: 'QuarantineStockReceived',
                        jobClass: self::class,
                        notes: 'Damaged item received during inbound inspection. Routed to quarantine.'
                    );
                }

                // 4C. Update Transfer Manifest Item if linked
                if ($transferItemId) {
                    $trfItem = InventoryTransferManifestItem::lockForUpdate()->find($transferItemId);
                    if ($trfItem) {
                        $trfItem->qty_received_good += $qtyGood;
                        $trfItem->qty_received_damaged += $qtyDamaged;
                        $trfItem->qty_received_missing += $qtyMissing;
                        if ($qtyDamaged > 0 || $qtyMissing > 0) {
                            $trfItem->item_condition = ReceiptItemCondition::DAMAGED->value;
                        }
                        $trfItem->save();
                    }
                }
            }

            // 5. Update Inbound Receipt Manifest Totals
            $receipt->total_received_good = $totalGood;
            $receipt->total_received_damaged = $totalDamaged;
            $receipt->total_received_missing = $totalMissing;
            $receipt->save();

            // 6. Update Transfer Manifest Status if linked
            if ($transferManifest) {
                $transferManifest->received_at = now();
                $transferManifest->received_by_admin_id = $actorId;

                $allShipped = $transferManifest->items()->sum('qty_shipped');
                $allGood = $transferManifest->items()->sum('qty_received_good');
                $allDamaged = $transferManifest->items()->sum('qty_received_damaged');
                $allMissing = $transferManifest->items()->sum('qty_received_missing');

                if ($allDamaged > 0 || $allMissing > 0) {
                    $transferManifest->status = TransferStatus::DISCREPANCY;
                } elseif ($allGood >= $allShipped) {
                    $transferManifest->status = TransferStatus::RECEIVED;
                } else {
                    $transferManifest->status = TransferStatus::PARTIALLY_RECEIVED;
                }
                $transferManifest->save();
            }

            // 7. Dispatch events safely after commit
            DB::afterCommit(function () use ($eventsToDispatch) {
                foreach ($eventsToDispatch as $event) {
                    Event::dispatch($event);
                }
            });

            Log::channel('fulfillment')->info("Completed Inbound Receipt #{$receipt->receipt_number}. Good: {$totalGood}, Damaged: {$totalDamaged}, Missing: {$totalMissing}");

            return $receipt->load('items');
        });
    }

    /**
     * Backward-compatible purchase order full receipt confirmation.
     * Dynamically routes to hayest_dropship_ye (or hayest_central if fallback).
     *
     * @throws \Throwable
     */
    public function confirmFullReceipt(
        int $purchaseOrderId,
        int $actorId,
        ?string $notes = null,
        ?string $idempotencyKey = null,
        ?string $correlationId = null,
        ?string $targetSourceCode = null
    ): array {
        $idempotencyKey = $idempotencyKey ?: 'inbound_receipt_po_'.$purchaseOrderId.'_'.Str::random(12);

        return DB::transaction(function () use ($purchaseOrderId, $actorId, $notes, $idempotencyKey, $correlationId, $targetSourceCode) {
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

            $targetSource = $this->resolveDestinationSource($targetSourceCode);
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

                // 1. Record 'source_receipt' audit movement
                $auditMovement = $this->inventoryMovementService->recordMovement([
                    'product_id' => $productId,
                    'sku' => $sku,
                    'movement_type' => 'source_receipt',
                    'quantity' => $qty,
                    'source_inventory_source_id' => null,
                    'target_inventory_source_id' => $targetSource->id,
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

                // 2. Execute physical stock-in
                $stockInMovement = $this->inventoryMovementService->recordHayestStockIn(
                    productId: $productId,
                    sku: $sku,
                    quantity: $qty,
                    targetSourceId: $targetSource->id,
                    orderId: $po->order_id,
                    orderItemId: $orderItemId,
                    purchaseOrderId: $po->id,
                    purchaseOrderItemId: $poItem->id,
                    idempotencyKey: $idempotencyKey.'_hayest_stock_in_'.$poItem->id,
                    actorId: $actorId,
                    actorType: 'admin',
                    referenceEvent: 'HayestStockReceived',
                    jobClass: self::class,
                    notes: $notes ?: "Physical stock received into {$targetSource->code} for PO #{$po->id}"
                );
                $recordedMovements[] = $stockInMovement;

                // 3. Rebind order_allocation from supplier to target warehouse
                if ($orderItemId) {
                    $allocations = OrderAllocation::where('order_item_id', $orderItemId)
                        ->where('state', 'reserved')
                        ->lockForUpdate()
                        ->get();

                    foreach ($allocations as $allocation) {
                        $allocation->rebindToWarehouse($targetSource->code);
                        $reboundAllocations[] = $allocation;
                    }
                }

                // 4. Prepare event
                $eventsToDispatch[] = new HayestStockReceived(
                    orderId: (int) $po->order_id,
                    orderItemId: (int) ($orderItemId ?? 0),
                    productId: (int) $productId,
                    quantity: $qty,
                    inventorySourceCode: $targetSource->code,
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

            // 6. Register afterCommit callback
            DB::afterCommit(function () use ($eventsToDispatch) {
                foreach ($eventsToDispatch as $event) {
                    Event::dispatch($event);
                }
            });

            Log::channel('fulfillment')->info("Purchase order #{$purchaseOrderId} receipt confirmed into {$targetSource->code}.");

            return [
                'purchase_order' => $po,
                'movements' => $recordedMovements,
                'allocations' => $reboundAllocations,
                'already_processed' => false,
            ];
        });
    }

    /**
     * Mark an inbound shipment as pending physical receipt/inspection.
     */
    public function markInboundPending(int $purchaseOrderId, ?int $procurementSessionId = null, ?string $correlationId = null): PurchaseOrder
    {
        $po = PurchaseOrder::findOrFail($purchaseOrderId);

        if ($po->state === PurchaseOrder::STATE_CANCELED) {
            throw new \DomainException("Cannot mark canceled purchase order #{$purchaseOrderId} as inbound pending.");
        }

        if ($po->receipt_status === 'not_received' || empty($po->receipt_status)) {
            $po->receipt_status = 'inbound_receipt_pending';
            $po->save();

            Log::channel('fulfillment')->info("Purchase order #{$purchaseOrderId} marked as inbound_receipt_pending. Session: {$procurementSessionId}, Correlation: {$correlationId}");
        }

        return $po;
    }

    /**
     * Record a discrepancy, missing quantity, or damaged goods during physical inspection.
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

    /**
     * Resolve destination source model by code or id.
     */
    protected function resolveDestinationSource(string|int|null $source): InventorySource
    {
        if (is_numeric($source)) {
            return InventorySource::findOrFail($source);
        }

        if (is_string($source) && ! empty($source)) {
            $src = InventorySource::where('code', $source)->first();
            if ($src) {
                return $src;
            }
        }

        // Default canonical delivery source
        $defaultSource = InventorySource::where('code', 'hayest_dropship_ye')->first()
            ?: InventorySource::where('code', 'hayest_internal_ye')->first()
            ?: InventorySource::where('code', 'hayest_central')->first();

        if (! $defaultSource) {
            throw new Exception('No valid Yemen destination inventory source found.');
        }

        return $defaultSource;
    }

    /**
     * Resolve quarantine source model.
     */
    protected function resolveQuarantineSource(string|int|null $source): ?InventorySource
    {
        if (is_numeric($source)) {
            return InventorySource::find($source);
        }

        if (is_string($source) && ! empty($source)) {
            $src = InventorySource::where('code', $source)->first();
            if ($src) {
                return $src;
            }
        }

        return InventorySource::where('code', 'hayest_quarantine_ye')->first()
            ?: InventorySource::where('code', 'hayest_quarantine_sa')->first();
    }

    /**
     * Generate sequential receipt number.
     */
    protected function generateReceiptNumber(): string
    {
        $prefix = 'REC-SAN-'.date('Ymd').'-';
        $random = Str::upper(Str::random(4));

        return $prefix.$random;
    }
}
