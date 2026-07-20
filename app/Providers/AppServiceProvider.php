<?php

namespace App\Providers;

use App\Models\AliExpressSetting;
use App\Services\AliExpress\Shipping\AliExpressShipping;
use Barryvdh\Debugbar\Facades\Debugbar;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\ParallelTesting;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $allowedIPs = array_map('trim', explode(',', config('app.debug_allowed_ips', '')));

        $allowedIPs = array_filter($allowedIPs);

        if (empty($allowedIPs)) {
            return;
        }

        if (in_array(Request::ip(), $allowedIPs)) {
            Debugbar::enable();
        } else {
            Debugbar::disable();
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /**
         * Force HTTPS URL generation when APP_URL uses https or when explicitly
         * requested via FORCE_HTTPS. AliExpress only accepts HTTPS callback
         * URLs, so any generated callback must use the https scheme.
         */
        if (config('app.force_https') || str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        ParallelTesting::setUpTestDatabase(function (string $database, int $token) {
            Artisan::call('db:seed');
        });

        $this->registerDropShippingAdminMenu();

        $this->registerAliExpressCarrier();

        $this->applyAliExpressSettings();

        $this->restrictProductTypes();
    }

    /**
     * Register the AliExpress storefront shipping carrier into config('carriers')
     * so it is offered at checkout alongside Bagisto's built-in carriers.
     *
     * Additive (read current array, add our entry, re-set) so no packages/Webkul
     * file is touched. The carrier computes rates from locally-cached shipping
     * data — it never calls the AliExpress API during checkout.
     */
    protected function registerAliExpressCarrier(): void
    {
        $carriers = config('carriers', []);

        $carriers['aliexpress'] = [
            'code' => 'aliexpress',
            'title' => 'الشحن',
            'description' => 'الشحن إلى عنوانك',
            'active' => true,
            'class' => AliExpressShipping::class,
        ];

        config(['carriers' => $carriers]);
    }

    /**
     * Override the AliExpress config with values stored in the database (the
     * admin "Key Management" page), falling back to the .env / config defaults
     * for any value not set in the DB.
     *
     * This makes the DB the primary source of credentials while keeping the
     * app bootable before the table exists (fresh install / pre-migration) and
     * when no row has been saved yet. Runs in boot() so every request — and the
     * AliExpress OAuth/API services that read config('aliexpress.*') — sees the
     * stored credentials without touching .env.
     */
    protected function applyAliExpressSettings(): void
    {
        try {
            if (! Schema::hasTable('aliexpress_settings')) {
                return;
            }

            $settings = AliExpressSetting::current();
        } catch (\Throwable $e) {
            // DB unavailable (e.g. during install) — keep the env/config defaults.
            return;
        }

        $overrides = [
            'aliexpress.app_key' => $settings->app_key,
            'aliexpress.app_secret' => $settings->app_secret,
            'aliexpress.redirect_uri' => $settings->redirect_uri,
            'aliexpress.authorize_url' => $settings->authorize_url,
            'aliexpress.token_url' => $settings->token_url,
            'aliexpress.business_url' => $settings->business_url,
            'aliexpress.sign_method' => $settings->sign_method,
        ];

        foreach ($overrides as $key => $value) {
            // Only override when the DB actually has a non-empty value, so an
            // unset field transparently falls back to the existing config/env.
            if ($value !== null && $value !== '') {
                config([$key => $value]);
            }
        }
    }

    /**
     * Restrict the catalog to two product types only: "simple" (a product
     * without variants) and "configurable" (a product with variants).
     *
     * Bagisto registers all its built-in types (booking, virtual, grouped,
     * downloadable, bundle, ...) via mergeConfigFrom('product_types'). App
     * providers boot after package providers, so re-setting the config here
     * keeps only the two types we want — without editing any Webkul package
     * file. Reversible: remove this method to restore all types.
     */
    protected function restrictProductTypes(): void
    {
        $types = config('product_types', []);

        $allowed = array_intersect_key($types, array_flip(['simple', 'configurable']));

        config(['product_types' => $allowed]);
    }

    /**
     * Additively merge the "Drop Shipping" parent and its "Import Products"
     * child into the existing admin sidebar menu.
     *
     * Bagisto's AdminServiceProvider loads the menu via
     * mergeConfigFrom(.../Config/menu.php, 'menu.admin'); because
     * mergeConfigFrom only fills keys absent from the already-bound config and
     * app providers boot after package providers, appending here (read current
     * array, push entries, re-set the config) is the correct, non-breaking way
     * to add items without editing any packages/Webkul file. Bagisto builds the
     * menu tree from the dot-nesting of the "key" field, so 'dropshipping' is
     * the parent and 'dropshipping.import' is its child.
     *
     * The "name" field is normally a translation key; plain display strings are
     * used here intentionally for this PoC. Laravel's translator returns the
     * given string unchanged when no matching key exists, so the labels render
     * as "Drop Shipping" and "Import Products" without touching package lang
     * files.
     */
    protected function registerDropShippingAdminMenu(): void
    {
        if (! config('dropshipping.admin_v2', true)) {
            return;
        }

        $menu = config('menu.admin', []);

        // Remove any pre-existing Drop Shipping menu entries (e.g. the package's
        // "coming soon" imports/fulfillment/api-keys placeholders) so only this
        // app's working pages remain — avoiding duplicate sidebar items without
        // editing any packages/Webkul file. Reversible: delete this filter.
        $menu = array_values(array_filter($menu, function ($item) {
            $key = $item['key'] ?? '';

            return $key !== 'dropshipping' && ! str_starts_with($key, 'dropshipping.');
        }));

        $menu[] = [
            'key' => 'dropshipping',
            'name' => 'admin::app.components.layouts.sidebar.dropshipping',
            'route' => 'admin.dropshipping.import.index',
            'sort' => 6,
            'icon' => 'icon-cart',
        ];

        $menu[] = [
            'key' => 'dropshipping.import',
            'name' => 'admin::app.components.layouts.sidebar.dropshipping-imports',
            'route' => 'admin.dropshipping.import.index',
            'sort' => 1,
            'icon' => '',
        ];

        $menu[] = [
            'key' => 'dropshipping.fulfillment',
            'name' => 'admin::app.components.layouts.sidebar.dropshipping-fulfillment',
            'route' => 'admin.dropshipping.fulfillment.index',
            'sort' => 2,
            'icon' => '',
        ];

        $menu[] = [
            'key' => 'dropshipping.keys',
            'name' => 'إدارة المفاتيح',
            'route' => 'admin.dropshipping.keys.index',
            'sort' => 3,
            'icon' => '',
        ];

        $menu[] = [
            'key' => 'dropshipping.sync',
            'name' => 'إدارة المزامنة',
            'route' => 'admin.dropshipping.sync.index',
            'sort' => 4,
            'icon' => '',
        ];

        config(['menu.admin' => $menu]);
    }
}
