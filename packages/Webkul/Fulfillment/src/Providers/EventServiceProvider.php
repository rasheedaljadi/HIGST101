<?php

namespace Webkul\Fulfillment\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Webkul\Fulfillment\Events\OrderAccepted;
use Webkul\Fulfillment\Events\Procurement\ProcurementCompleted;
use Webkul\Fulfillment\Listeners\InitiateFulfillmentListener;
use Webkul\Fulfillment\Listeners\OrderLifecycleListener;
use Webkul\Fulfillment\Listeners\ProcurementCompletedListener;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Fulfillment Saga Trigger
        Event::listen(
            OrderAccepted::class,
            InitiateFulfillmentListener::class
        );

        // Procurement Inbound Receipt Trigger
        Event::listen(
            ProcurementCompleted::class,
            ProcurementCompletedListener::class
        );

        // Order Lifecycle & Bookkeeping Triggers
        Event::listen(
            'sales.order.place.after',
            [OrderLifecycleListener::class, 'handleOrderPlaced']
        );

        Event::listen(
            'sales.invoice.save.after',
            [OrderLifecycleListener::class, 'handleInvoiceSaved']
        );

        Event::listen(
            'sales.shipment.save.after',
            [OrderLifecycleListener::class, 'handleShipmentSaved']
        );

        Event::listen(
            'sales.refund.save.after',
            [OrderLifecycleListener::class, 'handleRefundSaved']
        );
    }
}
