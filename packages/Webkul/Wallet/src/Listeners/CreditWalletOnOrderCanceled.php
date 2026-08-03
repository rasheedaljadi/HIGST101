<?php

namespace Webkul\Wallet\Listeners;

use Illuminate\Support\Facades\Log;
use Webkul\Sales\Contracts\Order;
use Webkul\Wallet\Models\WalletTransaction;
use Webkul\Wallet\Repositories\WalletAccountRepository;
use Webkul\Wallet\Repositories\WalletTransactionRepository;
use Webkul\Wallet\Services\WalletService;

class CreditWalletOnOrderCanceled
{
    /**
     * Create a new listener instance.
     */
    public function __construct(
        protected WalletService $walletService,
        protected WalletAccountRepository $walletAccountRepository,
        protected WalletTransactionRepository $walletTransactionRepository
    ) {}

    /**
     * Handle the event: sales.order.cancel.after
     *
     * D-002 Decision: Immediate credit on cancel (no Refund flow required).
     *
     * @param  Order  $order
     */
    public function handle($order): void
    {
        if ($order->payment->method !== 'wallet') {
            return;
        }

        // Find the original DEBIT_PAYMENT transaction for this order
        $debitTransaction = $this->walletTransactionRepository
            ->where('type', WalletTransaction::TYPE_DEBIT_PAYMENT)
            ->where('reference_type', get_class($order))
            ->where('reference_id', $order->id)
            ->first();

        if (! $debitTransaction) {
            Log::warning('Wallet: No DEBIT_PAYMENT found for canceled order #'.$order->id);

            return;
        }

        $wallet = $this->walletAccountRepository->find($debitTransaction->wallet_id);

        if (! $wallet) {
            Log::error('Wallet: Wallet not found for canceled order #'.$order->id);

            return;
        }

        try {
            $this->walletService->credit(
                wallet: $wallet,
                amount: $debitTransaction->amount,
                type: WalletTransaction::TYPE_CREDIT_CANCEL,
                description: 'Refund for canceled Order #'.$order->increment_id,
                referenceType: get_class($order),
                referenceId: $order->id,
                referenceTransactionId: $debitTransaction->id
            );
        } catch (\Exception $e) {
            Log::error('Wallet: Failed to credit wallet on order cancel #'.$order->id.': '.$e->getMessage());
        }
    }
}
