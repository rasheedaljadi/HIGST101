<?php

namespace Webkul\MobileApi\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Webkul\MobileApi\Http\Middleware\ValidateStorefrontKey;

class MobileApiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/admin-menu.php', 'menu.admin'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/acl.php', 'acl'
        );
    }

    public function boot(Router $router): void
    {
        $router->aliasMiddleware('storefront.key', ValidateStorefrontKey::class);

        $this->loadRoutesFrom(__DIR__.'/../Routes/api-routes.php');
        $this->loadRoutesFrom(__DIR__.'/../Routes/admin-routes.php');

        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'mobile_api');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'mobile_api');
    }
}
