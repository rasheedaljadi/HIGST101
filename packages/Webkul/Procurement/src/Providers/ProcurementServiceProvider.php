<?php

namespace Webkul\Procurement\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Webkul\Procurement\Console\Commands\BackfillDemandSkuIds;
use Webkul\Procurement\Console\Commands\PollAliExpressOrdersCommand;
use Webkul\Procurement\Console\Commands\ProcessProcurementAutoBatchCommand;
use Webkul\Procurement\Console\Commands\ProcurementRemediateFailedSubmissionCommand;
use Webkul\Procurement\Contracts\AliExpressAuthorizationContextResolver;
use Webkul\Procurement\Contracts\AliExpressOrderGateway;
use Webkul\Procurement\Gateways\AliExpressOrderSubmissionGateway;
use Webkul\Procurement\Services\AliExpressAuthorizationResolver;

class ProcurementServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->registerConfig();

        $this->app->singleton(
            AliExpressOrderGateway::class,
            AliExpressOrderSubmissionGateway::class
        );

        $this->app->singleton(
            AliExpressAuthorizationContextResolver::class,
            AliExpressAuthorizationResolver::class
        );

        $this->app->register(EventServiceProvider::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'procurement');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'procurement');

        $this->loadRoutesFrom(__DIR__.'/../Routes/admin-routes.php');

        if ($this->app->runningInConsole()) {
            $this->commands([
                BackfillDemandSkuIds::class,
                PollAliExpressOrdersCommand::class,
                ProcessProcurementAutoBatchCommand::class,
                ProcurementRemediateFailedSubmissionCommand::class,
            ]);
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            if (config('procurement.v2_enabled', true) && config('procurement.polling.enabled', true)) {
                $schedule->command('procurement:poll-aliexpress')->everyTwoMinutes()->withoutOverlapping();
            }
        });
    }

    /**
     * Register package config.
     */
    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/procurement.php',
            'procurement'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/acl.php',
            'acl'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/admin-menu.php',
            'menu.admin'
        );
    }
}
