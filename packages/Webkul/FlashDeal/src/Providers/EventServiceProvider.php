<?php

namespace Webkul\FlashDeal\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Webkul\FlashDeal\Listeners\OrderPlacedListener;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'checkout.order.save.after' => [
            OrderPlacedListener::class,
        ],
    ];
}
