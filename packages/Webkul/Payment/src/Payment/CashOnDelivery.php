<?php

namespace Webkul\Payment\Payment;

use Illuminate\Support\Facades\Storage;
use Webkul\DeliveryManagement\Services\PaymentEligibilityChecker;

class CashOnDelivery extends Payment
{
    /**
     * Payment method code.
     *
     * @var string
     */
    protected $code = 'cashondelivery';

    /**
     * Get redirect url.
     *
     * @return string
     */
    public function getRedirectUrl() {}

    /**
     * Is available.
     *
     * @return bool
     */
    public function isAvailable()
    {
        if (! $this->cart) {
            $this->setCart();
        }

        $active = $this->getConfigData('active') ?? config('payment_methods.'.$this->code.'.active', true);
        $stockable = ! $this->cart || $this->cart->hasOnlyStockableItems();

        if (! $active || ! $stockable) {
            return false;
        }

        if (class_exists(PaymentEligibilityChecker::class)) {
            return app(PaymentEligibilityChecker::class)
                ->isCartEligible($this->code, $this->cart);
        }

        return true;
    }

    /**
     * Get payment method image.
     *
     * @return array
     */
    public function getImage()
    {
        $url = $this->getConfigData('image');

        return $url ? Storage::url($url) : bagisto_asset('images/cash-on-delivery.png', 'shop');
    }
}
