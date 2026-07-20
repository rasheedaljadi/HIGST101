<?php

namespace Webkul\Fulfillment\Services\Domain;

use Carbon\Carbon;
use Webkul\Fulfillment\DataObjects\EventProcessingResult;
use Webkul\Fulfillment\Models\ProcessedEventProxy;

class EventDeduplicationService
{
    /**
     * Validate and log an event.
     *
     * @param  string  $provider
     * @param  string  $eventId
     * @param  string  $eventName
     * @param  \Carbon\Carbon  $occurredAt
     * @return \Webkul\Fulfillment\DataObjects\EventProcessingResult
     */
    public function processEvent(string $provider, string $eventId, string $eventName, Carbon $occurredAt): EventProcessingResult
    {
        // 1. Check for Duplicate
        $existing = ProcessedEventProxy::where('provider', $provider)
            ->where('event_id', $eventId)
            ->first();

        if ($existing) {
            return new EventProcessingResult(EventProcessingResult::STATUS_DUPLICATE);
        }

        // 2. Check for Stale (Out-of-Order) Event
        $latestEvent = ProcessedEventProxy::where('provider', $provider)
            ->orderBy('processed_at', 'desc')
            ->first();

        if ($latestEvent && Carbon::parse($latestEvent->processed_at)->gt($occurredAt)) {
            return new EventProcessingResult(EventProcessingResult::STATUS_STALE);
        }

        // 3. Accept event and persist deduplication record
        ProcessedEventProxy::create([
            'provider'     => $provider,
            'event_id'     => $eventId,
            'event_name'   => $eventName,
            'processed_at' => $occurredAt,
        ]);

        return new EventProcessingResult(EventProcessingResult::STATUS_ACCEPTED);
    }
}
