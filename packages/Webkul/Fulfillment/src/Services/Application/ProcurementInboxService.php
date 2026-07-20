<?php

namespace Webkul\Fulfillment\Services\Application;

use Illuminate\Support\Facades\DB;
use Webkul\Fulfillment\Services\Domain\EventSchemaRegistry;

class ProcurementInboxService
{
    public function __construct(
        protected WebhookProcessingLockService $lockService,
        protected EventSchemaRegistry $schemaRegistry
    ) {}

    /**
     * Process incoming webhook event.
     */
    public function receive(
        string $provider,
        string $eventId,
        string $eventType,
        array $payload,
        callable $processor
    ): bool {
        $hash = hash('sha256', json_encode($payload));

        $exists = DB::table('procurement_inbox_events')
            ->where('provider', $provider)
            ->where('event_id', $eventId)
            ->exists();

        if ($exists) {
            return false;
        }

        if (! $this->lockService->acquire($eventId)) {
            return false;
        }

        try {
            DB::table('procurement_inbox_events')->insert([
                'provider'          => $provider,
                'event_id'          => $eventId,
                'event_type'        => $eventType,
                'external_order_id' => $payload['aliexpress_order_id'] ?? null,
                'payload_hash'      => $hash,
                'payload'           => json_encode($payload),
                'status'            => 'pending',
                'received_at'       => now(),
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);

            $processor($payload);

            DB::table('procurement_inbox_events')
                ->where('provider', $provider)
                ->where('event_id', $eventId)
                ->update([
                    'status'       => 'processed',
                    'processed_at' => now(),
                    'updated_at'   => now(),
                ]);

            return true;
        } finally {
            $this->lockService->release($eventId);
        }
    }
}
