<?php

namespace Webkul\Wallet\Listeners;

use Illuminate\Support\Facades\Log;
use Webkul\Sales\Contracts\Order;
use Webkul\Wallet\Models\WalletTransaction;
use Webkul\Wallet\Repositories\WalletAccountRepository;
use Webkul\Wallet\Services\WalletService;

class ApplyWalletCashbackListener
{
    public function __construct(
        protected WalletService $walletService,
        protected WalletAccountRepository $walletAccountRepository
    ) {}

    /**
     * Handle the event: sales.invoice.save.after
     *
     * Automatically applies cashback promotion (e.g. 5%) when an order is paid via wallet.
     *
     * @param  mixed  $invoice
     */
    public function handle($invoice): void
    {
        $order = $invoice->order;

        if (! $order || ! $order->customer_id) {
            return;
        }

        // Only apply cashback if payment method was wallet
        if ($order->payment?->method !== 'wallet') {
            return;
        }

        $cashbackPercentage = (float) core()->getConfigData('sales.payment_methods.wallet.cashback_percentage') ?: 5.0;

        if ($cashbackPercentage <= 0) {
            return;
        }

        $cashbackAmount = round(($order->base_grand_total * $cashbackPercentage) / 100, 2);

        if ($cashbackAmount <= 0) {
            return;
        }

        $wallet = $this->walletAccountRepository->getOrCreateForCustomer(
            customerId: $order->customer_id,
            currencyCode: core()->getBaseCurrencyCode()
        );

        try {
            $this->walletService->credit(
                wallet: $wallet,
                amount: $cashbackAmount,
                type: WalletTransaction::TYPE_CREDIT_PROMOTION,
                description: "Cashback Reward ({$cashbackPercentage}%) for Order #{$order->increment_id}",
                referenceType: get_class($order),
                referenceId: $order->id,
                createdByType: 'system',
                createdById: null
            );

            Log::info("HIGEST Wallet: Applied {$cashbackPercentage}% cashback ({$cashbackAmount}) to Customer #{$order->customer_id} for Order #{$order->increment_id}");
        } catch (\Throwable $e) {
            Log::error("HIGEST Wallet: Failed to apply cashback for Order #{$order->increment_id}: ".$e->getMessage());
        }
    }
}
