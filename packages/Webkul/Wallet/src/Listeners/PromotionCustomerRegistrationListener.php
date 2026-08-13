<?php

namespace Webkul\Wallet\Listeners;

use Illuminate\Support\Facades\Log;
use Webkul\Wallet\Models\WalletPromotion;
use Webkul\Wallet\Models\WalletPromotionOutbox;

class PromotionCustomerRegistrationListener
{
    /**
     * Handle the event when a customer registers.
     *
     * @param  mixed  $customer
     */
    public function handle($customer): void
    {
        if (! $customer || ! isset($customer->id)) {
            return;
        }

        try {
            // Check feature flag - only proceed if promotions are active
            $mode = function_exists('core') ? (core()->getConfigData('sales.wallet_promotions.mode') ?? 'legacy_only') : 'legacy_only';
            if ($mode === 'legacy_only') {
                return;
            }

            // Find active welcome bonus promotions
            $promotions = WalletPromotion::where('type', WalletPromotion::TYPE_WELCOME_BONUS)
                ->where('status', WalletPromotion::STATUS_ACTIVE)
                ->get();

            foreach ($promotions as $promotion) {
                $eventKey = "welcome_bonus:customer:{$customer->id}:promo:{$promotion->id}";

                WalletPromotionOutbox::firstOrCreate(
                    ['event_key' => $eventKey],
                    [
                        'event_type' => WalletPromotion::TYPE_WELCOME_BONUS,
                        'promotion_id' => $promotion->id,
                        'customer_id' => $customer->id,
                        'reference_type' => 'customer',
                        'reference_id' => (string) $customer->id,
                        'event_payload' => [
                            'customer_id' => $customer->id,
                            'currency' => function_exists('core') ? (core()->getBaseCurrencyCode() ?? 'USD') : 'USD',
                        ],
                        'status' => WalletPromotionOutbox::STATUS_PENDING,
                        'created_at' => now(),
                    ]
                );
            }
        } catch (\Exception $e) {
            Log::error("PromotionCustomerRegistrationListener error for customer #{$customer->id}: ".$e->getMessage());
        }
    }
}
