<?php

namespace Webkul\Procurement\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Webkul\Fulfillment\Events\OrderAccepted;
use Webkul\Procurement\Listeners\AliExpressLiveStockListener;
use Webkul\Procurement\Listeners\OrderAcceptedListener;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        OrderAccepted::class => [
            OrderAcceptedListener::class,
        ],
        'checkout.cart.add.before' => [
            [AliExpressLiveStockListener::class, 'handleCartAddBefore'],
        ],
        'checkout.order.save.before' => [
            [AliExpressLiveStockListener::class, 'handleOrderSaveBefore'],
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        parent::boot();
    }
}
