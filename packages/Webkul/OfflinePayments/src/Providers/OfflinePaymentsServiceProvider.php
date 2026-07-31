<?php

namespace Webkul\OfflinePayments\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Webkul\OfflinePayments\Services\OfflinePaymentAccountResolver;
use Webkul\OfflinePayments\ViewComposers\CheckoutPaymentComposer;

class OfflinePaymentsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(OfflinePaymentAccountResolver::class);

        $this->app->register(EventServiceProvider::class);

        $this->mergeConfigFrom(__DIR__.'/../Config/admin-menu.php', 'menu.admin');
        $this->mergeConfigFrom(__DIR__.'/../Config/acl.php', 'acl');
        $this->mergeConfigFrom(__DIR__.'/../Config/payment_methods.php', 'payment_methods');
        $this->mergeConfigFrom(__DIR__.'/../Config/system.php', 'core');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadRoutesFrom(__DIR__.'/../Routes/admin-routes.php');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'offline_payments');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'offline_payments');

        Event::listen('bagisto.admin.sales.order.payment-method.after', function ($viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('offline_payments::admin.sales.orders.payment-snapshot');
        });

        Event::listen('bagisto.shop.customers.account.orders.view.payment_method.after', function ($viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('offline_payments::shop.payment.details');
        });

        View::composer(
            'shop::checkout.onepage.payment',
            CheckoutPaymentComposer::class
        );
    }
}
