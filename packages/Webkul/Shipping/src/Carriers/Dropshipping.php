<?php

namespace Webkul\Shipping\Carriers;

use App\Models\AliExpressSetting;
use Webkul\Checkout\Facades\Cart;
use Webkul\Checkout\Models\CartShippingRate;

class Dropshipping extends AbstractShipping
{
    /**
     * Shipping method carrier code.
     *
     * @var string
     */
    protected $code = 'dropshipping';

    /**
     * Shipping method code.
     *
     * @var string
     */
    protected $method = 'dropshipping_dropshipping';

    /**
     * Calculate rate for dropshipping.
     *
     * @return CartShippingRate|false
     */
    public function calculate()
    {
        if (! $this->isAvailable()) {
            return false;
        }

        return $this->getRate();
    }

    /**
     * Get rate.
     */
    public function getRate(): CartShippingRate
    {
        $cart = Cart::getCart();

        $cartShippingRate = new CartShippingRate;

        $cartShippingRate->carrier = $this->getCode();
        $cartShippingRate->carrier_title = $this->getConfigData('title') ?: 'شحن الدروب شيبينج';
        $cartShippingRate->method = $this->getMethod();
        $cartShippingRate->method_title = $this->getConfigData('title') ?: 'شحن الدروب شيبينج';
        $cartShippingRate->method_description = $this->getConfigData('description') ?: 'شحن مباشر من المورد للعميل';
        $cartShippingRate->price = 0;
        $cartShippingRate->base_price = 0;

        $baseRate = (float) $this->getConfigData('default_rate');

        $shippingMargin = 0.0;
        if (class_exists(AliExpressSetting::class)) {
            $setting = AliExpressSetting::current();
            if ($setting && ! empty($setting->shipping_margin)) {
                $shippingMargin = (float) $setting->shipping_margin;
            }
        }

        $effectiveRate = $baseRate + $shippingMargin;

        if ($this->getConfigData('type') == 'per_unit') {
            if ($cart && $cart->items) {
                foreach ($cart->items as $item) {
                    if ($item->getTypeInstance()->isStockable()) {
                        $cartShippingRate->price += core()->convertPrice($effectiveRate) * $item->quantity;
                        $cartShippingRate->base_price += $effectiveRate * $item->quantity;
                    }
                }
            }
        } else {
            $cartShippingRate->price = core()->convertPrice($effectiveRate);
            $cartShippingRate->base_price = $effectiveRate;
        }

        return $cartShippingRate;
    }
}
