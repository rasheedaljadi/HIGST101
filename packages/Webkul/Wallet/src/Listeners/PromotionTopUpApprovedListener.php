<?php

namespace Webkul\Wallet\Listeners;

use Illuminate\Support\Facades\Log;
use Webkul\Wallet\Models\WalletPromotion;
use Webkul\Wallet\Models\WalletPromotionOutbox;
use Webkul\Wallet\Models\WalletTopUp;

class PromotionTopUpApprovedListener
{
    /**
     * Handle approved top-up event.
     */
    public function handle(WalletTopUp $topup): void
    {
        if (! $topup || ! isset($topup->id) || $topup->status !== 'approved') {
            return;
        }

        try {
            // Check feature flag - only proceed if promotions are active
            $mode = function_exists('core') ? (core()->getConfigData('sales.wallet_promotions.mode') ?? 'legacy_only') : 'legacy_only';
            if ($mode === 'legacy_only') {
                return;
            }

            // Find active top-up bonus promotions
            $promotions = WalletPromotion::where('type', WalletPromotion::TYPE_TOPUP_BONUS)
                ->where('status', WalletPromotion::STATUS_ACTIVE)
                ->get();

            foreach ($promotions as $promotion) {
                $eventKey = "topup_bonus:topup:{$topup->id}:promo:{$promotion->id}";

                WalletPromotionOutbox::firstOrCreate(
                    ['event_key' => $eventKey],
                    [
                        'event_type' => WalletPromotion::TYPE_TOPUP_BONUS,
                        'promotion_id' => $promotion->id,
                        'customer_id' => $topup->customer_id,
                        'reference_type' => 'wallet_topup',
                        'reference_id' => (string) $topup->id,
                        'event_payload' => [
                            'topup_id' => $topup->id,
                            'amount' => (string) $topup->amount,
                            'currency_code' => $topup->currency_code ?? 'USD',
                        ],
                        'status' => WalletPromotionOutbox::STATUS_PENDING,
                        'created_at' => now(),
                    ]
                );
            }
        } catch (\Exception $e) {
            Log::error("PromotionTopUpApprovedListener error for topup #{$topup->id}: ".$e->getMessage());
        }
    }
}
