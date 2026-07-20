<?php

namespace Webkul\Fulfillment\Handlers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\Fulfillment\Commands\CreatePurchaseOrderCommand;
use Webkul\Fulfillment\Models\PurchaseOrder;

class CreatePurchaseOrderHandler
{
    /**
     * Handle the purchase order creation command.
     *
     * @param  \Webkul\Fulfillment\Commands\CreatePurchaseOrderCommand  $command
     * @return \Webkul\Fulfillment\Models\PurchaseOrder
     */
    public function handle(CreatePurchaseOrderCommand $command): PurchaseOrder
    {
        return DB::transaction(function () use ($command) {
            $allocation = \Webkul\Fulfillment\Models\OrderAllocation::findOrFail($command->orderAllocationId);

            $idempotencyKey = hash('sha256', 'alloc-' . $command->orderAllocationId);

            $po = PurchaseOrder::firstOrCreate([
                'idempotency_key' => $idempotencyKey,
            ], [
                'order_id'           => $command->orderId,
                'provider'           => $command->providerCode,
                'internal_reference' => 'PO-' . strtoupper(Str::random(10)),
                'supplier_snapshot'  => $allocation->supplier_snapshot,
                'supplier_cost'      => $allocation->supplier_snapshot['supplier_cost'] ?? null,
                'supplier_currency'  => $allocation->supplier_snapshot['supplier_currency'] ?? null,
                'state'              => PurchaseOrder::STATE_PENDING,
            ]);

            if ($po->wasRecentlyCreated) {
                \Webkul\Fulfillment\Models\PurchaseOrderItem::firstOrCreate([
                    'purchase_order_id' => $po->id,
                    'order_item_id'     => $allocation->order_item_id,
                ], [
                    'aliexpress_product_id' => $allocation->supplier_snapshot['supplier_product_id'] ?? null,
                    'sku_id'                => $allocation->supplier_snapshot['supplier_sku_id'] ?? null,
                    'qty'                   => (int) $allocation->reserved_qty,
                    'supplier_unit_cost'    => $allocation->supplier_snapshot['supplier_cost'] ?? null,
                ]);
            }

            // Append domain event to outbox inside the same transaction only if newly created
            if ($po->wasRecentlyCreated) {
                DB::table('domain_outbox_events')->insert([
                    'event_id'       => (string) Str::uuid(),
                    'event_name'     => 'PurchaseOrderCreated',
                    'event_version'  => 1,
                    'aggregate_type' => 'PurchaseOrder',
                    'aggregate_id'   => (string) $po->id,
                    'correlation_id' => $command->correlationId,
                    'causation_id'   => $command->causationId,
                    'payload'        => json_encode([
                        'purchase_order_id'   => $po->id,
                        'order_id'            => $command->orderId,
                        'order_allocation_id' => $command->orderAllocationId,
                        'provider'            => $command->providerCode,
                        'internal_reference'  => $po->internal_reference,
                    ]),
                    'status'         => 'pending',
                    'attempts'       => 0,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }

            return $po;
        });
    }
}
