<?php

namespace Webkul\Fulfillment\Listeners;

use Illuminate\Support\Facades\Log;

class NotificationListener
{
    /**
     * Handle critical failure notifications.
     *
     * @param  string  $eventName
     * @param  array  $payload
     * @param  string  $correlationId
     * @param  string  $causationId
     * @return void
     */
    public function handle(string $eventName, array $payload, string $correlationId, string $causationId, string $outboxEventId = null): void
    {
        $message = sprintf(
            "ALERT: Fulfillment failure event [%s] received. Correlation: %s. Details: %s",
            $eventName,
            $correlationId,
            json_encode($payload)
        );

        Log::warning($message);

        // In a real system, this would queue a mail or dispatch to Slack channel.
        // We write to a temporary/audit variable or system logs for integration testing.
        session()->flash('fulfillment_alert', $message);
    }
}
