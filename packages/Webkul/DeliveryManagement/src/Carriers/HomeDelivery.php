<?php

namespace Webkul\DeliveryManagement\Carriers;

use Webkul\Checkout\Facades\Cart;
use Webkul\Checkout\Models\CartShippingRate;
use Webkul\DeliveryManagement\Services\GovernorateDeliveryValidator;
use Webkul\DeliveryManagement\Services\ShippingMethodAdapter;
use Webkul\Shipping\Carriers\AbstractShipping;

class HomeDelivery extends AbstractShipping
{
    /**
     * Shipping method carrier code.
     *
     * @var string
     */
    protected $code = 'homedelivery';

    /**
     * Shipping method code.
     *
     * @var string
     */
    protected $method = 'homedelivery_standard';

    /**
     * Is available.
     */
    public function isAvailable(): bool
    {
        $cart = Cart::getCart();

        if (! $cart || ! $cart->shipping_address) {
            return true;
        }

        $stateCode = (string) $cart->shipping_address->state;

        if (empty($stateCode)) {
            return true;
        }

        /** @var GovernorateDeliveryValidator $validator */
        $validator = app(GovernorateDeliveryValidator::class);

        return $validator->isDeliveryTypeEnabled($stateCode, ShippingMethodAdapter::CANONICAL_HOME_DELIVERY);
    }

    /**
     * Calculate rate for home delivery.
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
        $stateCode = (string) ($cart?->shipping_address?->state ?? '');

        /** @var GovernorateDeliveryValidator $validator */
        $validator = app(GovernorateDeliveryValidator::class);
        $rule = $validator->getActiveRule($stateCode, ShippingMethodAdapter::CANONICAL_HOME_DELIVERY);

        $fee = (float) ($rule?->delivery_fee ?? 0.0);
        $cartSubTotal = (float) ($cart?->base_sub_total ?? 0.0);
        $freeThreshold = $rule?->free_delivery_threshold !== null ? (float) $rule->free_delivery_threshold : null;

        $isFreeDelivery = false;
        if ($freeThreshold !== null && $freeThreshold > 0 && $cartSubTotal >= $freeThreshold) {
            $isFreeDelivery = true;
            $fee = 0.0;
        }

        $cartShippingRate = new CartShippingRate;

        $cartShippingRate->carrier = $this->getCode();
        $cartShippingRate->carrier_title = 'توصيل إلى المنزل (Home Delivery)';
        $cartShippingRate->method = $this->getMethod();

        if ($isFreeDelivery) {
            $cartShippingRate->method_title = 'توصيل منزلي مجاني';
            $cartShippingRate->method_description = '🎉 توصيل مجاني لتجاوز سلتك الحد الأدنى ('.core()->currency($freeThreshold).')';
        } else {
            $cartShippingRate->method_title = 'توصيل مباشر إلى العنوان';
            if ($freeThreshold !== null && $freeThreshold > 0) {
                $cartShippingRate->method_description = 'توصيل الطلب إلى عنوان العميل (توصيل مجاني للطلبات أكبر من '.core()->currency($freeThreshold).')';
            } else {
                $cartShippingRate->method_description = 'توصيل الطلب إلى العنوان المحدد من قبل العميل';
            }
        }

        $cartShippingRate->price = core()->convertPrice($fee);
        $cartShippingRate->base_price = $fee;

        return $cartShippingRate;
    }
}
