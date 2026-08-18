<?php

namespace Webkul\Inventory\Providers;

use Illuminate\Support\ServiceProvider;
use Webkul\Inventory\Console\Commands\CheckStockThresholds;

class InventoryServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'inventory');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'inventory');

        $this->loadRoutesFrom(__DIR__.'/../Routes/admin-routes.php');

        if ($this->app->runningInConsole()) {
            $this->commands([
                CheckStockThresholds::class,
            ]);
        }
    }

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/admin-menu.php',
            'menu.admin'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/acl.php',
            'acl'
        );
    }
}
