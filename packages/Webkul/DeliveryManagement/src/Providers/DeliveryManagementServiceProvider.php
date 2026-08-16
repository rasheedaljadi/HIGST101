<?php

namespace Webkul\DeliveryManagement\Providers;

use Illuminate\Support\ServiceProvider;

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
    }
}
