<?php

namespace Webkul\Fulfillment\Services\Application;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\Fulfillment\DataObjects\ChangeSet;

class SyncEventPublisher
{
    /**
     * Publish changeset to outbox table.
     */
    public function publish(ChangeSet $changeSet, string $syncRunId): int
    {
        $changes = $changeSet->getChanges();
        if (empty($changes)) {
            return 0;
        }

        // Define sort order: identityChanged => 1, priceChanged => 2, stockChanged => 3, others => 4
        $typeOrder = [
            'identityChanged' => 1,
            'priceChanged'    => 2,
            'stockChanged'    => 3,
            'removed'         => 4,
            'unavailable'     => 4,
        ];

        // Sort changes by variant_id first, then by typeOrder, ensuring deterministic order per variant
        usort($changes, function ($a, $b) use ($typeOrder) {
            if ($a['variant_id'] !== $b['variant_id']) {
                return ($a['variant_id'] ?? 0) <=> ($b['variant_id'] ?? 0);
            }

            $orderA = $typeOrder[$a['type']] ?? 99;
            $orderB = $typeOrder[$b['type']] ?? 99;

            return $orderA <=> $orderB;
        });

        $publishedCount = 0;

        foreach ($changes as $change) {
            $eventName = $this->mapTypeToEventName($change['type']);
            if (!$eventName) {
                continue;
            }

            // Append sync_run_id to event payload for trace correlation
            $payload = $change['payload'];
            $payload['sync_run_id'] = $syncRunId;
            $payload['event_version'] = 1;

            DB::table('domain_outbox_events')->insert([
                'event_id'       => (string) Str::uuid(),
                'event_name'     => $eventName,
                'event_version'  => 1,
                'aggregate_type' => 'ExternalProduct',
                'aggregate_id'   => (string) $changeSet->productId,
                'correlation_id' => $syncRunId,
                'causation_id'   => $syncRunId,
                'payload'        => json_encode($payload),
                'status'         => 'pending',
                'attempts'       => 0,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            $publishedCount++;
        }

        return $publishedCount;
    }

    private function mapTypeToEventName(string $type): ?string
    {
        return match ($type) {
            'identityChanged' => 'VariantIdentityChanged',
            'priceChanged'    => 'SupplierPriceChanged',
            'stockChanged'    => 'SupplierStockChanged',
            'removed'         => 'ExternalProductRemoved',
            'unavailable'     => 'ExternalProductUnavailable',
            default           => null,
        };
    }
}
