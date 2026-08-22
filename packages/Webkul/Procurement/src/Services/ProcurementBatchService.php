<?php

namespace Webkul\Procurement\Services;

use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\Procurement\Models\ProcurementAuditLog;
use Webkul\Procurement\Models\ProcurementBatch;
use Webkul\Procurement\Models\ProcurementBatchDemand;
use Webkul\Procurement\Models\ProcurementCostSnapshot;
use Webkul\Procurement\Models\ProcurementDemand;
use Webkul\Procurement\Models\ProcurementDemandAllocation;
use Webkul\Procurement\Models\SupplierPurchaseOrder;
use Webkul\Procurement\Models\SupplierPurchaseOrderItem;
use Webkul\Procurement\Security\ProcurementAcl;

class ProcurementBatchService
{
    /**
     * Get query for demands eligible for batching.
     */
    public function getOpenDemandsQuery(?string $provider = 'aliexpress', ?string $currency = 'USD'): Builder
    {
        return ProcurementDemand::query()
            ->where('state', ProcurementDemand::STATE_OPEN_FOR_BATCHING)
            ->where('provider', $provider ?? 'aliexpress')
            ->where('supplier_currency_code', $currency ?? 'USD')
            ->whereNotNull('supplier_store_id')
            ->where('supplier_store_id', '!=', '')
            ->whereRaw('(qty_required_external - qty_batched - qty_cancelled) > 0');
    }

    /**
     * Preview batch aggregation without mutating state.
     */
    public function previewBatch(array $demandIds): array
    {
        $demands = ProcurementDemand::whereIn('id', $demandIds)
            ->where('state', ProcurementDemand::STATE_OPEN_FOR_BATCHING)
            ->get();

        $stores = [];
        $totalItems = 0;
        $expectedTotalCost = 0.0;

        foreach ($demands as $demand) {
            $unbatchedQty = $demand->remaining_unbatched_qty;
            if ($unbatchedQty <= 0) {
                continue;
            }

            $storeId = $demand->supplier_store_id;
            if (empty($storeId)) {
                throw new DomainException("Demand #{$demand->id} has no valid supplier store ID.");
            }

            $unitCost = (float) ($demand->source_snapshot['unit_cost'] ?? 10.0);
            $lineCost = $unbatchedQty * $unitCost;

            if (! isset($stores[$storeId])) {
                $stores[$storeId] = [
                    'store_id' => $storeId,
                    'store_name' => $demand->supplier_store_name ?: 'Store #'.$storeId,
                    'total_demands' => 0,
                    'total_qty' => 0,
                    'expected_cost' => 0.0,
                    'items' => [],
                ];
            }

            $stores[$storeId]['total_demands']++;
            $stores[$storeId]['total_qty'] += $unbatchedQty;
            $stores[$storeId]['expected_cost'] += $lineCost;
            $stores[$storeId]['items'][] = [
                'demand_id' => $demand->id,
                'order_id' => $demand->order_id,
                'supplier_product_id' => $demand->supplier_product_id,
                'supplier_sku_id' => $demand->supplier_sku_id,
                'qty' => $unbatchedQty,
                'unit_cost' => $unitCost,
                'line_cost' => $lineCost,
            ];

            $totalItems += $unbatchedQty;
            $expectedTotalCost += $lineCost;
        }

        return [
            'demands_count' => $demands->count(),
            'total_items_count' => $totalItems,
            'expected_total_cost' => round($expectedTotalCost, 4),
            'currency' => 'USD',
            'stores_count' => count($stores),
            'stores' => array_values($stores),
        ];
    }

    /**
     * Create a procurement batch and split into store-specific Supplier POs.
     *
     * @throws DomainException
     */
    public function createBatch(array $demandIds, ?int $actorId = null): ProcurementBatch
    {
        if (empty($demandIds)) {
            throw new DomainException('Cannot create procurement batch with empty demands list.');
        }

        ProcurementAcl::authorizeActor($actorId, ProcurementAcl::PERMISSION_BATCH_CREATE, allowSystem: true);

        return DB::transaction(function () use ($demandIds, $actorId) {
            // Lock demands with SELECT ... FOR UPDATE to prevent concurrency race conditions
            /** @var Collection<int, ProcurementDemand> $demands */
            $demands = ProcurementDemand::whereIn('id', $demandIds)
                ->lockForUpdate()
                ->get();

            if ($demands->isEmpty()) {
                throw new DomainException('No matching procurement demands found.');
            }

            // Verify each demand is open and has available unbatched quantity and valid store ID
            foreach ($demands as $demand) {
                if ($demand->state !== ProcurementDemand::STATE_OPEN_FOR_BATCHING) {
                    throw new DomainException("Demand #{$demand->id} is in state '{$demand->state}' and cannot be batched.");
                }

                if (empty($demand->supplier_store_id)) {
                    throw new DomainException("Demand #{$demand->id} does not have a valid supplier_store_id and cannot be batched.");
                }

                $available = $demand->qty_required_external - $demand->qty_batched - $demand->qty_cancelled;
                if ($available <= 0) {
                    throw new DomainException("Demand #{$demand->id} has no remaining quantity available to batch.");
                }

                if (strtoupper((string) $demand->supplier_currency_code) !== 'USD') {
                    throw new DomainException("Demand #{$demand->id} has non-USD currency ({$demand->supplier_currency_code}). V2 strictly accepts USD only.");
                }
            }

            $firstDemand = $demands->first();
            $batchNumber = 'BATCH-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));

            // Create Batch Header
            $batch = ProcurementBatch::create([
                'batch_number' => $batchNumber,
                'provider' => $firstDemand->provider ?? 'aliexpress',
                'provider_account_id' => $firstDemand->provider_account_id,
                'currency_code' => 'USD',
                'destination_signature' => $firstDemand->destination_source_code,
                'state' => ProcurementBatch::STATE_READY_FOR_REVIEW,
                'created_by' => $actorId,
                'source_snapshot_at' => now(),
                'expected_total_cost' => 0.0000,
                'actual_total_cost' => 0.0000,
                'cost_variance_amount' => 0.0000,
                'lock_version' => 1,
            ]);

            // Group Demands strictly by Store ID
            $storeGroups = [];
            foreach ($demands as $demand) {
                $qtyToBatch = $demand->remaining_unbatched_qty;

                // Create Batch Demand join record
                ProcurementBatchDemand::create([
                    'batch_id' => $batch->id,
                    'procurement_demand_id' => $demand->id,
                    'qty_batched' => $qtyToBatch,
                    'qty_released' => 0,
                    'state' => 'batched',
                ]);

                // Update Demand counters and state
                $newQtyBatched = $demand->qty_batched + $qtyToBatch;
                $isFullyBatched = ($newQtyBatched >= $demand->qty_required_external);

                $demand->update([
                    'qty_batched' => $newQtyBatched,
                    'state' => $isFullyBatched ? ProcurementDemand::STATE_BATCHED : ProcurementDemand::STATE_OPEN_FOR_BATCHING,
                ]);

                $storeId = (string) $demand->supplier_store_id;
                $storeGroups[$storeId][] = [
                    'demand' => $demand,
                    'qty' => $qtyToBatch,
                ];
            }

            $batchTotalExpectedCost = 0.0;
            $storeIndex = 1;

            // Create SupplierPurchaseOrder for each store
            foreach ($storeGroups as $storeId => $groupedItems) {
                $poNumber = 'SPO-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)).'-'.str_pad((string) $storeIndex, 2, '0', STR_PAD_LEFT);
                $storeIndex++;

                $firstItemDemand = $groupedItems[0]['demand'];
                $activeFingerprint = hash('sha256', "batch-{$batch->id}-store-{$storeId}-provider-{$firstItemDemand->provider}-curr-USD");

                $supplierPo = SupplierPurchaseOrder::create([
                    'batch_id' => $batch->id,
                    'purchase_order_number' => $poNumber,
                    'provider' => $firstItemDemand->provider ?? 'aliexpress',
                    'provider_account_id' => $firstItemDemand->provider_account_id,
                    'supplier_store_id' => $storeId,
                    'supplier_store_name' => $firstItemDemand->supplier_store_name,
                    'currency_code' => 'USD',
                    'destination_signature' => $firstItemDemand->destination_source_code,
                    'state' => SupplierPurchaseOrder::STATE_DRAFT,
                    'expected_items_total' => 0.0000,
                    'expected_shipping_total' => 0.0000,
                    'expected_discount_total' => 0.0000,
                    'expected_total' => 0.0000,
                    'actual_items_total' => 0.0000,
                    'actual_shipping_total' => 0.0000,
                    'actual_discount_total' => 0.0000,
                    'actual_total' => 0.0000,
                    'cost_variance_amount' => 0.0000,
                    'payment_state' => 'unpaid',
                    'external_sync_state' => 'pending',
                    'active_fingerprint' => $activeFingerprint,
                    'lock_version' => 1,
                ]);

                // Aggregate by SKU within store
                $skuGroups = [];
                foreach ($groupedItems as $entry) {
                    /** @var ProcurementDemand $demand */
                    $demand = $entry['demand'];
                    $qty = $entry['qty'];

                    $skuKey = $demand->supplier_product_id.'|'.$demand->supplier_sku_id.'|'.$demand->product_id;
                    if (! isset($skuGroups[$skuKey])) {
                        $skuGroups[$skuKey] = [
                            'supplier_product_id' => $demand->supplier_product_id,
                            'supplier_sku_id' => $demand->supplier_sku_id,
                            'product_id' => $demand->product_id,
                            'variant_product_id' => $demand->variant_product_id,
                            'unit_cost' => (float) ($demand->source_snapshot['unit_cost'] ?? 10.0),
                            'total_qty' => 0,
                            'allocations' => [],
                        ];
                    }

                    $skuGroups[$skuKey]['total_qty'] += $qty;
                    $skuGroups[$skuKey]['allocations'][] = [
                        'demand_id' => $demand->id,
                        'qty' => $qty,
                    ];
                }

                $poItemsTotal = 0.0;

                // Create SupplierPurchaseOrderItems and Allocations
                foreach ($skuGroups as $skuData) {
                    $itemTotalCost = $skuData['total_qty'] * $skuData['unit_cost'];
                    $poItemsTotal += $itemTotalCost;

                    $poItem = SupplierPurchaseOrderItem::create([
                        'supplier_purchase_order_id' => $supplierPo->id,
                        'supplier_product_id' => $skuData['supplier_product_id'],
                        'supplier_sku_id' => $skuData['supplier_sku_id'],
                        'product_id' => $skuData['product_id'],
                        'variant_product_id' => $skuData['variant_product_id'],
                        'qty_ordered' => $skuData['total_qty'],
                        'qty_confirmed' => 0,
                        'qty_received_good' => 0,
                        'qty_damaged' => 0,
                        'qty_missing' => 0,
                        'expected_unit_cost' => $skuData['unit_cost'],
                        'actual_unit_cost' => null,
                        'snapshots' => [
                            'created_at' => now()->toIso8601String(),
                            'unit_cost' => $skuData['unit_cost'],
                            'sku' => $skuData['supplier_sku_id'],
                        ],
                    ]);

                    foreach ($skuData['allocations'] as $alloc) {
                        ProcurementDemandAllocation::create([
                            'procurement_demand_id' => $alloc['demand_id'],
                            'supplier_purchase_order_item_id' => $poItem->id,
                            'qty_allocated' => $alloc['qty'],
                            'qty_ordered' => 0,
                            'qty_received_good' => 0,
                            'qty_cancelled' => 0,
                            'state' => ProcurementDemandAllocation::STATE_ALLOCATED,
                        ]);
                    }
                }

                $supplierPo->update([
                    'expected_items_total' => $poItemsTotal,
                    'expected_total' => $poItemsTotal,
                ]);

                // Create Cost Snapshot for SPO
                ProcurementCostSnapshot::create([
                    'snapshotable_type' => SupplierPurchaseOrder::class,
                    'snapshotable_id' => $supplierPo->id,
                    'snapshot_type' => ProcurementCostSnapshot::TYPE_EXPECTED_AT_BATCHING,
                    'items_subtotal' => $poItemsTotal,
                    'shipping_amount' => 0.0000,
                    'discount_amount' => 0.0000,
                    'tax_fee_amount' => 0.0000,
                    'total_amount' => $poItemsTotal,
                    'currency_code' => 'USD',
                    'exchange_rate' => 1.000000,
                    'allocation_basis' => 'proportionate_subtotal',
                    'breakdown' => [
                        'spo_id' => $supplierPo->id,
                        'items_total' => $poItemsTotal,
                        'items_count' => count($skuGroups),
                    ],
                    'actor_id' => $actorId,
                    'actor_type' => 'admin',
                    'correlation_id' => "batch-{$batch->id}-spo-{$supplierPo->id}",
                    'snapshot_hash' => hash('sha256', "spo-{$supplierPo->id}-{$poItemsTotal}-USD-".now()->toIso8601String()),
                    'created_at' => now(),
                ]);

                $batchTotalExpectedCost += $poItemsTotal;
            }

            // Update Batch totals
            $batch->update([
                'expected_total_cost' => $batchTotalExpectedCost,
            ]);

            // Create Cost Snapshot for Batch
            ProcurementCostSnapshot::create([
                'snapshotable_type' => ProcurementBatch::class,
                'snapshotable_id' => $batch->id,
                'snapshot_type' => ProcurementCostSnapshot::TYPE_EXPECTED_AT_BATCHING,
                'items_subtotal' => $batchTotalExpectedCost,
                'shipping_amount' => 0.0000,
                'discount_amount' => 0.0000,
                'tax_fee_amount' => 0.0000,
                'total_amount' => $batchTotalExpectedCost,
                'currency_code' => 'USD',
                'exchange_rate' => 1.000000,
                'allocation_basis' => 'proportionate_subtotal',
                'breakdown' => [
                    'batch_id' => $batch->id,
                    'stores_count' => count($storeGroups),
                    'total_demands' => $demands->count(),
                ],
                'actor_id' => $actorId,
                'actor_type' => 'admin',
                'correlation_id' => "batch-{$batch->id}",
                'snapshot_hash' => hash('sha256', "batch-{$batch->id}-{$batchTotalExpectedCost}-USD-".now()->toIso8601String()),
                'created_at' => now(),
            ]);

            // Audit log
            ProcurementAuditLog::create([
                'auditable_type' => ProcurementBatch::class,
                'auditable_id' => $batch->id,
                'action' => 'batch_created',
                'actor_id' => $actorId,
                'actor_type' => 'admin',
                'old_state' => null,
                'new_state' => ProcurementBatch::STATE_READY_FOR_REVIEW,
                'details' => [
                    'batch_number' => $batch->batch_number,
                    'demands_count' => $demands->count(),
                    'stores_count' => count($storeGroups),
                    'expected_total_cost' => $batchTotalExpectedCost,
                ],
                'correlation_id' => "batch-{$batch->id}",
            ]);

            return $batch->fresh(['supplierOrders.items.allocations', 'demands']);
        });
    }

    /**
     * Approve a batch to allow submission to AliExpress.
     */
    public function approveBatch(int $batchId, int $actorId, ?string $notes = null): ProcurementBatch
    {
        ProcurementAcl::authorizeActor($actorId, ProcurementAcl::PERMISSION_BATCH_APPROVE);

        return DB::transaction(function () use ($batchId, $actorId, $notes) {
            /** @var ProcurementBatch $batch */
            $batch = ProcurementBatch::where('id', $batchId)->lockForUpdate()->firstOrFail();

            if ($batch->state !== ProcurementBatch::STATE_READY_FOR_REVIEW && $batch->state !== ProcurementBatch::STATE_DRAFT) {
                throw new DomainException("Cannot approve batch in state '{$batch->state}'.");
            }

            $batch->update([
                'state' => ProcurementBatch::STATE_APPROVED,
                'approved_by' => $actorId,
                'approved_at' => now(),
            ]);

            foreach ($batch->supplierOrders as $spo) {
                $spo->update([
                    'state' => SupplierPurchaseOrder::STATE_READY_TO_SUBMIT,
                ]);
            }

            ProcurementAuditLog::create([
                'auditable_type' => ProcurementBatch::class,
                'auditable_id' => $batch->id,
                'action' => 'batch_approved',
                'actor_id' => $actorId,
                'actor_type' => 'admin',
                'old_state' => ProcurementBatch::STATE_READY_FOR_REVIEW,
                'new_state' => ProcurementBatch::STATE_APPROVED,
                'details' => ['notes' => $notes],
                'correlation_id' => "batch-{$batch->id}",
            ]);

            return $batch->fresh();
        });
    }

    /**
     * Reject a batch and release locked demand quantities back to open pool.
     */
    public function rejectBatch(int $batchId, int $actorId, string $reason): ProcurementBatch
    {
        ProcurementAcl::authorizeActor($actorId, ProcurementAcl::PERMISSION_BATCH_APPROVE);

        return DB::transaction(function () use ($batchId, $actorId, $reason) {
            /** @var ProcurementBatch $batch */
            $batch = ProcurementBatch::where('id', $batchId)->lockForUpdate()->firstOrFail();

            if (in_array($batch->state, [
                ProcurementBatch::STATE_SUBMITTED_TO_PROVIDER,
                ProcurementBatch::STATE_COMPLETED,
                ProcurementBatch::STATE_AWAITING_MANUAL_PAYMENT,
            ], true)) {
                throw new DomainException("Cannot reject batch in '{$batch->state}' state after submission.");
            }

            $batchDemands = ProcurementBatchDemand::where('batch_id', $batch->id)->get();

            foreach ($batchDemands as $batchDemand) {
                /** @var ProcurementDemand $demand */
                $demand = ProcurementDemand::where('id', $batchDemand->procurement_demand_id)->lockForUpdate()->first();

                if ($demand) {
                    $newQtyBatched = max(0, $demand->qty_batched - $batchDemand->qty_batched);
                    $demand->update([
                        'qty_batched' => $newQtyBatched,
                        'state' => ProcurementDemand::STATE_OPEN_FOR_BATCHING,
                    ]);

                    $batchDemand->update([
                        'qty_released' => $batchDemand->qty_batched,
                        'state' => 'released',
                    ]);
                }
            }

            foreach ($batch->supplierOrders as $spo) {
                $spo->update([
                    'state' => SupplierPurchaseOrder::STATE_CANCELLED,
                    'active_fingerprint' => null,
                ]);

                foreach ($spo->items as $item) {
                    ProcurementDemandAllocation::where('supplier_purchase_order_item_id', $item->id)
                        ->update(['state' => ProcurementDemandAllocation::STATE_CANCELLED]);
                }
            }

            $batch->update([
                'state' => ProcurementBatch::STATE_CANCELLED,
                'rejection_reason' => $reason,
                'reviewed_by' => $actorId,
            ]);

            ProcurementAuditLog::create([
                'auditable_type' => ProcurementBatch::class,
                'auditable_id' => $batch->id,
                'action' => 'batch_rejected',
                'actor_id' => $actorId,
                'actor_type' => 'admin',
                'old_state' => $batch->getOriginal('state'),
                'new_state' => ProcurementBatch::STATE_CANCELLED,
                'details' => ['reason' => $reason],
                'correlation_id' => "batch-{$batch->id}",
            ]);

            return $batch->fresh();
        });
    }
}
