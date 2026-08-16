<?php

namespace Webkul\Notification\Listeners;

use Webkul\Fulfillment\Events\SyncCompleted;
use Webkul\Fulfillment\Events\SyncFailed;
use Webkul\Notification\Services\ScheduledSyncNotificationService;

class ScheduledSyncNotificationListener
{
    public function __construct(
        protected ScheduledSyncNotificationService $notificationService
    ) {}

    /**
     * Handle completed sync event.
     */
    public function handleSyncCompleted(SyncCompleted $event): void
    {
        $this->notificationService->notifySyncCompleted(
            $event->runId,
            $event->statistics ?? [],
            $event->healthSnapshot ?? []
        );
    }

    /**
     * Handle failed sync event.
     */
    public function handleSyncFailed(SyncFailed $event): void
    {
        $this->notificationService->notifySyncFailed(
            $event->runId,
            $event->errorMessage ?? 'Unknown error'
        );
    }
}
