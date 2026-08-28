<?php

namespace Webkul\Fulfillment\Providers;

use App\Services\AliExpress\AliExpressApiClient;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Webkul\Fulfillment\Console\Commands\BenchmarkSyncCommand;
use Webkul\Fulfillment\Console\Commands\ProductionAcceptanceFulfillmentCommand;
use Webkul\Fulfillment\Console\Commands\ProductionCheckFulfillmentCommand;
use Webkul\Fulfillment\Console\Commands\RecoverSyncRunsCommand;
use Webkul\Fulfillment\Console\Commands\SmokeTestFulfillmentCommand;
use Webkul\Fulfillment\Console\Commands\SoakTestSyncCommand;
use Webkul\Fulfillment\Jobs\PollSupplierOrdersJob;
use Webkul\Fulfillment\Jobs\SyncProductBatchJob;
use Webkul\Fulfillment\Providers\AliExpress\AliExpressHttpClient;
use Webkul\Fulfillment\Services\Application\InboxEventProcessor;
use Webkul\Fulfillment\Services\Application\OutboxEventProcessor;
use Webkul\Fulfillment\Services\Application\ReconciliationEngine;

class FulfillmentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->registerConfig();

        $this->app->singleton(
            AliExpressApiClient::class,
            AliExpressHttpClient::class
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
                BenchmarkSyncCommand::class,
                RecoverSyncRunsCommand::class,
                SoakTestSyncCommand::class,
                SmokeTestFulfillmentCommand::class,
                ProductionCheckFulfillmentCommand::class,
                ProductionAcceptanceFulfillmentCommand::class,
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

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('fulfillment:recover-sync-runs')->everyFifteenMinutes();

            if (config('fulfillment.poll.enabled', true)) {
                $schedule->job(new PollSupplierOrdersJob)->everyThirtyMinutes();
            }

            if (config('fulfillment.sync.enabled', true)) {
                $schedule->job(new SyncProductBatchJob('aliexpress'))->dailyAt('04:30')->withoutOverlapping()->onOneServer();
            }
            $schedule->call(function () {
                app(ReconciliationEngine::class)->reconcile();
            })->daily();

            $schedule->call(function () {
                app(OutboxEventProcessor::class)->processPending();
            })->name('process-outbox-events')->everyMinute()->withoutOverlapping()->onOneServer();

            $schedule->call(function () {
                app(InboxEventProcessor::class)->processPending();
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
