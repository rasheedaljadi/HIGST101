<?php

namespace Webkul\Sales\Providers;

use Illuminate\Support\ServiceProvider;
use Webkul\Sales\Console\Commands\CheckUnclosedOrdersReminderCommand;

class SalesServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                CheckUnclosedOrdersReminderCommand::class,
            ]);
        }
    }
}
