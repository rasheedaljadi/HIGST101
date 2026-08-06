<?php

namespace Webkul\Wallet\Payment;

use Illuminate\Support\Facades\Storage;
use Webkul\Payment\Payment\Payment;
use Webkul\Wallet\Repositories\WalletAccountRepository;

class WalletPayment extends Payment
{
    /**
     * Payment method code.
     *
     * @var string
     */
    protected $code = 'wallet';

    /**
     * Determine if the payment method is available at checkout.
     *
     * Availability rules (Sprint 0.5 — Option A: Regular Payment Method):
     *  1. Wallet feature is globally active
     *  2. Customer is authenticated
     *  3. Wallet account exists and is active
     *  4. Available balance >= order grand total (V1: no partial payment)
     *  5. Currency matches store base currency
     */
    /**
     * Determine if the payment method is available at checkout.
     */
    public function isAvailable(): bool
    {
        $isActive = $this->getConfigData('active');
        $isGlobalActive = core()->getConfigData('sales.wallet.active');

        if ($isActive === '0' || $isActive === 0 || $isActive === false) {
            return false;
        }

        if (($isActive === null || $isActive === '') && ($isGlobalActive === '0' || $isGlobalActive === 0 || $isGlobalActive === false)) {
            return false;
        }

        return true;
    }

    /**
     * Get the redirect URL after payment.
     * Wallet payment is instant — no redirect needed.
     */
    public function getRedirectUrl(): string
    {
        return '';
    }

    /**
     * Get custom title configured in admin.
     */
    public function getTitle(): string
    {
        return $this->getConfigData('title') ?: 'محفظة هايست الإلكترونية';
    }

    /**
     * Get balance description to show in checkout.
     */
    public function getDescription(): string
    {
        $customDesc = $this->getConfigData('description');
        if ($customDesc) {
            return $customDesc;
        }

        if (! auth()->guard('customer')->check()) {
            return 'الدفع المباشر والسريع من رصيد محفظتك المتاح لدى هايست';
        }

        $customerId = auth()->guard('customer')->id();

        $wallet = app(WalletAccountRepository::class)
            ->where('customer_id', $customerId)
            ->first();

        if (! $wallet) {
            return 'الدفع المباشر والسريع من رصيد محفظتك المتاح لدى هايست';
        }

        return trans('wallet::app.shop.checkout.balance', [
            'balance' => core()->formatBasePrice($wallet->available_balance),
        ]);
    }

    /**
     * Get payment method image.
     */
    public function getImage(): string
    {
        $url = $this->getConfigData('image');

        return $url ? Storage::url($url) : '';
    }
}
