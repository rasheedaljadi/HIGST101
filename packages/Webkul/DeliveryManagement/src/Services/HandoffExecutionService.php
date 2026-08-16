<?php

namespace Webkul\DeliveryManagement\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\DeliveryManagement\Models\DeliveryAssignment;
use Webkul\Inventory\Services\InventoryMovementService;
use Webkul\Sales\Contracts\Order;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Repositories\ShipmentRepository;

class HandoffExecutionService
{
    public function __construct(
        protected InventoryMovementService $inventoryMovementService,
        protected ShipmentRepository $shipmentRepository,
        protected OrderRepository $orderRepository,
        protected ShippingMethodAdapter $shippingMethodAdapter
    ) {}

    /**
     * Execute handoff from central inventory (hayest_central) to delivery party (courier / delivery point).
     *
     * @throws Exception
     */
    public function executeHandoff(
        int $orderId,
        int $actorId,
        string $actorType = 'admin',
        ?string $idempotencyKey = null
    ): DeliveryAssignment {
        return DB::transaction(function () use ($orderId, $actorId, $actorType, $idempotencyKey) {
            // 1. Retrieve DeliveryAssignment with row lock
            /** @var DeliveryAssignment|null $assignment */
            $assignment = DeliveryAssignment::where('order_id', $orderId)
                ->lockForUpdate()
                ->first();

            if (! $assignment) {
                throw new Exception("DeliveryAssignment not found for Order #{$orderId}");
            }

            // 2. Idempotency check: If already handed over / picked up, return existing assignment
            if (in_array($assignment->status, [
                DeliveryAssignment::STATUS_PICKED_UP,
                DeliveryAssignment::STATUS_OUT_FOR_DELIVERY,
                DeliveryAssignment::STATUS_ARRIVED_AT_POINT,
                DeliveryAssignment::STATUS_DELIVERED,
            ], true)) {
                if ($idempotencyKey && $assignment->idempotency_key === $idempotencyKey) {
                    return $assignment;
                }

                if ($assignment->shipment_id) {
                    return $assignment;
                }
            }

            // 3. Status validation: Assignment must be ready or assigned
            if (! in_array($assignment->status, [
                DeliveryAssignment::STATUS_READY_FOR_ASSIGNMENT,
                DeliveryAssignment::STATUS_ASSIGNED,
            ], true)) {
                throw new Exception("Cannot execute handoff for assignment with status '{$assignment->status}'");
            }

            // 4. Source Lookup: hayest_central only (reject 'default' or any external source)
            $hayestSource = $this->inventoryMovementService->getSourceByCode('hayest_central');

            if (! $hayestSource) {
                throw new Exception("Critical: Central inventory source 'hayest_central' not found.");
            }

            if ($hayestSource->code === 'default') {
                throw new Exception("Security Violation: External source 'default' is prohibited for customer handoff.");
            }

            /** @var Order|null $order */
            $order = $this->orderRepository->find($orderId);

            if (! $order) {
                throw new Exception("Order #{$orderId} not found");
            }

            // 5. Verification: Order must have received stock and rebound allocation to warehouse:hayest_central
            if (DB::getSchemaBuilder()->hasTable('order_allocations')) {
                $allocations = DB::table('order_allocations')
                    ->where('order_id', $orderId)
                    ->where('state', '!=', 'canceled')
                    ->get();

                if ($allocations->isNotEmpty()) {
                    foreach ($allocations as $alloc) {
                        if ($alloc->allocation_type !== 'warehouse' || $alloc->source_code !== 'hayest_central') {
                            throw new Exception("Handoff Rejected: Order #{$orderId} allocation is still with supplier '{$alloc->source_code}'. Inbound receipt and allocation rebind to hayest_central required first.");
                        }
                    }
                }
            }

            // 6. Check stock availability in product_inventories for hayest_central
            $itemsMap = [];
            foreach ($order->items as $item) {
                $qtyToShip = $item->qty_to_ship;

                if ($qtyToShip <= 0) {
                    continue;
                }

                $availableStock = DB::table('product_inventories')
                    ->where('product_id', $item->product_id)
                    ->where('inventory_source_id', $hayestSource->id)
                    ->lockForUpdate()
                    ->value('qty') ?? 0;

                if ($availableStock < $qtyToShip) {
                    throw new Exception("Insufficient stock in 'hayest_central' for product #{$item->product_id} (SKU: {$item->sku}). Required: {$qtyToShip}, Available: {$availableStock}");
                }

                $itemsMap[$item->id] = [$hayestSource->id => $qtyToShip];
            }

            if (empty($itemsMap)) {
                throw new Exception("Order #{$orderId} has no items available to ship.");
            }

            // 7. Create Shipment once from hayest_central source
            $trackNumber = 'HAYEST-'.str_pad((string) $order->id, 8, '0', STR_PAD_LEFT);
            $shipmentData = [
                'order_id' => $order->id,
                'shipment' => [
                    'carrier_title' => $order->shipping_title ?: 'Hayest Express Delivery',
                    'track_number' => $trackNumber,
                    'source' => $hayestSource->id,
                    'items' => $itemsMap,
                ],
            ];

            $shipment = $this->shipmentRepository->create($shipmentData);

            if (! $shipment || ! $shipment->id) {
                throw new Exception("Failed to generate Shipment record for Order #{$orderId}");
            }

            // 8. Record single audit movement in inventory_movements (without double deducting)
            foreach ($order->items as $item) {
                $qtyShipped = $item->qty_shipped > 0 ? $item->qty_shipped : ($itemsMap[$item->id][$hayestSource->id] ?? 0);

                if ($qtyShipped <= 0) {
                    continue;
                }

                $this->inventoryMovementService->recordMovement([
                    'movement_type' => 'handoff_to_delivery_party',
                    'product_id' => $item->product_id,
                    'sku' => $item->sku,
                    'quantity' => $qtyShipped,
                    'source_inventory_source_id' => $hayestSource->id,
                    'order_id' => $order->id,
                    'order_item_id' => $item->id,
                    'shipment_id' => $shipment->id,
                    'delivery_assignment_id' => $assignment->id,
                    'actor_id' => $actorId,
                    'actor_type' => $actorType,
                    'idempotency_key' => 'HANDOFF-'.$order->id.'-'.$item->id.'-'.$shipment->id,
                    'notes' => 'Handoff from central warehouse to delivery party. Track: '.$trackNumber,
                ]);
            }

            // 9. Update DeliveryAssignment to picked_up
            $assignment->update([
                'shipment_id' => $shipment->id,
                'status' => DeliveryAssignment::STATUS_PICKED_UP,
                'picked_up_at' => now(),
                'idempotency_key' => $idempotencyKey ?: $assignment->idempotency_key,
            ]);

            Log::info("[Delivery] Handoff completed for Order #{$orderId}, Shipment #{$shipment->id}, Assignment #{$assignment->id}");

            return $assignment->fresh();
        });
    }
}
