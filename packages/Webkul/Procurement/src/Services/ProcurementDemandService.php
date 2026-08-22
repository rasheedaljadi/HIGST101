<?php

namespace Webkul\Procurement\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Fulfillment\Models\OrderAllocation;
use Webkul\Inventory\Models\InventorySource;
use Webkul\Procurement\Models\ProcurementAuditLog;
use Webkul\Procurement\Models\ProcurementDemand;
use Webkul\Product\Models\ProductInventory;
use Webkul\Sales\Contracts\Order;

class ProcurementDemandService
{
    public function __construct(
        protected ProcurementEligibilityService $eligibilityService
    ) {}

    /**
     * Process an accepted order and generate procurement demands for external deficits.
     *
     * @return array<ProcurementDemand>
     */
    public function processOrderDemands(Order $order): array
    {
        if (! $order->items || $order->items->isEmpty()) {
            return [];
        }

        if (! $this->eligibilityService->isOrderEligible($order)) {
            Log::info("[Procurement V2] Order #{$order->id} is not eligible for procurement processing yet.");

            return [];
        }

        return DB::transaction(function () use ($order) {
            $createdDemands = [];

            // Resolve inventory source IDs
            $yeDestinationCode = config('procurement.destination_source_code', 'hayest_dropship_ye');
            $yeDropshipSource = InventorySource::where('code', $yeDestinationCode)->first();
            $internalSource = InventorySource::where('code', config('procurement.internal_source_code', 'hayest_internal_ye'))->first();

            foreach ($order->items as $item) {
                $classification = $this->eligibilityService->classifyOrderItem($item);
                $qtyRequested = (int) $item->qty_ordered;

                // 1. Internal Product Rule: Never create external demand or AliExpress PO
                if (! $classification['is_imported']) {
                    $internalStock = 0;
                    if ($internalSource) {
                        $inv = ProductInventory::where('product_id', $item->product_id)
                            ->where('inventory_source_id', $internalSource->id)
                            ->first();
                        $internalStock = (int) ($inv?->qty ?? 0);
                    }

                    if ($internalStock < $qtyRequested) {
                        // Record internal stock exception audit log
                        ProcurementAuditLog::create([
                            'auditable_type' => get_class($item),
                            'auditable_id' => $item->id,
                            'action' => 'internal_stock_exception',
                            'old_state' => null,
                            'new_state' => 'internal_stock_deficit',
                            'details' => [
                                'order_id' => $order->id,
                                'order_item_id' => $item->id,
                                'product_id' => $item->product_id,
                                'sku' => $item->sku,
                                'qty_requested' => $qtyRequested,
                                'qty_available_internal' => $internalStock,
                                'deficit' => $qtyRequested - $internalStock,
                                'reason' => 'Internal product has insufficient stock. Manual warehouse fulfillment required.',
                            ],
                            'correlation_id' => "order-{$order->id}-item-{$item->id}",
                        ]);
                    }

                    continue;
                }

                // Idempotency check: Demand uniqueness fingerprint
                $fingerprint = hash('sha256', "demand|{$order->id}|{$item->id}|{$classification['provider']}|{$classification['supplier_sku_id']}");

                /** @var ProcurementDemand|null $existingDemand */
                $existingDemand = ProcurementDemand::where('active_fingerprint', $fingerprint)
                    ->lockForUpdate()
                    ->first();

                if ($existingDemand) {
                    $createdDemands[] = $existingDemand;

                    continue;
                }

                // 2. Imported Product Rule: Atomic lock and calculate real available stock in hayest_dropship_ye
                $availableYeStock = 0;
                if ($yeDropshipSource) {
                    // Atomically lock or create the ProductInventory record to prevent race conditions
                    $inv = ProductInventory::where('product_id', $item->product_id)
                        ->where('inventory_source_id', $yeDropshipSource->id)
                        ->where('vendor_id', 0)
                        ->lockForUpdate()
                        ->first();

                    if (! $inv) {
                        try {
                            $inv = ProductInventory::firstOrCreate(
                                [
                                    'product_id' => $item->product_id,
                                    'inventory_source_id' => $yeDropshipSource->id,
                                    'vendor_id' => 0,
                                ],
                                ['qty' => 0]
                            );
                        } catch (\Throwable) {
                            // Retry query in case of parallel insertion
                            $inv = ProductInventory::where('product_id', $item->product_id)
                                ->where('inventory_source_id', $yeDropshipSource->id)
                                ->where('vendor_id', 0)
                                ->first();
                        }

                        if ($inv) {
                            $inv = ProductInventory::where('id', $inv->id)->lockForUpdate()->first();
                        }
                    }

                    $physicalSellableQty = max(0, (int) ($inv?->qty ?? 0));

                    // Lock and calculate all active local reservations on hayest_dropship_ye for this product
                    $activeReservations = (int) OrderAllocation::join('order_items', 'order_allocations.order_item_id', '=', 'order_items.id')
                        ->where('order_items.product_id', $item->product_id)
                        ->where('order_allocations.source_code', $yeDestinationCode)
                        ->where('order_allocations.state', 'reserved')
                        ->lockForUpdate()
                        ->sum('order_allocations.reserved_qty');

                    $availableYeStock = max(0, $physicalSellableQty - $activeReservations);
                }

                $qtyCoveredByLocal = min($availableYeStock, $qtyRequested);
                $qtyRequiredExternal = max(0, $qtyRequested - $qtyCoveredByLocal);

                // Create durable OrderAllocation reservation if locally covered
                if ($qtyCoveredByLocal > 0) {
                    OrderAllocation::create([
                        'order_id' => $order->id,
                        'order_item_id' => $item->id,
                        'allocation_type' => 'warehouse',
                        'source_code' => $yeDestinationCode,
                        'reserved_qty' => $qtyCoveredByLocal,
                        'fulfilled_qty' => 0,
                        'canceled_qty' => 0,
                        'state' => 'reserved',
                        'version' => 1,
                    ]);
                }

                // Determine demand state: Check if store metadata is missing or conflicting
                $isMetadataValid = ($classification['metadata_status'] === 'valid' && ! empty($classification['supplier_store_id']));

                if ($qtyRequiredExternal <= 0) {
                    $initialState = ProcurementDemand::STATE_LOCALLY_COVERED;
                } elseif (! $isMetadataValid) {
                    $initialState = ProcurementDemand::STATE_SUPPLIER_EXCEPTION;
                } else {
                    $initialState = ProcurementDemand::STATE_OPEN_FOR_BATCHING;
                }

                $demand = ProcurementDemand::create([
                    'order_id' => $order->id,
                    'order_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'variant_product_id' => $item->product?->parent_id ? $item->product_id : null,
                    'provider' => $classification['provider'],
                    'provider_account_id' => $classification['provider_account_id'],
                    'supplier_store_id' => $classification['supplier_store_id'],
                    'supplier_store_name' => $classification['supplier_store_name'],
                    'supplier_product_id' => $classification['supplier_product_id'],
                    'supplier_sku_id' => $classification['supplier_sku_id'],
                    'destination_source_code' => $yeDestinationCode,
                    'order_currency_code' => 'USD',
                    'supplier_currency_code' => $classification['currency'],
                    'qty_requested' => $qtyRequested,
                    'qty_covered_by_local' => $qtyCoveredByLocal,
                    'qty_required_external' => $qtyRequiredExternal,
                    'qty_batched' => 0,
                    'qty_ordered_external' => 0,
                    'qty_received_good' => 0,
                    'qty_cancelled' => 0,
                    'state' => $initialState,
                    'source_snapshot' => $classification['source_snapshot'],
                    'eligibility_snapshot' => [
                        'order_status' => $order->status,
                        'payment_method' => $order->payment?->method,
                        'is_cod' => strtolower((string) $order->payment?->method) === 'cashondelivery',
                        'metadata_status' => $classification['metadata_status'],
                        'exception_reason' => $classification['exception_reason'],
                        'processed_at' => now()->toIso8601String(),
                    ],
                    'active_fingerprint' => $fingerprint,
                    'lock_version' => 1,
                ]);

                $auditAction = ($initialState === ProcurementDemand::STATE_SUPPLIER_EXCEPTION)
                    ? 'demand_exception_recorded'
                    : 'demand_created';

                ProcurementAuditLog::create([
                    'auditable_type' => ProcurementDemand::class,
                    'auditable_id' => $demand->id,
                    'action' => $auditAction,
                    'old_state' => null,
                    'new_state' => $initialState,
                    'details' => [
                        'order_id' => $order->id,
                        'order_item_id' => $item->id,
                        'qty_requested' => $qtyRequested,
                        'qty_covered_by_local' => $qtyCoveredByLocal,
                        'qty_required_external' => $qtyRequiredExternal,
                        'currency' => $classification['currency'],
                        'metadata_status' => $classification['metadata_status'],
                        'exception_reason' => $classification['exception_reason'],
                    ],
                    'correlation_id' => "order-{$order->id}-demand-{$demand->id}",
                ]);

                $createdDemands[] = $demand;
            }

            return $createdDemands;
        });
    }
}
