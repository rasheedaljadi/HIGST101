<?php

namespace Webkul\Wallet\Listeners;

use Illuminate\Support\Facades\Log;
use Webkul\Sales\Contracts\Refund;
use Webkul\Wallet\Jobs\ProcessWalletCreditJob;
use Webkul\Wallet\Models\WalletPendingCredit;
use Webkul\Wallet\Models\WalletTransaction;
use Webkul\Wallet\Repositories\WalletAccountRepository;
use Webkul\Wallet\Services\WalletService;

class CreditWalletOnRefundCreated
{
    /**
     * Create a new listener instance.
     */
    public function __construct(
        protected WalletService $walletService,
        protected WalletAccountRepository $walletAccountRepository
    ) {}

    /**
     * Handle the event: sales.refund.save.after
     *
     * D-003 Decision: ALL refunds go to wallet regardless of original payment method.
     * PayPal gateway refund has been disabled in Admin\Listeners\Refund::refundOrder().
     *
     * This listener fires INSIDE DB::beginTransaction() in RefundRepository::create().
     * The wallet credit runs in its own nested transaction (lockForUpdate).
     * If it fails, the error is logged but the Refund itself is NOT rolled back
     * (the Refund domain logic already committed by this point at line 164).
     *
     * @param  Refund  $refund
     */
    public function handle($refund): void
    {
        $amount = (float) $refund->base_grand_total;

        if ($amount <= 0) {
            return;
        }

        $order = $refund->order;

        if (! $order?->customer_id) {
            Log::warning('Wallet: Refund #'.$refund->id.' has no customer. Skipping wallet credit.');

            return;
        }

        $wallet = $this->walletAccountRepository->getOrCreateForCustomer(
            customerId: $order->customer_id,
            currencyCode: core()->getBaseCurrencyCode()
        );

        try {
            $this->walletService->credit(
                wallet: $wallet,
                amount: $amount,
                type: WalletTransaction::TYPE_CREDIT_REFUND,
                description: 'Refund for Order #'.$order->increment_id.' (Refund #'.$refund->id.')',
                referenceType: get_class($refund),
                referenceId: $refund->id,
                createdByType: 'system',
                createdById: null
            );
        } catch (\Throwable $e) {
            // H-03 Fix: Log error, save to wallet_pending_credits, and dispatch retry Job
            Log::error('Wallet: Direct credit failed for Refund #'.$refund->id.' ('.$e->getMessage().'). Dispatching retry Job.');

            try {
                $pendingCredit = WalletPendingCredit::create([
                    'wallet_id' => $wallet->id,
                    'refund_id' => $refund->id,
                    'amount' => $amount,
                    'status' => WalletPendingCredit::STATUS_PENDING,
                    'attempts' => 0,
                    'error_message' => $e->getMessage(),
                ]);

                ProcessWalletCreditJob::dispatch($pendingCredit->id);
            } catch (\Throwable $jobError) {
                Log::critical('Wallet: Failed to dispatch ProcessWalletCreditJob for Refund #'.$refund->id.': '.$jobError->getMessage());
            }
        }
    }
}
