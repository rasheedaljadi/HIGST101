<?php

namespace Webkul\Wallet\Listeners;

use Illuminate\Support\Facades\Log;
use Webkul\Wallet\Models\WalletTransaction;
use Webkul\Wallet\Repositories\WalletAccountRepository;
use Webkul\Wallet\Services\WalletService;

class RefundEventListener
{
    /**
     * Create a new listener instance.
     */
    public function __construct(
        protected WalletService $walletService,
        protected WalletAccountRepository $walletAccountRepository
    ) {}

    /**
     * Handle the refund event (sales.refund.save.after).
     *
     * @param  mixed  $refund
     */
    public function handle($refund): void
    {
        $order = $refund->order ?? null;

        if (! $order || ! $order->customer_id) {
            Log::info('Wallet RefundEventListener: Skipping guest refund for Order #'.($order->id ?? 'unknown'));

            return;
        }

        $amount = (float) ($refund->base_grand_total ?? $refund->grand_total ?? 0);

        if ($amount <= 0) {
            return;
        }

        try {
            $wallet = $this->walletAccountRepository->firstOrCreate(
                ['customer_id' => $order->customer_id],
                [
                    'currency_code' => core()->getBaseCurrencyCode() ?? 'USD',
                    'total_balance' => 0,
                    'available_balance' => 0,
                    'held_balance' => 0,
                    'status' => 'active',
                ]
            );

            $this->walletService->credit(
                wallet: $wallet,
                amount: $amount,
                type: WalletTransaction::TYPE_CREDIT_REFUND,
                description: 'Refund for Order #'.$order->id,
                meta: ['refund_id' => $refund->id],
                referenceType: get_class($refund),
                referenceId: $refund->id
            );
        } catch (\Exception $e) {
            Log::error('Wallet RefundEventListener Error for Order #'.$order->id.': '.$e->getMessage());
        }
    }
}
