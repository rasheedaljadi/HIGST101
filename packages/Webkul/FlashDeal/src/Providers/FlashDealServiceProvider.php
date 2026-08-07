<?php

namespace Webkul\FlashDeal\Providers;

use Illuminate\Support\ServiceProvider;

class FlashDealServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);

        $this->mergeConfigFrom(__DIR__.'/../Config/menu.php', 'menu.admin');
        $this->mergeConfigFrom(__DIR__.'/../Config/admin-menu.php', 'menu.admin');
        $this->mergeConfigFrom(__DIR__.'/../Config/acl.php', 'acl');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__.'/../Routes/admin-routes.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'flash_deal');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'flashdeal');
    }
}
