<?php

namespace Webkul\Wallet\Listeners;

use Webkul\Sales\Contracts\Order;
use Webkul\Wallet\Exceptions\InsufficientWalletBalanceException;
use Webkul\Wallet\Models\WalletTransaction;
use Webkul\Wallet\Repositories\WalletAccountRepository;
use Webkul\Wallet\Services\WalletService;

class DebitWalletOnOrderCreated
{
    /**
     * Create a new listener instance.
     */
    public function __construct(
        protected WalletService $walletService,
        protected WalletAccountRepository $walletAccountRepository
    ) {}

    /**
     * Handle the event: checkout.order.save.after
     *
     * Sprint 0.5 — ملاحظة 5: This event fires INSIDE DB::beginTransaction() in
     * OrderRepository::createOrderIfNotThenRetry(). If we throw an exception here,
     * the entire order creation rolls back automatically.
     *
     * H-01: Throws localized Exception for user-friendly checkout error display.
     * H-02: Records 0.00 DEBIT_PAYMENT transaction for zero-amount orders to maintain 1:1 ledger mapping.
     *
     * @param  Order  $order
     */
    public function handle($order): void
    {
        if ($order->payment->method !== 'wallet') {
            return;
        }

        $amount = max(0.0, (float) $order->base_grand_total);

        $wallet = $this->walletAccountRepository
            ->where('customer_id', $order->customer_id)
            ->where('status', 'active')
            ->first();

        if (! $wallet) {
            throw new \RuntimeException(
                trans('wallet::app.shop.checkout.wallet-unavailable')
            );
        }

        try {
            // WalletService::debit() uses lockForUpdate() internally.
            // Any exception here (InsufficientBalance, Suspended) will propagate up
            // and trigger the OrderRepository catch → DB::rollBack() → order is cancelled.
            $this->walletService->debit(
                wallet: $wallet,
                amount: $amount,
                type: WalletTransaction::TYPE_DEBIT_PAYMENT,
                description: 'Payment for Order #'.$order->increment_id.($amount == 0 ? ' (Zero-Amount Order)' : ''),
                referenceType: get_class($order),
                referenceId: $order->id,
                createdByType: 'customer',
                createdById: $order->customer_id
            );
        } catch (InsufficientWalletBalanceException $e) {
            // H-01: Throw translatable exception for Bagisto checkout error display
            throw new \RuntimeException(
                trans('wallet::app.shop.checkout.insufficient-balance', [
                    'available' => core()->formatBasePrice($wallet->available_balance),
                    'required' => core()->formatBasePrice($amount),
                ])
            );
        }
    }
}
