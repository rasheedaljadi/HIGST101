<?php

namespace Webkul\Wallet\Listeners;

use Illuminate\Support\Facades\Log;
use Webkul\Sales\Models\Refund;
use Webkul\Wallet\Services\PromotionGrantService;

class PromotionRefundListener
{
    public function __construct(
        protected PromotionGrantService $promotionGrantService
    ) {}

    /**
     * Handle sales refund event.
     */
    public function handle(Refund $refund): void
    {
        if (! $refund || ! isset($refund->id)) {
            return;
        }

        try {
            // Check feature flag - only proceed if promotions are active
            $mode = function_exists('core') ? (core()->getConfigData('sales.wallet_promotions.mode') ?? 'legacy_only') : 'legacy_only';
            if ($mode === 'legacy_only') {
                return;
            }

            $order = $refund->order;
            if (! $order || ! $order->customer_id) {
                return;
            }

            foreach ($refund->items as $item) {
                if (! $item->order_item_id) {
                    continue;
                }

                $this->promotionGrantService->handleItemRefund(
                    refundId: $refund->id,
                    orderItemId: $item->order_item_id,
                    refundedQty: (int) $item->qty,
                    refundedItemAmount: (string) $item->total
                );
            }
        } catch (\Exception $e) {
            Log::error("PromotionRefundListener error for refund #{$refund->id}: ".$e->getMessage());
        }
    }
}
