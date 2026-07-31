<?php

namespace Webkul\OfflinePayments\Payment;

use Illuminate\Support\Facades\Storage;
use Webkul\OfflinePayments\Services\OfflinePaymentAccountResolver;
use Webkul\Payment\Payment\Payment;

class OfflinePayments extends Payment
{
    /**
     * Payment method code.
     *
     * @var string
     */
    protected $code = 'offline_payments';

    /**
     * Create a new payment method instance.
     */
    public function __construct(
        protected OfflinePaymentAccountResolver $accountResolver
    ) {}

    /**
     * Checks if payment method is available.
     *
     * @return bool
     */
    public function isAvailable()
    {
        if (! parent::isAvailable()) {
            return false;
        }

        $this->setCart();

        if (! $this->cart) {
            return false;
        }

        $accounts = $this->accountResolver->getAccountsForCart($this->cart);

        return $accounts->isNotEmpty();
    }

    /**
     * Get redirect url.
     *
     * @return string
     */
    public function getRedirectUrl()
    {
        return '';
    }

    /**
     * Returns payment method image.
     *
     * @return string
     */
    public function getImage()
    {
        $url = $this->getConfigData('image');

        return $url ? Storage::url($url) : '';
    }
}
