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

        if ($this->app->runningInConsole()) {
            $this->commands([
                CheckStockThresholds::class,
            ]);
        }
    }
}
