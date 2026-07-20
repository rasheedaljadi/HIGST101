<?php

namespace Webkul\Fulfillment\Services\Application;

use Illuminate\Support\Facades\Cache;

class WebhookProcessingLockService
{
    /**
     * Acquire lock for processing webhook.
     */
    public function acquire(string $eventId): bool
    {
        return (bool) Cache::lock("webhook_lock:{$eventId}", 300)->get();
    }

    /**
     * Release lock.
     */
    public function release(string $eventId): void
    {
        Cache::lock("webhook_lock:{$eventId}")->release();
    }
}
