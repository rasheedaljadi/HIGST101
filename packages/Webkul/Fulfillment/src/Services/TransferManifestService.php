<?php

namespace Webkul\Fulfillment\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Webkul\Fulfillment\Enums\ReceiptItemCondition;
use Webkul\Fulfillment\Enums\TransferStatus;
use Webkul\Fulfillment\Models\InventoryTransferManifest;
use Webkul\Fulfillment\Models\InventoryTransferManifestItem;
use Webkul\Inventory\Models\InventorySource;

class TransferManifestService
{
    /**
     * Create a new cross-border transfer manifest with strict idempotency and transaction safety.
     *
     * @throws Exception
     */
    public function createManifest(array $data, int $actorId): InventoryTransferManifest
    {
        $idempotencyKey = $data['idempotency_key'] ?? ('TRF_IDEMP_'.Str::upper(Str::random(16)));

        return DB::transaction(function () use ($data, $actorId, $idempotencyKey) {
            // 1. Check idempotency: Return existing manifest if already registered
            $existing = InventoryTransferManifest::where('idempotency_key', $idempotencyKey)
                ->with('items')
                ->first();

            if ($existing) {
                Log::channel('fulfillment')->info("Transfer manifest already exists for idempotency_key {$idempotencyKey}. Returning existing record.");

                return $existing;
            }

            // 2. Validate Source & Destination
            $sourceId = $data['source_inventory_source_id'] ?? null;
            $destinationId = $data['destination_inventory_source_id'] ?? null;

            if (! $sourceId || ! $destinationId) {
                throw new Exception('Source and Destination inventory sources are required.');
            }

            if ($sourceId === $destinationId) {
                throw new Exception('Source and Destination inventory sources must be distinct.');
            }

            $source = InventorySource::findOrFail($sourceId);
            $destination = InventorySource::findOrFail($destinationId);

            // 3. Generate Manifest Number
            $manifestNumber = $data['manifest_number'] ?? $this->generateManifestNumber();

            // 4. Create Transfer Manifest header
            $manifest = InventoryTransferManifest::create([
                'manifest_number' => $manifestNumber,
                'idempotency_key' => $idempotencyKey,
                'source_inventory_source_id' => $source->id,
                'destination_inventory_source_id' => $destination->id,
                'status' => $data['status'] ?? TransferStatus::DRAFT->value,
                'tracking_number' => $data['tracking_number'] ?? null,
                'carrier_name' => $data['carrier_name'] ?? null,
                'total_packages' => $data['total_packages'] ?? 1,
                'total_items_count' => count($data['items'] ?? []),
                'dispatched_at' => isset($data['status']) && $data['status'] === TransferStatus::IN_TRANSIT->value ? now() : null,
                'estimated_arrival_at' => $data['estimated_arrival_at'] ?? null,
                'created_by_admin_id' => $actorId,
                'notes' => $data['notes'] ?? null,
            ]);

            // 5. Create Transfer Manifest Items
            $items = $data['items'] ?? [];
            if (empty($items)) {
                throw new Exception('A transfer manifest must contain at least one item.');
            }

            foreach ($items as $item) {
                $qtyShipped = (int) ($item['qty_shipped'] ?? $item['qty'] ?? 0);
                if ($qtyShipped <= 0) {
                    throw new Exception("Invalid quantity for SKU {$item['sku']}. Must be greater than 0.");
                }

                InventoryTransferManifestItem::create([
                    'inventory_transfer_manifest_id' => $manifest->id,
                    'product_id' => $item['product_id'],
                    'order_id' => $item['order_id'] ?? null,
                    'order_item_id' => $item['order_item_id'] ?? null,
                    'order_allocation_id' => $item['order_allocation_id'] ?? null,
                    'purchase_order_id' => $item['purchase_order_id'] ?? null,
                    'sku' => $item['sku'],
                    'qty_shipped' => $qtyShipped,
                    'qty_received_good' => 0,
                    'qty_received_damaged' => 0,
                    'qty_received_missing' => 0,
                    'item_condition' => ReceiptItemCondition::GOOD->value,
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            Log::channel('fulfillment')->info("Created Transfer Manifest #{$manifest->manifest_number} (ID: {$manifest->id}) with ".count($items).' items.');

            return $manifest->load('items');
        });
    }

    /**
     * Dispatch an existing draft transfer manifest.
     *
     * @throws Exception
     */
    public function dispatchManifest(
        int $manifestId,
        int $actorId,
        ?string $trackingNumber = null,
        ?string $carrierName = null
    ): InventoryTransferManifest {
        return DB::transaction(function () use ($manifestId, $actorId, $trackingNumber, $carrierName) {
            $manifest = InventoryTransferManifest::lockForUpdate()->findOrFail($manifestId);

            if ($manifest->status === TransferStatus::IN_TRANSIT) {
                return $manifest;
            }

            if ($manifest->status->isFinal()) {
                throw new Exception("Cannot dispatch manifest #{$manifest->manifest_number} in final status '{$manifest->status->value}'.");
            }

            $manifest->status = TransferStatus::IN_TRANSIT;
            $manifest->dispatched_at = now();
            if ($trackingNumber) {
                $manifest->tracking_number = $trackingNumber;
            }
            if ($carrierName) {
                $manifest->carrier_name = $carrierName;
            }
            $manifest->save();

            Event::dispatch('inventory.transfer_manifest.in_transit', $manifest);

            Log::channel('fulfillment')->info("Dispatched Transfer Manifest #{$manifest->manifest_number} by Admin #{$actorId}.");

            return $manifest;
        });
    }

    /**
     * Generate sequential manifest number.
     */
    protected function generateManifestNumber(): string
    {
        $prefix = 'TRF-YE-'.date('Ymd').'-';
        $random = Str::upper(Str::random(4));

        return $prefix.$random;
    }
}
