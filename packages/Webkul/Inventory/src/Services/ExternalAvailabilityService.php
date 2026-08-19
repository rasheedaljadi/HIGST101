<?php

namespace Webkul\Inventory\Services;

use Webkul\Inventory\Models\ExternalAvailabilitySnapshot;

class ExternalAvailabilityService
{
    /**
     * Record or update an external availability snapshot.
     * Decoupled from physical inventory tables and inventory_movements ledger.
     */
    public function syncSnapshot(array $data): ExternalAvailabilitySnapshot
    {
        return ExternalAvailabilitySnapshot::updateOrCreate(
            [
                'provider' => $data['provider'] ?? 'aliexpress',
                'external_sku' => $data['external_sku'],
            ],
            [
                'external_product_id' => $data['external_product_id'] ?? $data['external_sku'],
                'internal_product_id' => $data['internal_product_id'] ?? null,
                'available_quantity' => (int) ($data['available_quantity'] ?? 0),
                'price_usd' => $data['price_usd'] ?? null,
                'raw_payload' => $data['raw_payload'] ?? null,
                'synced_at' => now(),
                'sync_status' => $data['sync_status'] ?? 'active',
            ]
        );
    }

    /**
     * Retrieve current external availability for a product / SKU.
     */
    public function getAvailableQuantity(string $externalSku, string $provider = 'aliexpress'): int
    {
        $snapshot = ExternalAvailabilitySnapshot::where('provider', $provider)
            ->where('external_sku', $externalSku)
            ->where('sync_status', 'active')
            ->first();

        return $snapshot ? $snapshot->available_quantity : 0;
    }

    /**
     * Check if item is available externally.
     */
    public function isExternalAvailable(string $externalSku, int $requiredQty = 1, string $provider = 'aliexpress'): bool
    {
        return $this->getAvailableQuantity($externalSku, $provider) >= $requiredQty;
    }

    /**
     * Batch update multiple external availability snapshots.
     */
    public function batchSyncProviderAvailability(string $provider, array $items): int
    {
        $count = 0;
        foreach ($items as $item) {
            $item['provider'] = $provider;
            $this->syncSnapshot($item);
            $count++;
        }

        return $count;
    }
}
