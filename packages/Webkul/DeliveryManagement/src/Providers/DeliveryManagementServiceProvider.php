<?php

namespace Webkul\DeliveryManagement\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Webkul\DeliveryManagement\Listeners\OrderCreatedListener;

class DeliveryManagementServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'delivery');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'delivery');

        $this->loadRoutesFrom(__DIR__.'/../Routes/delivery-routes.php');

        Event::listen('checkout.order.save.after', [OrderCreatedListener::class, 'handle']);
        Event::listen('sales.order.save.after', [OrderCreatedListener::class, 'handle']);
    }

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/delivery.php',
            'delivery'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/carriers.php',
            'carriers'
        );
    }
}
