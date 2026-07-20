<?php

namespace App\Services\AliExpress\Shipping;

use App\Models\AliExpressProductImport;
use App\Models\AliExpressSetting;
use Webkul\Checkout\Facades\Cart;
use Webkul\Checkout\Models\CartShippingRate;
use Webkul\Shipping\Carriers\AbstractShipping;

/**
 * Storefront shipping carrier for the AliExpress dropshipping store.
 *
 * Computes the cart's shipping rate ENTIRELY from data cached locally at import
 * time (aliexpress_product_imports.base_shipping_cost) — it never calls the
 * AliExpress API during browsing or checkout. The cached base cost (Cn -> SA)
 * is summed per cart item × quantity, then a flat, admin-managed margin is
 * added once per order to cover the internal SA -> Yemen -> customer leg and
 * the store's profit.
 *
 * Registered into config('carriers') from AppServiceProvider so it appears as a
 * checkout shipping option alongside Bagisto's built-in carriers.
 */
class AliExpressShipping extends AbstractShipping
{
    protected $code = 'aliexpress';

    protected $method = 'aliexpress_aliexpress';

    /**
     * Whether this carrier is available (admin toggle).
     */
    public function isAvailable()
    {
        return (bool) $this->settings()->shipping_enabled;
    }

    /**
     * Calculate the cart shipping rate from cached per-product shipping costs.
     *
     * @return CartShippingRate|false
     */
    public function calculate()
    {
        if (! $this->isAvailable()) {
            return false;
        }

        $cart = Cart::getCart();

        if (! $cart) {
            return false;
        }

        $settings = $this->settings();

        $baseCost = 0.0;
        $maxDays = 0;
        $minDays = 0;
        $resolvedAny = false;

        foreach ($cart->items as $item) {
            if (! $item->getTypeInstance()->isStockable()) {
                continue;
            }

            $shipping = $this->shippingForCartItem($item);

            if ($shipping === null) {
                continue;
            }

            $resolvedAny = true;

            $baseCost += (float) $shipping->base_shipping_cost * $item->quantity;

            // Delivery window = the slowest item in the cart.
            $minDays = max($minDays, (int) $shipping->shipping_min_days);
            $maxDays = max($maxDays, (int) $shipping->shipping_max_days);
        }

        // When no item had cached shipping (e.g. shipping not yet synced), do
        // not offer this carrier rather than quoting a misleading 0.
        if (! $resolvedAny) {
            return false;
        }

        // Flat margin (store currency) added once per order: covers the
        // internal SA -> Yemen leg + store margin.
        $margin = (float) $settings->shipping_margin;

        $basePrice = round($baseCost + $margin, 2);

        // Total delivery estimate = AliExpress window + extra internal days.
        $extraDays = (int) $settings->shipping_extra_days;
        $totalMin = $minDays + $extraDays;
        $totalMax = $maxDays + $extraDays;

        $rate = new CartShippingRate;
        $rate->carrier = $this->getCode();
        $rate->carrier_title = $this->getConfigData('title') ?: 'AliExpress Shipping';
        $rate->method = $this->getMethod();
        $rate->method_title = $this->getConfigData('title') ?: 'AliExpress Shipping';
        $rate->method_description = $this->deliveryDescription($totalMin, $totalMax);

        // base_price is in the store's base currency; price is the channel
        // currency conversion (mirrors Bagisto's FlatRate carrier).
        $rate->base_price = $basePrice;
        $rate->price = core()->convertPrice($basePrice);

        return $rate;
    }

    /**
     * Resolve the cached shipping row for a cart item, matching the item's
     * product or its configurable parent (shipping is cached on the parent).
     */
    protected function shippingForCartItem($item): ?AliExpressProductImport
    {
        $productIds = array_values(array_filter([
            $item->product_id,
            $item->product?->parent_id,
            $item->additional['product_id'] ?? null,
        ]));

        if ($productIds === []) {
            return null;
        }

        return AliExpressProductImport::query()
            ->whereIn('product_id', $productIds)
            ->whereNotNull('base_shipping_cost')
            ->orderByRaw('product_id = ? DESC', [$item->product_id])
            ->first();
    }

    /**
     * Build a human-readable delivery estimate for the rate description.
     */
    protected function deliveryDescription(int $min, int $max): string
    {
        if ($max <= 0 && $min <= 0) {
            return (string) ($this->getConfigData('description') ?: 'الشحن إلى عنوانك');
        }

        if ($min > 0 && $max > 0 && $min !== $max) {
            return "مدة التوصيل المتوقعة: {$min}–{$max} يوم";
        }

        $days = $max > 0 ? $max : $min;

        return "مدة التوصيل المتوقعة: {$days} يوم";
    }

    /**
     * The single AliExpress settings row.
     */
    protected function settings(): AliExpressSetting
    {
        return AliExpressSetting::current();
    }
}
