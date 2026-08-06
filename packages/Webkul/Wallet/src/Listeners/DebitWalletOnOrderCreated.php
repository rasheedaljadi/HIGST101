<?php

namespace Webkul\Wallet\Listeners;

use Illuminate\Support\Facades\Log;
use Webkul\Sales\Contracts\Order;
use Webkul\Sales\Repositories\InvoiceRepository;
use Webkul\Sales\Repositories\OrderTransactionRepository;
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
            ->first();

        if (! $wallet && $order->customer_id) {
            $wallet = $this->walletAccountRepository->create([
                'customer_id' => $order->customer_id,
                'total_balance' => 0.00,
                'available_balance' => 0.00,
                'held_balance' => 0.00,
                'currency_code' => core()->getBaseCurrencyCode(),
                'status' => 'active',
            ]);
        }

        if (! $wallet || $wallet->status !== 'active') {
            throw new \RuntimeException(
                trans('wallet::app.shop.checkout.wallet-unavailable') ?? 'حساب المحفظة غير متاح أو معلق حالياً.'
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

            // Auto-create paid invoice for HIGEST Wallet payments so payment status is automatically confirmed (Paid)
            if ($order->canInvoice()) {
                try {
                    $invoiceRepository = app(InvoiceRepository::class);
                    $invoiceData = ['order_id' => $order->id];
                    foreach ($order->items as $item) {
                        $invoiceData['invoice']['items'][$item->id] = $item->qty_to_invoice;
                    }
                    $invoice = $invoiceRepository->create($invoiceData, 'paid', 'processing');

                    // Create transaction record for Sales Transactions (/admin/sales/transactions)
                    $transactionRepository = app(OrderTransactionRepository::class);
                    $transactionRepository->create([
                        'transaction_id' => 'WLT-'.strtoupper(bin2hex(random_bytes(6))),
                        'status' => 'paid',
                        'type' => 'wallet',
                        'payment_method' => 'wallet',
                        'order_id' => $order->id,
                        'invoice_id' => $invoice->id,
                        'amount' => $invoice->grand_total,
                    ]);
                } catch (\Throwable $e) {
                    Log::error('Auto invoice creation failed for wallet order #'.$order->id.': '.$e->getMessage());
                }
            }
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
