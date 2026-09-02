<?php

namespace Webkul\Procurement\Services;

use App\Models\AliExpressProductImport;
use App\Models\AliExpressSetting;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\Procurement\Models\ExternalPlatformOrder;
use Webkul\Procurement\Models\ProcurementAuditLog;
use Webkul\Procurement\Models\ProcurementBatch;
use Webkul\Procurement\Models\ProcurementBatchDemand;
use Webkul\Procurement\Models\ProcurementCostSnapshot;
use Webkul\Procurement\Models\ProcurementDemand;
use Webkul\Procurement\Models\ProcurementDemandAllocation;
use Webkul\Procurement\Models\SupplierPurchaseOrder;
use Webkul\Procurement\Models\SupplierPurchaseOrderItem;
use Webkul\Procurement\Security\ProcurementAcl;
use Webkul\User\Models\Admin;

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
                throw new DomainException('لم يتم العثور على أي احتياجات شراء مطابقة للتجميع.');
            }

            // Verify each demand is open and has available unbatched quantity and valid store ID
            foreach ($demands as $demand) {
                if ($demand->state !== ProcurementDemand::STATE_OPEN_FOR_BATCHING) {
                    throw new DomainException("المطلب #{$demand->id} في حالة '{$demand->state}' ولا يمكن تجميعه في دفعة.");
                }

                if (empty($demand->supplier_store_id)) {
                    throw new DomainException("المطلب #{$demand->id} لا يحتوي على معرف متجر مورد صالح.");
                }

                $available = $demand->qty_required_external - $demand->qty_batched - $demand->qty_cancelled;
                if ($available <= 0) {
                    throw new DomainException("المطلب #{$demand->id} ليس لديه كميات متبقية للتجميع.");
                }

                if (strtoupper((string) $demand->supplier_currency_code) !== 'USD') {
                    throw new DomainException("المطلب #{$demand->id} بعملة غير الدولار ({$demand->supplier_currency_code}). النظام يقبل عملة USD فقط.");
                }

                // Stock validation check: Block batching if supplier stock is zero
                $stock = $this->resolveDemandSupplierStock($demand);
                if ($stock !== null && $stock <= 0) {
                    $productName = $demand->product?->name ?: "الصنف {$demand->supplier_sku_id}";
                    throw new DomainException("لا يمكن تجميع الدفعة: الصنف (SKU: {$demand->supplier_sku_id}) للمنتج '{$productName}' غير متوفر حالياً لدى المورد في علي إكسبرس (المخزون: 0). يرجى استبعاد هذا المطلب لتتمكن من تجميع باقي الطلبات.");
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
                'created_by' => $actorId ?: null,
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

            $shouldConsolidate = (bool) config('procurement.batching.consolidate_orders', true);

            if ($shouldConsolidate) {
                // Combine all demands across all suppliers into a single consolidated purchase order
                $orderGroups = ['consolidated' => []];
                foreach ($storeGroups as $groupedItems) {
                    foreach ($groupedItems as $item) {
                        $orderGroups['consolidated'][] = $item;
                    }
                }
            } else {
                $orderGroups = $storeGroups;
            }

            $batchTotalExpectedCost = 0.0;
            $storeIndex = 1;

            // Create SupplierPurchaseOrder for each order group
            foreach ($orderGroups as $groupId => $groupedItems) {
                $poNumber = 'SPO-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)).'-'.str_pad((string) $storeIndex, 2, '0', STR_PAD_LEFT);
                $storeIndex++;

                $firstItemDemand = $groupedItems[0]['demand'];

                $uniqueStores = collect($groupedItems)->pluck('demand.supplier_store_id')->filter()->unique();
                $uniqueStoreNames = collect($groupedItems)->pluck('demand.supplier_store_name')->filter()->unique();

                if ($uniqueStores->count() === 1) {
                    $resolvedStoreId = (string) $uniqueStores->first();
                    $resolvedStoreName = (string) ($uniqueStoreNames->first() ?: "Store #{$resolvedStoreId}");
                } else {
                    $resolvedStoreId = 'consolidated';
                    $resolvedStoreName = 'موردين متعددين (AliExpress Consolidated)';
                }

                $activeFingerprint = hash('sha256', "batch-{$batch->id}-group-{$groupId}-provider-{$firstItemDemand->provider}-curr-USD-".now()->timestamp);

                $supplierPo = SupplierPurchaseOrder::create([
                    'batch_id' => $batch->id,
                    'purchase_order_number' => $poNumber,
                    'provider' => $firstItemDemand->provider ?? 'aliexpress',
                    'provider_account_id' => $firstItemDemand->provider_account_id,
                    'supplier_store_id' => $resolvedStoreId,
                    'supplier_store_name' => $resolvedStoreName,
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

                // Calculate Expected Shipping Total for this Store SPO
                $poShippingTotal = 0.0;
                $processedImportIds = [];

                foreach ($groupedItems as $entry) {
                    /** @var ProcurementDemand $demand */
                    $demand = $entry['demand'];
                    $importId = $demand->source_snapshot['import_id'] ?? null;
                    $import = null;

                    if ($importId) {
                        $import = AliExpressProductImport::find($importId);
                    }
                    if (! $import && $demand->supplier_product_id) {
                        $import = AliExpressProductImport::where('aliexpress_product_id', $demand->supplier_product_id)->first();
                    }

                    if ($import) {
                        $isChoice = $import->isChoice() || (
                            stripos($import->shipping_company ?? '', 'selection') !== false ||
                            stripos($import->shipping_company ?? '', 'choice') !== false
                        );
                        if (! $isChoice && $import->base_shipping_cost !== null && (float) $import->base_shipping_cost > 0) {
                            if (! in_array($import->id, $processedImportIds, true)) {
                                $poShippingTotal += (float) $import->base_shipping_cost;
                                $processedImportIds[] = $import->id;
                            }
                        }
                    }
                }

                $poExpectedTotal = $poItemsTotal + $poShippingTotal;

                $supplierPo->update([
                    'expected_items_total' => $poItemsTotal,
                    'expected_shipping_total' => $poShippingTotal,
                    'expected_total' => $poExpectedTotal,
                ]);

                // Create Cost Snapshot for SPO
                ProcurementCostSnapshot::create([
                    'snapshotable_type' => SupplierPurchaseOrder::class,
                    'snapshotable_id' => $supplierPo->id,
                    'snapshot_type' => ProcurementCostSnapshot::TYPE_EXPECTED_AT_BATCHING,
                    'items_subtotal' => $poItemsTotal,
                    'shipping_amount' => $poShippingTotal,
                    'discount_amount' => 0.0000,
                    'tax_fee_amount' => 0.0000,
                    'total_amount' => $poExpectedTotal,
                    'currency_code' => 'USD',
                    'exchange_rate' => 1.000000,
                    'allocation_basis' => 'proportionate_subtotal',
                    'breakdown' => [
                        'spo_id' => $supplierPo->id,
                        'items_total' => $poItemsTotal,
                        'shipping_total' => $poShippingTotal,
                        'items_count' => count($skuGroups),
                    ],
                    'actor_id' => $actorId,
                    'actor_type' => 'admin',
                    'correlation_id' => "batch-{$batch->id}-spo-{$supplierPo->id}",
                    'snapshot_hash' => hash('sha256', "spo-{$supplierPo->id}-{$poExpectedTotal}-USD-".now()->toIso8601String()),
                    'created_at' => now(),
                ]);

                $batchTotalExpectedCost += $poExpectedTotal;
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
        if ($actorId <= 0) {
            $actorId = (int) (auth()->guard('admin')->id() ?: auth()->id()) ?: (Admin::first()?->id ?? 1);
        }

        ProcurementAcl::authorizeActor($actorId, ProcurementAcl::PERMISSION_BATCH_APPROVE);

        /** @var ProcurementBatch $batch */
        $batch = ProcurementBatch::where('id', $batchId)->firstOrFail();

        if ($batch->state !== ProcurementBatch::STATE_READY_FOR_REVIEW && $batch->state !== ProcurementBatch::STATE_DRAFT && $batch->state !== ProcurementBatch::STATE_EXCEPTION) {
            throw new DomainException("Cannot approve batch in state '{$batch->state}'.");
        }

        // Pre-Approval Gate: Live Verification of Stock, Deliverability, and Cost Variance for all SPOs in Batch
        if (! (app()->environment('testing') && config('procurement.mock_dispatch_in_testing', true))) {
            /** @var ProcurementSubmitService $submitService */
            $submitService = app(ProcurementSubmitService::class);
            $aeSetting = AliExpressSetting::current();
            $varType = $aeSetting->variance_product_type ?? 'percentage';
            $varLimit = (float) ($aeSetting->variance_product_limit ?? 10.0);

            foreach ($batch->supplierOrders as $spo) {
                // If SPO is already in cost_variance_review and pending approval, inform admin
                if ($spo->state === SupplierPurchaseOrder::STATE_COST_VARIANCE_REVIEW) {
                    throw new DomainException(
                        "لا يمكن اعتماد الدفعة: أمر الشراء {$spo->purchase_order_number} بانتظار مراجعة وقبول فرق التكلفة في صفحة (فروقات التكلفة)."
                    );
                }

                $spo->load('items');

                // 1. Live Cost Variance Guard
                foreach ($spo->items as $item) {
                    $expectedCost = (float) $item->expected_unit_cost;
                    if ($expectedCost <= 0) {
                        continue;
                    }

                    $liveCost = $submitService->fetchLiveSkuCost($spo, $item);
                    if ($liveCost !== null && $liveCost > 0) {
                        $isExceeded = false;
                        $variancePercent = 0.0;
                        if ($varType === 'fixed') {
                            $varianceDelta = abs($liveCost - $expectedCost);
                            $isExceeded = $varianceDelta > $varLimit;
                            $variancePercent = ($varianceDelta / $expectedCost) * 100;
                        } else {
                            $variancePercent = abs(($liveCost - $expectedCost) / $expectedCost) * 100;
                            $isExceeded = $variancePercent > $varLimit;
                        }

                        if ($isExceeded) {
                            $varianceAmount = round($liveCost - $expectedCost, 4);

                            // Persist cost variance state transition to DB
                            $spo->update([
                                'state' => SupplierPurchaseOrder::STATE_COST_VARIANCE_REVIEW,
                                'cost_variance_amount' => $varianceAmount,
                            ]);

                            $batch->update([
                                'state' => ProcurementBatch::STATE_EXCEPTION,
                            ]);

                            ProcurementAuditLog::create([
                                'auditable_type' => SupplierPurchaseOrder::class,
                                'auditable_id' => $spo->id,
                                'action' => 'cost_variance_guard_triggered_at_approval',
                                'actor_id' => $actorId,
                                'actor_type' => 'admin',
                                'old_state' => $spo->getOriginal('state') ?? SupplierPurchaseOrder::STATE_DRAFT,
                                'new_state' => SupplierPurchaseOrder::STATE_COST_VARIANCE_REVIEW,
                                'details' => [
                                    'item_id' => $item->id,
                                    'supplier_sku_id' => $item->supplier_sku_id,
                                    'expected_unit_cost' => $expectedCost,
                                    'live_unit_cost' => $liveCost,
                                    'variance_percent' => round($variancePercent, 2),
                                    'threshold_limit' => $varLimit,
                                    'threshold_type' => $varType,
                                ],
                                'correlation_id' => "spo-{$spo->id}-approval-cost-guard",
                            ]);

                            $limitDisplay = $varType === 'fixed' ? "\${$varLimit}" : "{$varLimit}%";
                            throw new DomainException(
                                "لا يمكن اعتماد الدفعة: تجاوز تغير السعر للصنف (SKU: {$item->supplier_sku_id}) في أمر الشراء {$spo->purchase_order_number} الحد المسموح ({$limitDisplay}). التكلفة المتوقعة: \${$expectedCost}، التكلفة الحالية لدى المورد: \${$liveCost}. تم تحويله إلى قائمة (فروقات التكلفة) لمراجعته."
                            );
                        }
                    }
                }

                // 2. Preflight Check (Live Stock & Deliverability)
                $preflight = $submitService->preflightSupplierPurchaseOrder($spo->id);
                if (! $preflight->isSuccess || ! $preflight->isDeliverableToDestination) {
                    $batch->update([
                        'state' => ProcurementBatch::STATE_EXCEPTION,
                    ]);

                    $errReason = $preflight->errorMessage ?: 'أمر الشراء غير مؤهل للإرسال إلى المورد';
                    throw new DomainException(
                        "لا يمكن اعتماد الدفعة: أمر الشراء {$spo->purchase_order_number} غير مؤهل للإرسال إلى علي إكسبرس ({$errReason})."
                    );
                }
            }
        }

        // Commit Batch Approval in Transaction
        return DB::transaction(function () use ($batch, $actorId, $notes) {
            $batch->update([
                'state' => ProcurementBatch::STATE_APPROVED,
                'approved_by' => $actorId,
                'approved_at' => now(),
            ]);

            foreach ($batch->supplierOrders as $spo) {
                if ($spo->state !== SupplierPurchaseOrder::STATE_CANCELLED && $spo->state !== SupplierPurchaseOrder::STATE_COST_VARIANCE_REVIEW) {
                    $spo->update([
                        'state' => SupplierPurchaseOrder::STATE_READY_TO_SUBMIT,
                    ]);
                }
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
        if ($actorId <= 0) {
            $actorId = (int) (auth()->guard('admin')->id() ?: auth()->id()) ?: (Admin::first()?->id ?? 1);
        }

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

    /**
     * Remove a Supplier Purchase Order from a batch, releasing its demands back to open pool.
     *
     * @throws DomainException
     */
    public function removeSupplierOrderFromBatch(int $batchId, int $spoId, int $actorId): ProcurementBatch
    {
        if ($actorId <= 0) {
            $actorId = (int) (auth()->guard('admin')->id() ?: auth()->id()) ?: (Admin::first()?->id ?? 1);
        }

        ProcurementAcl::authorizeActor($actorId, ProcurementAcl::PERMISSION_BATCH_APPROVE, allowSystem: true);

        return DB::transaction(function () use ($batchId, $spoId, $actorId) {
            /** @var ProcurementBatch $batch */
            $batch = ProcurementBatch::with(['supplierOrders.items.allocations'])->where('id', $batchId)->lockForUpdate()->firstOrFail();

            /** @var SupplierPurchaseOrder|null $spo */
            $spo = $batch->supplierOrders->where('id', $spoId)->first();
            if (! $spo) {
                throw new DomainException('أمر الشراء المحدد غير موجود ضمن هذه الدفعة.');
            }

            // Check if this SPO has an active live order created on AliExpress
            $hasLiveOrder = $spo->platformOrders()
                ->whereNotNull('external_order_id')
                ->where('external_order_id', '!=', '')
                ->where('normalized_status', '!=', ExternalPlatformOrder::STATUS_SUBMISSION_FAILED)
                ->exists();

            if ($hasLiveOrder) {
                throw new DomainException('لا يمكن إزالة هذا الأمر لأنه تم إنشاؤه مسبقاً في علي إكسبرس. يرجى إلغاء طلب علي إكسبرس أولاً.');
            }

            // 1. Release allocations and update demand counters
            foreach ($spo->items as $item) {
                foreach ($item->allocations as $allocation) {
                    $demand = ProcurementDemand::where('id', $allocation->procurement_demand_id)->lockForUpdate()->first();
                    if ($demand) {
                        $newQtyBatched = max(0, $demand->qty_batched - $allocation->qty_allocated);
                        $demand->update([
                            'qty_batched' => $newQtyBatched,
                            'state' => ProcurementDemand::STATE_OPEN_FOR_BATCHING,
                        ]);

                        $batchDemand = ProcurementBatchDemand::where('batch_id', $batch->id)
                            ->where('procurement_demand_id', $demand->id)
                            ->first();

                        if ($batchDemand) {
                            $newBatchQty = max(0, $batchDemand->qty_batched - $allocation->qty_allocated);
                            if ($newBatchQty <= 0) {
                                $batchDemand->delete();
                            } else {
                                $batchDemand->update(['qty_batched' => $newBatchQty]);
                            }
                        }
                    }

                    $allocation->delete();
                }

                $item->delete();
            }

            $spoNumber = $spo->purchase_order_number;
            $storeName = $spo->supplier_store_name;
            $spo->delete();

            // 2. Recalculate remaining batch SPOs and cost
            $remainingSpos = SupplierPurchaseOrder::where('batch_id', $batch->id)->get();
            if ($remainingSpos->isEmpty()) {
                $batch->update([
                    'state' => ProcurementBatch::STATE_CANCELLED,
                    'expected_total_cost' => 0.0000,
                ]);
            } else {
                $newExpectedTotal = $remainingSpos->sum(fn ($s) => (float) $s->expected_total);
                $newState = $batch->state;
                if (in_array($batch->state, [ProcurementBatch::STATE_EXCEPTION, ProcurementBatch::STATE_PARTIALLY_SUBMITTED])) {
                    $allDraftOrReady = $remainingSpos->every(fn ($s) => in_array($s->state, [
                        SupplierPurchaseOrder::STATE_DRAFT,
                        SupplierPurchaseOrder::STATE_READY_TO_SUBMIT,
                    ]));
                    if ($allDraftOrReady) {
                        $newState = ProcurementBatch::STATE_APPROVED;
                    }
                }

                $batch->update([
                    'expected_total_cost' => $newExpectedTotal,
                    'state' => $newState,
                ]);
            }

            // 3. Audit Log
            ProcurementAuditLog::create([
                'auditable_type' => ProcurementBatch::class,
                'auditable_id' => $batch->id,
                'action' => 'supplier_order_removed_from_batch',
                'actor_id' => $actorId,
                'actor_type' => 'admin',
                'details' => [
                    'removed_spo_id' => $spoId,
                    'purchase_order_number' => $spoNumber,
                    'supplier_store_name' => $storeName,
                    'remaining_spos_count' => $remainingSpos->count(),
                ],
                'correlation_id' => "batch-{$batch->id}-remove-spo-{$spoId}",
            ]);

            return $batch->fresh(['supplierOrders.items.allocations', 'demands']);
        });
    }

    /**
     * Resolve the current supplier SKU stock from AliExpress import snapshots or demand snapshot.
     */
    public function resolveDemandSupplierStock(ProcurementDemand $demand): ?int
    {
        $import = AliExpressProductImport::where('id', $demand->source_snapshot['import_id'] ?? null)
            ->orWhere('aliexpress_product_id', $demand->supplier_product_id)
            ->orWhere('product_id', $demand->product_id)
            ->latest('id')
            ->first();

        if ($import && ! empty($import->payload_snapshot['variants'])) {
            foreach ($import->payload_snapshot['variants'] as $v) {
                $sId = (string) ($v['sku_id'] ?? $v['id'] ?? '');
                if ($sId == $demand->supplier_sku_id || count($import->payload_snapshot['variants']) === 1) {
                    return isset($v['stock']) || isset($v['quantity']) || isset($v['sku_stock'])
                        ? (int) ($v['stock'] ?? $v['quantity'] ?? $v['sku_stock'])
                        : 0;
                }
            }
        }

        if (! empty($demand->source_snapshot['stock'])) {
            return (int) $demand->source_snapshot['stock'];
        }

        return null;
    }
}
