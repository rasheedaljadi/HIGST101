<?php

namespace Webkul\OfflinePayments\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Webkul\OfflinePayments\Listeners\SavePaymentSnapshot;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Event::listen(
            'sales.order.place.after',
            [SavePaymentSnapshot::class, 'handle']
        );
    }
}
