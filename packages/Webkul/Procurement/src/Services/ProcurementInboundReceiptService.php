<?php

namespace Webkul\Procurement\Services;

use DomainException;
use Illuminate\Support\Facades\DB;
use Webkul\Inventory\Models\InventorySource;
use Webkul\Procurement\Models\ProcurementAuditLog;
use Webkul\Procurement\Models\ProcurementDemand;
use Webkul\Procurement\Models\ProcurementDemandAllocation;
use Webkul\Procurement\Models\SupplierPurchaseOrder;
use Webkul\Procurement\Models\SupplierPurchaseOrderItem;
use Webkul\Procurement\Security\ProcurementAcl;
use Webkul\Product\Models\ProductInventory;

class ProcurementInboundReceiptService
{
    /**
     * Forbidden inventory sources for receiving imported goods.
     */
    protected const FORBIDDEN_RECEIVING_SOURCES = [
        'hayest_central',
        'default',
        'aliexpress_source',
        'sourcing_staging',
    ];

    /**
     * Process inbound receipt for a Supplier Purchase Order in Saudi Hub (hayest_dropship_sa).
     *
     * @param  array<array{
     *     item_id: int,
     *     qty_good: int,
     *     qty_damaged?: int,
     *     qty_missing?: int
     * }>  $receivedLines
     *
     * @throws DomainException
     */
    public function receiveGoods(
        int $supplierPurchaseOrderId,
        array $receivedLines,
        int $actorId,
        string $targetSourceCode = 'hayest_dropship_sa'
    ): SupplierPurchaseOrder {
        return $this->receiveInSaudiHub($supplierPurchaseOrderId, $receivedLines, $actorId, $targetSourceCode);
    }

    /**
     * Stage 1: Official Saudi Staging Hub Inbound Receipt (hayest_dropship_sa).
     *
     * @throws DomainException
     */
    public function receiveInSaudiHub(
        int $supplierPurchaseOrderId,
        array $receivedLines,
        int $actorId,
        string $targetSourceCode = 'hayest_dropship_sa'
    ): SupplierPurchaseOrder {
        ProcurementAcl::authorizeActor($actorId, ProcurementAcl::PERMISSION_EXCEPTION_HANDLE);

        return DB::transaction(function () use ($supplierPurchaseOrderId, $receivedLines, $actorId, $targetSourceCode) {
            if (in_array($targetSourceCode, self::FORBIDDEN_RECEIVING_SOURCES, true)) {
                throw new DomainException("Security Violation: Cannot receive imported goods into forbidden/legacy source '{$targetSourceCode}'. Must use 'hayest_dropship_sa'.");
            }

            if ($targetSourceCode !== 'hayest_dropship_sa') {
                throw new DomainException("Invalid Saudi transit source '{$targetSourceCode}'. Official Saudi transit hub is 'hayest_dropship_sa'.");
            }

            /** @var SupplierPurchaseOrder $spo */
            $spo = SupplierPurchaseOrder::where('id', $supplierPurchaseOrderId)->lockForUpdate()->firstOrFail();

            $saSource = InventorySource::where('code', 'hayest_dropship_sa')->firstOrFail();
            $quarantineSaSource = InventorySource::where('code', config('procurement.quarantine_sa_source_code', 'hayest_quarantine_sa'))->first();

            foreach ($receivedLines as $line) {
                $itemId = (int) $line['item_id'];
                $qtyGood = max(0, (int) ($line['qty_good'] ?? 0));
                $qtyDamaged = max(0, (int) ($line['qty_damaged'] ?? 0));
                $qtyMissing = max(0, (int) ($line['qty_missing'] ?? 0));

                /** @var SupplierPurchaseOrderItem $item */
                $item = SupplierPurchaseOrderItem::where('id', $itemId)
                    ->where('supplier_purchase_order_id', $spo->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                // 1. Update PO Item counters
                $item->update([
                    'qty_received_good' => $item->qty_received_good + $qtyGood,
                    'qty_damaged' => $item->qty_damaged + $qtyDamaged,
                    'qty_missing' => $item->qty_missing + $qtyMissing,
                ]);

                // 2. Increment Saudi Staging Hub strictly by good quantity (is_salable = 0)
                if ($qtyGood > 0) {
                    $inventory = ProductInventory::firstOrCreate(
                        [
                            'product_id' => $item->product_id,
                            'inventory_source_id' => $saSource->id,
                        ],
                        ['qty' => 0]
                    );

                    $inventory->increment('qty', $qtyGood);
                }

                // 3. Increment Saudi Quarantine Hub for damaged units
                if ($qtyDamaged > 0 && $quarantineSaSource) {
                    $quarantineInv = ProductInventory::firstOrCreate(
                        [
                            'product_id' => $item->product_id,
                            'inventory_source_id' => $quarantineSaSource->id,
                        ],
                        ['qty' => 0]
                    );

                    $quarantineInv->increment('qty', $qtyDamaged);
                }

                // 4. Allocate good received quantities to Demand Allocations
                $allocations = ProcurementDemandAllocation::where('supplier_purchase_order_item_id', $item->id)
                    ->lockForUpdate()
                    ->get();

                $remainingGoodToDistribute = $qtyGood;

                foreach ($allocations as $alloc) {
                    if ($remainingGoodToDistribute <= 0) {
                        break;
                    }

                    $needed = $alloc->qty_allocated - $alloc->qty_received_good;
                    if ($needed <= 0) {
                        continue;
                    }

                    $assign = min($needed, $remainingGoodToDistribute);
                    $newReceivedGood = $alloc->qty_received_good + $assign;

                    $alloc->update([
                        'qty_received_good' => $newReceivedGood,
                        'state' => ($newReceivedGood >= $alloc->qty_allocated) ? ProcurementDemandAllocation::STATE_RECEIVED : ProcurementDemandAllocation::STATE_ORDERED,
                    ]);

                    $remainingGoodToDistribute -= $assign;

                    // Update corresponding Demand
                    /** @var ProcurementDemand $demand */
                    $demand = ProcurementDemand::where('id', $alloc->procurement_demand_id)->lockForUpdate()->first();
                    if ($demand) {
                        $demandNewReceived = $demand->qty_received_good + $assign;
                        $demandState = ($demandNewReceived >= $demand->qty_required_external)
                            ? ProcurementDemand::STATE_FULFILLED
                            : ProcurementDemand::STATE_PARTIALLY_RECEIVED;

                        $demand->update([
                            'qty_received_good' => $demandNewReceived,
                            'state' => $demandState,
                        ]);
                    }
                }
            }

            ProcurementAuditLog::create([
                'auditable_type' => SupplierPurchaseOrder::class,
                'auditable_id' => $spo->id,
                'action' => 'inbound_receipt_sa_processed',
                'actor_id' => $actorId,
                'actor_type' => 'admin',
                'old_state' => $spo->state,
                'new_state' => $spo->state,
                'details' => [
                    'target_source' => 'hayest_dropship_sa',
                    'lines_count' => count($receivedLines),
                ],
                'correlation_id' => "spo-{$spo->id}-receipt-sa",
            ]);

            return $spo->fresh(['items.allocations']);
        });
    }

    /**
     * Stage 2: Dispatch from Saudi Transit Hub to Yemen Cross-Dock (Manifest / In-Transit).
     *
     * @param  array<int, int>  $itemQuantities  [item_id => qty_to_dispatch]
     *
     * @throws DomainException
     */
    public function dispatchToYemenTransfer(
        int $supplierPurchaseOrderId,
        array $itemQuantities,
        int $actorId,
        string $manifestReference = 'SA-YE-TRF'
    ): void {
        ProcurementAcl::authorizeActor($actorId, ProcurementAcl::PERMISSION_EXCEPTION_HANDLE);

        DB::transaction(function () use ($supplierPurchaseOrderId, $itemQuantities, $actorId, $manifestReference) {
            $saSource = InventorySource::where('code', 'hayest_dropship_sa')->firstOrFail();

            foreach ($itemQuantities as $itemId => $qty) {
                $qty = max(0, (int) $qty);
                if ($qty <= 0) {
                    continue;
                }

                $item = SupplierPurchaseOrderItem::where('id', $itemId)
                    ->where('supplier_purchase_order_id', $supplierPurchaseOrderId)
                    ->firstOrFail();

                // Deduct from Saudi staging stock
                $saInv = ProductInventory::where('product_id', $item->product_id)
                    ->where('inventory_source_id', $saSource->id)
                    ->lockForUpdate()
                    ->first();

                if (! $saInv || $saInv->qty < $qty) {
                    throw new DomainException("Insufficient stock in 'hayest_dropship_sa' for product #{$item->product_id}. Available: ".($saInv?->qty ?? 0).", Required: {$qty}");
                }

                $saInv->decrement('qty', $qty);
            }

            ProcurementAuditLog::create([
                'auditable_type' => SupplierPurchaseOrder::class,
                'auditable_id' => $supplierPurchaseOrderId,
                'action' => 'dispatch_sa_to_ye_transfer',
                'actor_id' => $actorId,
                'actor_type' => 'admin',
                'old_state' => null,
                'new_state' => 'in_transit_to_ye',
                'details' => [
                    'manifest' => $manifestReference,
                    'items' => $itemQuantities,
                ],
                'correlation_id' => "spo-{$supplierPurchaseOrderId}-transfer",
            ]);
        });
    }

    /**
     * Stage 3: Official Yemen Hub Inbound Receipt (hayest_dropship_ye).
     *
     * @param  array<array{
     *     item_id: int,
     *     qty_good: int,
     *     qty_damaged?: int
     * }>  $receivedLines
     *
     * @throws DomainException
     */
    public function receiveInYemenHub(
        int $supplierPurchaseOrderId,
        array $receivedLines,
        int $actorId,
        string $targetSourceCode = 'hayest_dropship_ye'
    ): void {
        ProcurementAcl::authorizeActor($actorId, ProcurementAcl::PERMISSION_EXCEPTION_HANDLE);

        DB::transaction(function () use ($supplierPurchaseOrderId, $receivedLines, $actorId, $targetSourceCode) {
            if (in_array($targetSourceCode, ['hayest_central', 'hayest_internal_ye', 'default', 'aliexpress_source'], true)) {
                throw new DomainException("Security Violation: Cannot receive imported goods into '{$targetSourceCode}'. Imported products in Yemen must be received into 'hayest_dropship_ye'.");
            }

            if ($targetSourceCode !== 'hayest_dropship_ye') {
                throw new DomainException("Invalid Yemen dropship destination source '{$targetSourceCode}'. Canonical hub is 'hayest_dropship_ye'.");
            }

            $yeSource = InventorySource::where('code', 'hayest_dropship_ye')->firstOrFail();
            $quarantineYeSource = InventorySource::where('code', config('procurement.quarantine_ye_source_code', 'hayest_quarantine_ye'))->first();

            foreach ($receivedLines as $line) {
                $itemId = (int) $line['item_id'];
                $qtyGood = max(0, (int) ($line['qty_good'] ?? 0));
                $qtyDamaged = max(0, (int) ($line['qty_damaged'] ?? 0));

                $item = SupplierPurchaseOrderItem::where('id', $itemId)
                    ->where('supplier_purchase_order_id', $supplierPurchaseOrderId)
                    ->firstOrFail();

                // 1. Increment Yemen Dropship Hub by good quantity (is_salable = 1, is_delivery_source = 1)
                if ($qtyGood > 0) {
                    $yeInv = ProductInventory::firstOrCreate(
                        [
                            'product_id' => $item->product_id,
                            'inventory_source_id' => $yeSource->id,
                        ],
                        ['qty' => 0]
                    );

                    $yeInv->increment('qty', $qtyGood);
                }

                // 2. Increment Yemen Quarantine Hub for transit damaged units
                if ($qtyDamaged > 0 && $quarantineYeSource) {
                    $quarantineInv = ProductInventory::firstOrCreate(
                        [
                            'product_id' => $item->product_id,
                            'inventory_source_id' => $quarantineYeSource->id,
                        ],
                        ['qty' => 0]
                    );

                    $quarantineInv->increment('qty', $qtyDamaged);
                }
            }

            ProcurementAuditLog::create([
                'auditable_type' => SupplierPurchaseOrder::class,
                'auditable_id' => $supplierPurchaseOrderId,
                'action' => 'inbound_receipt_ye_processed',
                'actor_id' => $actorId,
                'actor_type' => 'admin',
                'old_state' => 'in_transit_to_ye',
                'new_state' => 'received_in_ye',
                'details' => [
                    'target_source' => 'hayest_dropship_ye',
                    'lines_count' => count($receivedLines),
                ],
                'correlation_id' => "spo-{$supplierPurchaseOrderId}-receipt-ye",
            ]);
        });
    }
}
