<?php

namespace Webkul\Inventory\Services;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Webkul\Inventory\Models\InventoryMovement;
use Webkul\Inventory\Models\InventorySource;

class InventoryMovementService
{
    /**
     * Resolve inventory source dynamically by code.
     */
    public function getSourceByCode(string $code): InventorySource
    {
        $source = InventorySource::where('code', $code)->first();

        if (! $source && $code === 'hayest_central') {
            $source = InventorySource::where('code', 'hayest_dropship_ye')->first()
                ?: InventorySource::where('code', 'hayest_internal_ye')->first();
        }

        if (! $source) {
            throw new ModelNotFoundException("Inventory source '{$code}' not found.");
        }

        return $source;
    }

    /**
     * Record an inventory movement inside an atomic transaction.
     */
    public function recordMovement(array $data): InventoryMovement
    {
        // Check if movement with idempotency key already exists
        if (! empty($data['idempotency_key'])) {
            $existing = InventoryMovement::where('idempotency_key', $data['idempotency_key'])->first();
            if ($existing) {
                return $existing;
            }
        }

        return DB::transaction(function () use ($data) {
            return InventoryMovement::create([
                'movement_type' => $data['movement_type'],
                'product_id' => $data['product_id'],
                'sku' => $data['sku'],
                'quantity' => (int) $data['quantity'],
                'source_inventory_source_id' => $data['source_inventory_source_id'] ?? null,
                'target_inventory_source_id' => $data['target_inventory_source_id'] ?? null,
                'order_id' => $data['order_id'] ?? null,
                'order_item_id' => $data['order_item_id'] ?? null,
                'purchase_order_id' => $data['purchase_order_id'] ?? null,
                'purchase_order_item_id' => $data['purchase_order_item_id'] ?? null,
                'shipment_id' => $data['shipment_id'] ?? null,
                'delivery_assignment_id' => $data['delivery_assignment_id'] ?? null,
                'actor_id' => $data['actor_id'] ?? null,
                'actor_type' => $data['actor_type'] ?? 'system',
                'reference_event' => $data['reference_event'] ?? null,
                'job_class' => $data['job_class'] ?? null,
                'idempotency_key' => $data['idempotency_key'],
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }

    /**
     * Record actual physical stock in to Hayest Central warehouse.
     */
    public function recordHayestStockIn(
        int $productId,
        string $sku,
        int $quantity,
        int $targetSourceId,
        ?int $orderId,
        ?int $orderItemId,
        ?int $purchaseOrderId,
        ?int $purchaseOrderItemId,
        string $idempotencyKey,
        ?int $actorId = null,
        string $actorType = 'system',
        ?string $referenceEvent = null,
        ?string $jobClass = null,
        ?string $notes = null
    ): InventoryMovement {
        return DB::transaction(function () use (
            $productId,
            $sku,
            $quantity,
            $targetSourceId,
            $orderId,
            $orderItemId,
            $purchaseOrderId,
            $purchaseOrderItemId,
            $idempotencyKey,
            $actorId,
            $actorType,
            $referenceEvent,
            $jobClass,
            $notes
        ) {
            // Check idempotency
            $existing = InventoryMovement::where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return $existing;
            }

            // Increase product_inventories for target inventory source
            $currentStock = DB::table('product_inventories')
                ->where('product_id', $productId)
                ->where('inventory_source_id', $targetSourceId)
                ->lockForUpdate()
                ->first();

            if ($currentStock) {
                DB::table('product_inventories')
                    ->where('id', $currentStock->id)
                    ->update([
                        'qty' => $currentStock->qty + $quantity,
                    ]);
            } else {
                DB::table('product_inventories')->insert([
                    'product_id' => $productId,
                    'inventory_source_id' => $targetSourceId,
                    'qty' => $quantity,
                ]);
            }

            // Create the movement record
            return InventoryMovement::create([
                'movement_type' => 'hayest_stock_in',
                'product_id' => $productId,
                'sku' => $sku,
                'quantity' => $quantity,
                'source_inventory_source_id' => null,
                'target_inventory_source_id' => $targetSourceId,
                'order_id' => $orderId,
                'order_item_id' => $orderItemId,
                'purchase_order_id' => $purchaseOrderId,
                'purchase_order_item_id' => $purchaseOrderItemId,
                'actor_id' => $actorId,
                'actor_type' => $actorType,
                'reference_event' => $referenceEvent,
                'job_class' => $jobClass,
                'idempotency_key' => $idempotencyKey,
                'notes' => $notes ?? 'Physical stock-in to Hayest Central warehouse',
            ]);
        });
    }

    /**
     * Record delivery failure restock to Hayest Central.
     */
    public function recordDeliveryFailureReturn(
        int $productId,
        string $sku,
        int $quantity,
        int $targetSourceId,
        int $orderId,
        ?int $orderItemId,
        int $deliveryAssignmentId,
        string $idempotencyKey,
        int $actorId,
        ?string $notes = null
    ): InventoryMovement {
        return DB::transaction(function () use (
            $productId,
            $sku,
            $quantity,
            $targetSourceId,
            $orderId,
            $orderItemId,
            $deliveryAssignmentId,
            $idempotencyKey,
            $actorId,
            $notes
        ) {
            $existing = InventoryMovement::where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return $existing;
            }

            // Increase product_inventories
            $currentStock = DB::table('product_inventories')
                ->where('product_id', $productId)
                ->where('inventory_source_id', $targetSourceId)
                ->lockForUpdate()
                ->first();

            if ($currentStock) {
                DB::table('product_inventories')
                    ->where('id', $currentStock->id)
                    ->update([
                        'qty' => $currentStock->qty + $quantity,
                    ]);
            } else {
                DB::table('product_inventories')->insert([
                    'product_id' => $productId,
                    'inventory_source_id' => $targetSourceId,
                    'qty' => $quantity,
                ]);
            }

            return InventoryMovement::create([
                'movement_type' => 'delivery_failure_return',
                'product_id' => $productId,
                'sku' => $sku,
                'quantity' => $quantity,
                'source_inventory_source_id' => null,
                'target_inventory_source_id' => $targetSourceId,
                'order_id' => $orderId,
                'order_item_id' => $orderItemId,
                'delivery_assignment_id' => $deliveryAssignmentId,
                'actor_id' => $actorId,
                'actor_type' => 'admin',
                'idempotency_key' => $idempotencyKey,
                'notes' => $notes ?? 'Restocked to Hayest local inventory after delivery failure approval',
            ]);
        });
    }

    /**
     * Release item from quarantine to a salable physical inventory source with audit trail.
     */
    public function releaseFromQuarantine(
        int $productId,
        string $sku,
        int $quantity,
        int $quarantineSourceId,
        int $targetSalableSourceId,
        int $actorId,
        string $idempotencyKey,
        ?string $reason = null
    ): InventoryMovement {
        return DB::transaction(function () use (
            $productId,
            $sku,
            $quantity,
            $quarantineSourceId,
            $targetSalableSourceId,
            $actorId,
            $idempotencyKey,
            $reason
        ) {
            $existing = InventoryMovement::where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return $existing;
            }

            // 1. Deduct from quarantine source
            $quarantineStock = DB::table('product_inventories')
                ->where('product_id', $productId)
                ->where('inventory_source_id', $quarantineSourceId)
                ->lockForUpdate()
                ->first();

            if (! $quarantineStock || $quarantineStock->qty < $quantity) {
                throw new \Exception("Insufficient quantity in quarantine source to release SKU {$sku}.");
            }

            DB::table('product_inventories')
                ->where('id', $quarantineStock->id)
                ->update([
                    'qty' => $quarantineStock->qty - $quantity,
                ]);

            // 2. Increase target salable source
            $salableStock = DB::table('product_inventories')
                ->where('product_id', $productId)
                ->where('inventory_source_id', $targetSalableSourceId)
                ->lockForUpdate()
                ->first();

            if ($salableStock) {
                DB::table('product_inventories')
                    ->where('id', $salableStock->id)
                    ->update([
                        'qty' => $salableStock->qty + $quantity,
                    ]);
            } else {
                DB::table('product_inventories')->insert([
                    'product_id' => $productId,
                    'inventory_source_id' => $targetSalableSourceId,
                    'qty' => $quantity,
                ]);
            }

            // 3. Create movement record
            return InventoryMovement::create([
                'movement_type' => 'quarantine_release',
                'product_id' => $productId,
                'sku' => $sku,
                'quantity' => $quantity,
                'source_inventory_source_id' => $quarantineSourceId,
                'target_inventory_source_id' => $targetSalableSourceId,
                'actor_id' => $actorId,
                'actor_type' => 'admin',
                'reference_event' => 'QuarantineReleased',
                'job_class' => self::class,
                'idempotency_key' => $idempotencyKey,
                'notes' => $reason ?: "Released {$quantity} of SKU {$sku} from quarantine to salable stock",
            ]);
        });
    }
}
