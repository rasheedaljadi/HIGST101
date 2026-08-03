<?php

namespace Webkul\Wallet\Payment;

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
    public function isAvailable(): bool
    {
        if (! core()->getConfigData('sales.wallet.active')) {
            return false;
        }

        if (! auth()->guard('customer')->check()) {
            return false;
        }

        $customerId = auth()->guard('customer')->id();

        $wallet = app(WalletAccountRepository::class)
            ->where('customer_id', $customerId)
            ->where('status', 'active')
            ->first();

        if (! $wallet) {
            return false;
        }

        // Sprint 0.5 — Currency check (ملاحظة 7)
        if ($wallet->currency_code !== core()->getBaseCurrencyCode()) {
            return false;
        }

        $cartTotal = (float) cart()->getCart()?->base_grand_total ?? 0.0;

        if ($cartTotal <= 0) {
            return true; // Zero-amount orders are always allowed
        }

        // Phase 5 — Partial Payment Architecture: Allow payment if balance >= cartTotal or if partial payment is enabled with available_balance > 0
        $allowPartial = (bool) core()->getConfigData('sales.payment_methods.wallet.allow_partial') ?? true;

        if ($allowPartial && $wallet->available_balance > 0) {
            return true;
        }

        return $wallet->available_balance >= $cartTotal;
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
     * Get balance description to show in checkout.
     */
    public function getDescription(): string
    {
        if (! auth()->guard('customer')->check()) {
            return '';
        }

        $customerId = auth()->guard('customer')->id();

        $wallet = app(WalletAccountRepository::class)
            ->where('customer_id', $customerId)
            ->first();

        if (! $wallet) {
            return '';
        }

        return trans('wallet::app.shop.checkout.balance', [
            'balance' => core()->formatBasePrice($wallet->available_balance),
        ]);
    }
}
