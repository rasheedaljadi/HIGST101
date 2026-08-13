<?php

namespace Webkul\Wallet\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Webkul\Wallet\Console\Commands\ProcessPromotionOutboxCommand;
use Webkul\Wallet\Console\Commands\VerifyWalletLedgerCommand;
use Webkul\Wallet\Repositories\WalletAccountRepository;
use Webkul\Wallet\Repositories\WalletPromoDebtRepository;
use Webkul\Wallet\Repositories\WalletPromoDebtSettlementRepository;
use Webkul\Wallet\Repositories\WalletPromotionAuditRepository;
use Webkul\Wallet\Repositories\WalletPromotionGrantConsumptionRepository;
use Webkul\Wallet\Repositories\WalletPromotionGrantRepository;
use Webkul\Wallet\Repositories\WalletPromotionOrderItemAllocationRepository;
use Webkul\Wallet\Repositories\WalletPromotionOutboxRepository;
use Webkul\Wallet\Repositories\WalletPromotionRepository;
use Webkul\Wallet\Repositories\WalletPromotionUsageRepository;
use Webkul\Wallet\Repositories\WalletTopUpRepository;
use Webkul\Wallet\Repositories\WalletTransactionRepository;
use Webkul\Wallet\Repositories\WalletWithdrawalMethodRepository;
use Webkul\Wallet\Repositories\WalletWithdrawalRequestRepository;
use Webkul\Wallet\Services\PaymentVerificationService;
use Webkul\Wallet\Services\PromotionGrantService;
use Webkul\Wallet\Services\WalletDebtService;
use Webkul\Wallet\Services\WalletPromotionOrchestrator;
use Webkul\Wallet\Services\WalletPromotionOutboxWorker;
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

        $this->loadRoutes();

        $this->app->register(EventServiceProvider::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                VerifyWalletLedgerCommand::class,
                ProcessPromotionOutboxCommand::class,
            ]);
        }
    }

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/admin-menu.php', 'menu.admin');
        $this->mergeConfigFrom(__DIR__.'/../Config/shop-menu.php', 'menu.customer');
        $this->mergeConfigFrom(__DIR__.'/../Config/acl.php', 'acl');
        $this->mergeConfigFrom(__DIR__.'/../Config/payment-methods.php', 'payment_methods');
        $this->mergeConfigFrom(__DIR__.'/../Config/system.php', 'core');

        $this->app->singleton(WalletService::class);
        $this->app->singleton(PromotionGrantService::class);
        $this->app->singleton(WalletDebtService::class);
        $this->app->singleton(PaymentVerificationService::class);
        $this->app->singleton(WalletPromotionOrchestrator::class);
        $this->app->singleton(WalletPromotionOutboxWorker::class);

        $this->app->bind(WalletAccountRepository::class);
        $this->app->bind(WalletTransactionRepository::class);
        $this->app->bind(WalletTopUpRepository::class);
        $this->app->bind(WalletWithdrawalRequestRepository::class);
        $this->app->bind(WalletWithdrawalMethodRepository::class);
        $this->app->bind(WalletPromotionRepository::class);
        $this->app->bind(WalletPromotionUsageRepository::class);
        $this->app->bind(WalletPromotionGrantRepository::class);
        $this->app->bind(WalletPromotionGrantConsumptionRepository::class);
        $this->app->bind(WalletPromotionOrderItemAllocationRepository::class);
        $this->app->bind(WalletPromoDebtRepository::class);
        $this->app->bind(WalletPromoDebtSettlementRepository::class);
        $this->app->bind(WalletPromotionOutboxRepository::class);
        $this->app->bind(WalletPromotionAuditRepository::class);
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
