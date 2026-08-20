<?php

namespace Webkul\Procurement\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
            $yeDropshipSource = InventorySource::where('code', config('procurement.destination_source_code', 'hayest_dropship_ye'))->first();
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

                // 2. Imported Product Rule: Consume owned stock in hayest_dropship_ye first
                $availableYeStock = 0;
                if ($yeDropshipSource) {
                    $inv = ProductInventory::where('product_id', $item->product_id)
                        ->where('inventory_source_id', $yeDropshipSource->id)
                        ->first();
                    $availableYeStock = max(0, (int) ($inv?->qty ?? 0));
                }

                $qtyCoveredByLocal = min($availableYeStock, $qtyRequested);
                $qtyRequiredExternal = max(0, $qtyRequested - $qtyCoveredByLocal);

                // Uniqueness fingerprint for active demand
                $fingerprint = hash('sha256', "demand|{$order->id}|{$item->id}|{$classification['provider']}|{$classification['supplier_sku_id']}");

                /** @var ProcurementDemand|null $existingDemand */
                $existingDemand = ProcurementDemand::where('active_fingerprint', $fingerprint)
                    ->lockForUpdate()
                    ->first();

                if ($existingDemand) {
                    $createdDemands[] = $existingDemand;

                    continue;
                }

                $initialState = ($qtyRequiredExternal > 0)
                    ? ProcurementDemand::STATE_OPEN_FOR_BATCHING
                    : ProcurementDemand::STATE_LOCALLY_COVERED;

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
                    'destination_source_code' => config('procurement.destination_source_code', 'hayest_dropship_ye'),
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
                        'processed_at' => now()->toIso8601String(),
                    ],
                    'active_fingerprint' => $fingerprint,
                    'lock_version' => 1,
                ]);

                ProcurementAuditLog::create([
                    'auditable_type' => ProcurementDemand::class,
                    'auditable_id' => $demand->id,
                    'action' => 'demand_created',
                    'old_state' => null,
                    'new_state' => $initialState,
                    'details' => [
                        'order_id' => $order->id,
                        'order_item_id' => $item->id,
                        'qty_requested' => $qtyRequested,
                        'qty_covered_by_local' => $qtyCoveredByLocal,
                        'qty_required_external' => $qtyRequiredExternal,
                        'currency' => $classification['currency'],
                    ],
                    'correlation_id' => "order-{$order->id}-demand-{$demand->id}",
                ]);

                $createdDemands[] = $demand;
            }

            return $createdDemands;
        });
    }
}
