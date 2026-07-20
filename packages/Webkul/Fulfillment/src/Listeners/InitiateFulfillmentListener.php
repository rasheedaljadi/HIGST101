<?php

namespace Webkul\Fulfillment\Listeners;

use Webkul\Fulfillment\Events\OrderAccepted;
use Webkul\Fulfillment\Services\Application\OrderLifecycleCoordinator;

class InitiateFulfillmentListener
{
    /**
     * Handle the event.
     */
    public function handle(OrderAccepted $event): void
    {
        app(OrderLifecycleCoordinator::class)->triggerFulfillmentSaga($event->orderId, $event->correlationId);
    }
}
