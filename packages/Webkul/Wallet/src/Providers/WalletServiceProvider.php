<?php

namespace Webkul\Wallet\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Webkul\Wallet\Console\Commands\VerifyWalletLedgerCommand;
use Webkul\Wallet\Repositories\WalletAccountRepository;
use Webkul\Wallet\Repositories\WalletTopUpRepository;
use Webkul\Wallet\Repositories\WalletTransactionRepository;
use Webkul\Wallet\Repositories\WalletWithdrawalMethodRepository;
use Webkul\Wallet\Repositories\WalletWithdrawalRequestRepository;
use Webkul\Wallet\Services\WalletService;

class WalletServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'wallet');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'wallet');

        $this->mergeConfigFrom(__DIR__.'/../Config/menu.php', 'menu.admin');

        $this->mergeConfigFrom(__DIR__.'/../Config/shop-menu.php', 'menu.customer');

        $this->mergeConfigFrom(__DIR__.'/../Config/acl.php', 'acl');

        $this->mergeConfigFrom(__DIR__.'/../Config/payment-methods.php', 'payment_methods');

        $this->mergeConfigFrom(__DIR__.'/../Config/system.php', 'core');

        $this->loadRoutes();

        $this->app->register(EventServiceProvider::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                VerifyWalletLedgerCommand::class,
            ]);
        }
    }

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(WalletService::class);

        $this->app->bind(WalletAccountRepository::class);
        $this->app->bind(WalletTransactionRepository::class);
        $this->app->bind(WalletTopUpRepository::class);
        $this->app->bind(WalletWithdrawalRequestRepository::class);
        $this->app->bind(WalletWithdrawalMethodRepository::class);
    }

    /**
     * Load admin and shop routes.
     */
    protected function loadRoutes(): void
    {
        /** @var Router $router */
        $router = $this->app['router'];

        // Admin routes
        $router->group([
            'prefix' => config('app.admin_url'),
            'middleware' => ['web', 'admin'],
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/../Routes/admin-wallet-routes.php');
        });

        // Shop routes
        $router->group([
            'middleware' => ['web', 'locale', 'theme', 'currency'],
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/../Routes/shop-wallet-routes.php');
        });
    }
}
