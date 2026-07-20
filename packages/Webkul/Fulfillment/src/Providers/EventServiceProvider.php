<?php

namespace Webkul\Fulfillment\Providers;

use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Fulfillment Saga Trigger
        \Illuminate\Support\Facades\Event::listen(
            \Webkul\Fulfillment\Events\OrderAccepted::class,
            \Webkul\Fulfillment\Listeners\InitiateFulfillmentListener::class
        );

        // Order Lifecycle & Bookkeeping Triggers
        \Illuminate\Support\Facades\Event::listen(
            'sales.order.place.after',
            [\Webkul\Fulfillment\Listeners\OrderLifecycleListener::class, 'handleOrderPlaced']
        );

        \Illuminate\Support\Facades\Event::listen(
            'sales.invoice.save.after',
            [\Webkul\Fulfillment\Listeners\OrderLifecycleListener::class, 'handleInvoiceSaved']
        );

        \Illuminate\Support\Facades\Event::listen(
            'sales.shipment.save.after',
            [\Webkul\Fulfillment\Listeners\OrderLifecycleListener::class, 'handleShipmentSaved']
        );

        \Illuminate\Support\Facades\Event::listen(
            'sales.refund.save.after',
            [\Webkul\Fulfillment\Listeners\OrderLifecycleListener::class, 'handleRefundSaved']
        );
    }
}
