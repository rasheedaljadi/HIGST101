<?php

namespace Webkul\DeliveryManagement\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\DeliveryManagement\Models\DeliveryAssignment;
use Webkul\Inventory\Models\InventorySource;
use Webkul\Inventory\Services\InventoryMovementService;
use Webkul\Sales\Contracts\Order;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Repositories\ShipmentRepository;

class HandoffExecutionService
{
    public function __construct(
        protected OrderRepository $orderRepository,
        protected ShipmentRepository $shipmentRepository,
        protected InventoryMovementService $inventoryMovementService
    ) {}

    /**
     * Execute atomic handoff from official Yemen delivery source to delivery courier / point.
     * Enforces:
     * - Order must be allocated to a verified delivery-capable Yemen warehouse.
     * - Prohibits handoff from virtual_projection, sourcing_staging, or quarantine sources.
     * - Locks stock rows for update, records audit movement, creates shipment, and transitions status.
     *
     * @throws Exception
     */
    public function executeHandoff(
        int $orderId,
        int $actorId,
        string $actorType = 'supervisor',
        ?string $idempotencyKey = null
    ): array {
        return DB::transaction(function () use ($orderId, $actorId, $actorType, $idempotencyKey) {
            // 1. Lock DeliveryAssignment
            $assignment = DeliveryAssignment::where('order_id', $orderId)
                ->lockForUpdate()
                ->first();

            if (! $assignment) {
                throw new Exception("No delivery assignment found for Order #{$orderId}");
            }

            // 2. Status guard: idempotent check
            if (in_array($assignment->status, [
                DeliveryAssignment::STATUS_PICKED_UP,
                DeliveryAssignment::STATUS_OUT_FOR_DELIVERY,
                DeliveryAssignment::STATUS_ARRIVED_AT_POINT,
                DeliveryAssignment::STATUS_DELIVERED,
            ], true)) {
                Log::info("[Delivery] Handoff idempotency hit: Order #{$orderId} already in status '{$assignment->status}'");

                return [
                    'assignment' => $assignment,
                    'shipment' => $assignment->shipment,
                    'already_handed_over' => true,
                ];
            }

            // 3. Must be ready for assignment or assigned
            if (! in_array($assignment->status, [
                DeliveryAssignment::STATUS_READY_FOR_ASSIGNMENT,
                DeliveryAssignment::STATUS_ASSIGNED,
            ])) {
                throw new Exception("Cannot execute handoff for Order #{$orderId}: Current status '{$assignment->status}' is invalid.");
            }

            // 4. Resolve and validate Order
            /** @var Order|null $order */
            $order = $this->orderRepository->find($orderId);
            if (! $order) {
                throw new Exception("Order #{$orderId} not found");
            }

            // 5. Determine Delivery Source from Order Allocations or Canonical Fallback
            $deliverySource = $this->resolveAndValidateDeliverySource($orderId);

            // 6. Check stock availability in product_inventories for the resolved delivery source
            $itemsMap = [];
            foreach ($order->items as $item) {
                $qtyToShip = $item->qty_to_ship;
                if ($qtyToShip <= 0) {
                    continue;
                }

                $availableStock = DB::table('product_inventories')
                    ->where('product_id', $item->product_id)
                    ->where('inventory_source_id', $deliverySource->id)
                    ->lockForUpdate()
                    ->value('qty') ?? 0;

                if ($availableStock < $qtyToShip) {
                    throw new Exception("Insufficient stock in '{$deliverySource->code}' for product #{$item->product_id} (SKU: {$item->sku}). Required: {$qtyToShip}, Available: {$availableStock}");
                }

                $itemsMap[$item->id] = [$deliverySource->id => $qtyToShip];
            }

            if (empty($itemsMap)) {
                throw new Exception("Order #{$orderId} has no items available to ship.");
            }

            // 7. Create Shipment once from the verified delivery source
            $trackNumber = 'HAYEST-'.str_pad((string) $order->id, 8, '0', STR_PAD_LEFT);
            $shipmentData = [
                'order_id' => $order->id,
                'shipment' => [
                    'carrier_title' => $order->shipping_title ?: 'Hayest Express Delivery',
                    'track_number' => $trackNumber,
                    'source' => $deliverySource->id,
                    'items' => $itemsMap,
                ],
            ];

            $shipment = $this->shipmentRepository->create($shipmentData);
            if (! $shipment || ! $shipment->id) {
                throw new Exception("Failed to generate Shipment record for Order #{$orderId}");
            }

            // 8. Record single audit movement in inventory_movements
            foreach ($order->items as $item) {
                $qtyShipped = $item->qty_shipped > 0 ? $item->qty_shipped : ($itemsMap[$item->id][$deliverySource->id] ?? 0);
                if ($qtyShipped <= 0) {
                    continue;
                }

                $this->inventoryMovementService->recordMovement([
                    'movement_type' => 'handoff_to_delivery_party',
                    'product_id' => $item->product_id,
                    'sku' => $item->sku,
                    'quantity' => $qtyShipped,
                    'source_inventory_source_id' => $deliverySource->id,
                    'order_id' => $order->id,
                    'order_item_id' => $item->id,
                    'shipment_id' => $shipment->id,
                    'delivery_assignment_id' => $assignment->id,
                    'actor_id' => $actorId,
                    'actor_type' => $actorType,
                    'idempotency_key' => 'HANDOFF-'.$order->id.'-'.$item->id.'-'.$shipment->id,
                    'notes' => "Handoff from {$deliverySource->code} to delivery party. Track: {$trackNumber}",
                ]);
            }

            // 9. Update DeliveryAssignment to picked_up
            $assignment->update([
                'shipment_id' => $shipment->id,
                'status' => DeliveryAssignment::STATUS_PICKED_UP,
                'picked_up_at' => now(),
                'idempotency_key' => $idempotencyKey ?: $assignment->idempotency_key,
            ]);

            Log::info("[Delivery] Handoff completed for Order #{$orderId} from source '{$deliverySource->code}', Shipment #{$shipment->id}, Assignment #{$assignment->id}");

            return [
                'assignment' => $assignment->fresh(),
                'shipment' => $shipment,
                'already_handed_over' => false,
            ];
        });
    }

    /**
     * Resolve and validate delivery inventory source from order allocations or canonical defaults.
     * Enforces safety guards: blocks virtual_projection, sourcing_staging, quarantine, and non-delivery sources.
     *
     * @throws Exception
     */
    protected function resolveAndValidateDeliverySource(int $orderId): InventorySource
    {
        $allocatedSourceCode = null;

        if (DB::getSchemaBuilder()->hasTable('order_allocations')) {
            $allocations = DB::table('order_allocations')
                ->where('order_id', $orderId)
                ->where('state', '!=', 'canceled')
                ->get();

            if ($allocations->isNotEmpty()) {
                foreach ($allocations as $alloc) {
                    if ($alloc->allocation_type !== 'warehouse') {
                        throw new Exception("Handoff Rejected: Order #{$orderId} allocation is still with supplier '{$alloc->source_code}'. Inbound receipt and allocation rebind required first.");
                    }
                    $allocatedSourceCode = $alloc->source_code;
                }
            }
        }

        // Resolve source model
        $source = null;
        if ($allocatedSourceCode) {
            $source = InventorySource::where('code', $allocatedSourceCode)->first();
        }

        if (! $source) {
            $source = InventorySource::where('code', 'hayest_dropship_ye')->first()
                ?: InventorySource::where('code', 'hayest_internal_ye')->first()
                ?: InventorySource::where('code', 'hayest_central')->first();
        }

        if (! $source) {
            throw new Exception('Critical: No valid local delivery source available for handoff.');
        }

        // Security & Origin Safety Guards
        if ($source->code === 'default' || $source->code === 'aliexpress_source') {
            throw new Exception("Security Violation: Virtual projection source '{$source->code}' is prohibited for customer handoff.");
        }

        if ($source->code === 'hayest_dropship_sa') {
            throw new Exception("Security Violation: Saudi staging transit hub '{$source->code}' cannot fulfill local Yemen deliveries directly.");
        }

        if (str_contains($source->code, 'quarantine')) {
            throw new Exception("Security Violation: Quarantine source '{$source->code}' is strictly prohibited for customer fulfillment.");
        }

        // Structural flags guard
        if (isset($source->is_delivery_source) && ! (bool) $source->is_delivery_source) {
            throw new Exception("Security Violation: Inventory source '{$source->code}' is not licensed as a local delivery source.");
        }

        return $source;
    }
}
