<?php

namespace Webkul\Fulfillment\Providers;

use Illuminate\Support\ServiceProvider;

class FulfillmentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->registerConfig();

        $this->app->singleton(
            \App\Services\AliExpress\AliExpressApiClient::class,
            \Webkul\Fulfillment\Providers\AliExpress\AliExpressHttpClient::class
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'fulfillment');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'fulfillment');

        $this->app->register(EventServiceProvider::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                \Webkul\Fulfillment\Console\Commands\BenchmarkSyncCommand::class,
                \Webkul\Fulfillment\Console\Commands\RecoverSyncRunsCommand::class,
                \Webkul\Fulfillment\Console\Commands\SoakTestSyncCommand::class,
                \Webkul\Fulfillment\Console\Commands\SmokeTestFulfillmentCommand::class,
                \Webkul\Fulfillment\Console\Commands\ProductionCheckFulfillmentCommand::class,
                \Webkul\Fulfillment\Console\Commands\ProductionAcceptanceFulfillmentCommand::class,
            ]);
        }

        if (config('fulfillment.admin_ui_enabled', true)) {
            $this->loadRoutesFrom(__DIR__.'/../Routes/admin-routes.php');
        } else {
            $this->app->booted(function () {
                $menu = config('menu.admin', []);
                $menu = array_filter($menu, function ($item) {
                    return $item['key'] !== 'dropshipping.fulfillment';
                });
                config(['menu.admin' => array_values($menu)]);
            });
        }

        $this->callAfterResolving(\Illuminate\Console\Scheduling\Schedule::class, function (\Illuminate\Console\Scheduling\Schedule $schedule) {
            if (config('fulfillment.poll.enabled', true)) {
                $schedule->job(new \Webkul\Fulfillment\Jobs\PollSupplierOrdersJob)->everyFifteenMinutes();
            }
            $schedule->call(function() {
                app(\Webkul\Fulfillment\Services\Application\ReconciliationEngine::class)->reconcile();
            })->daily();

            $schedule->call(function() {
                app(\Webkul\Fulfillment\Services\Application\OutboxEventProcessor::class)->processPending();
            })->name('process-outbox-events')->everyMinute()->withoutOverlapping()->onOneServer();

            $schedule->call(function() {
                app(\Webkul\Fulfillment\Services\Application\InboxEventProcessor::class)->processPending();
            })->name('process-inbox-events')->everyMinute()->withoutOverlapping()->onOneServer();
        });
    }

    /**
     * Register package config.
     */
    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/fulfillment.php',
            'fulfillment'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/acl.php',
            'acl'
        );
    }
}
