<?php

namespace Webkul\Sales\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Webkul\Sales\Console\Commands\CheckUnclosedOrdersReminderCommand;
use Webkul\Sales\Listeners\OrderLifecycleEventSubscriber;

class SalesServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        Event::subscribe(OrderLifecycleEventSubscriber::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                CheckUnclosedOrdersReminderCommand::class,
            ]);
        }
    }
}
