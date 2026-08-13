<?php

namespace Webkul\Wallet\Listeners;

use Illuminate\Support\Facades\Log;
use Webkul\Sales\Models\Invoice;
use Webkul\Wallet\Models\WalletPromotion;
use Webkul\Wallet\Models\WalletPromotionOutbox;
use Webkul\Wallet\Services\PaymentVerificationService;

class PromotionInvoicePaidListener
{
    public function __construct(
        protected PaymentVerificationService $paymentVerificationService
    ) {}

    /**
     * Handle invoice paid event.
     */
    public function handle(Invoice $invoice): void
    {
        if (! $invoice || ! isset($invoice->id)) {
            return;
        }

        try {
            // Check feature flag - only proceed if promotions are active
            $mode = function_exists('core') ? (core()->getConfigData('sales.wallet_promotions.mode') ?? 'legacy_only') : 'legacy_only';
            if ($mode === 'legacy_only') {
                return;
            }

            // Verify invoice payment status using authoritative invoices.state
            if (! $this->paymentVerificationService->verifyInvoicePayment($invoice)) {
                return;
            }

            $order = $invoice->order;
            if (! $order || ! $order->customer_id) {
                return;
            }

            // Find active order cashback promotions
            $promotions = WalletPromotion::whereIn('type', [
                WalletPromotion::TYPE_ORDER_SUBTOTAL_CASHBACK,
                WalletPromotion::TYPE_ORDER_CONDITIONAL_CASHBACK,
            ])
                ->where('status', WalletPromotion::STATUS_ACTIVE)
                ->get();

            foreach ($promotions as $promotion) {
                $eventKey = "order_cashback:invoice:{$invoice->id}:promo:{$promotion->id}";

                WalletPromotionOutbox::firstOrCreate(
                    ['event_key' => $eventKey],
                    [
                        'event_type' => $promotion->type,
                        'promotion_id' => $promotion->id,
                        'customer_id' => $order->customer_id,
                        'reference_type' => 'invoice',
                        'reference_id' => (string) $invoice->id,
                        'event_payload' => [
                            'invoice_id' => $invoice->id,
                            'order_id' => $order->id,
                            'subtotal' => (string) $invoice->sub_total,
                            'currency_code' => $invoice->order_currency_code ?? ($order->order_currency_code ?? 'USD'),
                            'invoiced_items' => $invoice->items->map(fn ($item) => [
                                'order_item_id' => $item->order_item_id,
                                'product_id' => $item->product_id,
                                'qty' => $item->qty,
                                'total' => (string) $item->total,
                            ])->toArray(),
                        ],
                        'status' => WalletPromotionOutbox::STATUS_PENDING,
                        'created_at' => now(),
                    ]
                );
            }
        } catch (\Exception $e) {
            Log::error("PromotionInvoicePaidListener error for invoice #{$invoice->id}: ".$e->getMessage());
        }
    }
}
