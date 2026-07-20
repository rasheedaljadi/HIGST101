<?php

namespace Webkul\Fulfillment\Services\Application;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OutboxEventProcessor
{
    /**
     * Map of outbox event names to their listener classes.
     *
     * @var array<string, array<string>>
     */
    protected array $listenerMap = [
        'OrderAllocationReserved' => [
            \Webkul\Fulfillment\Listeners\LedgerListener::class,
            \Webkul\Fulfillment\Listeners\TimelineListener::class,
        ],
        'OrderAllocationReleased' => [
            \Webkul\Fulfillment\Listeners\LedgerListener::class,
            \Webkul\Fulfillment\Listeners\TimelineListener::class,
        ],
        'PurchaseOrderCreated' => [
            \Webkul\Fulfillment\Listeners\TimelineListener::class,
        ],
        'SupplierOrderSubmitted' => [
            \Webkul\Fulfillment\Listeners\TimelineListener::class,
            \Webkul\Fulfillment\Listeners\LedgerListener::class,
        ],
        'SupplierOrderPaid' => [
            \Webkul\Fulfillment\Listeners\LedgerListener::class,
        ],
        'SupplierOrderRefunded' => [
            \Webkul\Fulfillment\Listeners\LedgerListener::class,
        ],
        'SupplierOrderFailed' => [
            \Webkul\Fulfillment\Listeners\TimelineListener::class,
            \Webkul\Fulfillment\Listeners\NotificationListener::class,
        ],
        'CustomerOrderFlagged' => [
            \Webkul\Fulfillment\Listeners\NotificationListener::class,
        ],
        'VariantIdentityChanged' => [
            \Webkul\Fulfillment\Listeners\AliExpressSyncReviewListener::class,
        ],
        'SupplierPriceChanged' => [
            \Webkul\Fulfillment\Listeners\AliExpressSyncReviewListener::class,
            \Webkul\Fulfillment\Listeners\CatalogProjectionListener::class,
        ],
        'SupplierStockChanged' => [
            \Webkul\Fulfillment\Listeners\AliExpressStockListener::class,
        ],
    ];

    /**
     * Process all pending outbox events.
     *
     * @param  int  $maxAttempts
     * @return int Number of processed events
     */
    public function processPending(int $maxAttempts = 3): int
    {
        $processedCount = 0;

        // Perform transactional select with pessimistic lock & skip locked to support concurrent workers
        $events = DB::transaction(function () {
            $query = DB::table('domain_outbox_events')
                ->where('status', 'pending');

            // Perform lockForUpdate query to acquire pessimistic lock with skip locked
            if (DB::getDriverName() === 'mysql') {
                try {
                    $rows = $query->lockForUpdate()->skipLocked()->get();
                } catch (\Throwable $e) {
                    $rows = $query->lockForUpdate()->get();
                }
            } else {
                $rows = $query->lockForUpdate()->get();
            }

            if ($rows->isEmpty()) {
                return $rows;
            }

            // Immediately mark as processing inside the same transaction
            DB::table('domain_outbox_events')
                ->whereIn('id', $rows->pluck('id')->toArray())
                ->update(['status' => 'processing']);

            return $rows;
        });

        foreach ($events as $event) {
            $eventName = $event->event_name;
            $payload = json_decode($event->payload, true);
            $listeners = $this->listenerMap[$eventName] ?? [];
            
            $allOk = true;

            foreach ($listeners as $listenerClass) {
                try {
                    $listener = app($listenerClass);
                    
                    // Call the listener handle method with stable outbox event_id
                    $listener->handle($eventName, $payload, $event->correlation_id, $event->causation_id, $event->event_id);
                } catch (\Exception $e) {
                    $allOk = false;
                    Log::error("Outbox listener failed: " . $listenerClass . " - " . $e->getMessage());

                    // Log failed attempt details to domain_outbox_event_attempts
                    DB::table('domain_outbox_event_attempts')->insert([
                        'outbox_event_id' => $event->id,
                        'listener'        => $listenerClass,
                        'attempt_number'  => $event->attempts + 1,
                        'error_message'   => $e->getMessage() . "\n" . $e->getTraceAsString(),
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                }
            }

            if ($allOk) {
                DB::table('domain_outbox_events')
                    ->where('id', $event->id)
                    ->update([
                        'status'   => 'processed',
                        'attempts' => $event->attempts + 1,
                    ]);
                $processedCount++;
            } else {
                $newAttempts = $event->attempts + 1;
                $newStatus = $newAttempts >= $maxAttempts ? 'failed' : 'pending';

                DB::table('domain_outbox_events')
                    ->where('id', $event->id)
                    ->update([
                        'status'   => $newStatus,
                        'attempts' => $newAttempts,
                    ]);
            }
        }

        return $processedCount;
    }
}
