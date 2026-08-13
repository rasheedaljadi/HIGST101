<?php

namespace Webkul\Wallet\Services;

use InvalidArgumentException;
use Webkul\Wallet\Contracts\WalletPromotion as WalletPromotionContract;
use Webkul\Wallet\Contracts\WalletPromotionGrant as WalletPromotionGrantContract;
use Webkul\Wallet\Models\WalletAccount;
use Webkul\Wallet\Models\WalletPromotion;
use Webkul\Wallet\Models\WalletPromotionGrant;
use Webkul\Wallet\Models\WalletPromotionUsage;

class PromotionGrantService
{
    /**
     * Calculate reward amount using pure BCMath.
     */
    public function calculateReward(WalletPromotionContract $promotion, string $eligibleAmountStr): string
    {
        $eligibleAmountStr = number_format((float) $eligibleAmountStr, 4, '.', '');
        $rewardValueStr = number_format((float) $promotion->reward_value, 4, '.', '');

        if ($promotion->action_type === WalletPromotion::ACTION_FIXED) {
            return $rewardValueStr;
        }

        // Percentage action: (eligibleAmount * rewardValue) / 100
        $rawReward = bcdiv(bcmul($eligibleAmountStr, $rewardValueStr, 4), '100.0000', 4);

        if ($promotion->max_reward_amount !== null) {
            $maxRewardStr = number_format((float) $promotion->max_reward_amount, 4, '.', '');
            if (bccomp($rawReward, $maxRewardStr, 4) === 1) {
                return $maxRewardStr;
            }
        }

        return $rawReward;
    }

    /**
     * Check if customer is eligible for promotion.
     */
    public function isEligible(WalletPromotionContract $promotion, int $customerId, string $spendAmountStr = '0.0000'): bool
    {
        if ($promotion->status !== WalletPromotion::STATUS_ACTIVE) {
            return false;
        }

        $now = now();
        if ($promotion->starts_from && $now->lt($promotion->starts_from)) {
            return false;
        }

        if ($promotion->ends_till && $now->gt($promotion->ends_till)) {
            return false;
        }

        if ($promotion->min_spend_amount !== null) {
            $minSpendStr = number_format((float) $promotion->min_spend_amount, 4, '.', '');
            if (bccomp($spendAmountStr, $minSpendStr, 4) === -1) {
                return false;
            }
        }

        if ($promotion->usage_limit !== null && $promotion->times_used >= $promotion->usage_limit) {
            return false;
        }

        if ($promotion->usage_per_customer !== null) {
            $customerUsages = WalletPromotionUsage::where('promotion_id', $promotion->id)
                ->where('customer_id', $customerId)
                ->whereIn('status', [WalletPromotionUsage::STATUS_APPROVED, WalletPromotionUsage::STATUS_PENDING])
                ->count();

            if ($customerUsages >= $promotion->usage_per_customer) {
                return false;
            }
        }

        return true;
    }

    /**
     * Create Usage and initial Grant.
     */
    public function createGrant(
        WalletPromotionContract $promotion,
        WalletAccount $wallet,
        string $eventKey,
        string $rewardAmountStr,
        string $netCreditedAmountStr,
        string $referenceType,
        int $referenceId,
        string $currencyCode = 'SAR',
        ?array $decisionMeta = null
    ): array {
        $rewardAmountStr = number_format((float) $rewardAmountStr, 4, '.', '');
        $netCreditedAmountStr = number_format((float) $netCreditedAmountStr, 4, '.', '');

        if (bccomp($rewardAmountStr, '0.0000', 4) <= 0) {
            throw new InvalidArgumentException('Reward amount must be greater than zero.');
        }

        $usage = WalletPromotionUsage::create([
            'promotion_id' => $promotion->id,
            'customer_id' => $wallet->customer_id,
            'event_key' => $eventKey,
            'reward_amount' => $rewardAmountStr,
            'base_reward_amount' => $rewardAmountStr,
            'net_credited_amount' => $netCreditedAmountStr,
            'currency_code' => $currencyCode,
            'exchange_rate' => '1.0000',
            'status' => WalletPromotionUsage::STATUS_APPROVED,
            'promotion_snapshot' => $promotion->toArray(),
            'decision_meta' => $decisionMeta,
        ]);

        $expiresAt = $promotion->grant_validity_days
            ? now()->addDays((int) $promotion->grant_validity_days)
            : null;

        // Grant starts with full rewardAmount as remaining, consumed = 0.0000
        $grant = WalletPromotionGrant::create([
            'promotion_id' => $promotion->id,
            'customer_id' => $wallet->customer_id,
            'wallet_id' => $wallet->id,
            'usage_id' => $usage->id,
            'original_amount' => $rewardAmountStr,
            'remaining_amount' => $rewardAmountStr,
            'consumed_amount' => '0.0000',
            'currency_code' => $currencyCode,
            'base_amount' => $rewardAmountStr,
            'status' => WalletPromotionGrant::STATUS_ACTIVE,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'granted_at' => now(),
            'expires_at' => $expiresAt,
        ]);

        // Increment promotion usage count
        $promotion->increment('times_used');
        $promotion->increment('total_allocated', (float) $rewardAmountStr);

        return [
            'usage' => $usage,
            'grant' => $grant,
        ];
    }

    /**
     * Reverse a grant lot on order refund or cancellation.
     * Invariant: original_amount = remaining_amount + consumed_amount
     */
    public function reverseGrantLot(
        WalletPromotionGrantContract $grant,
        string $amountToReverseStr,
        string $reason = 'Order refund reversal'
    ): string {
        $amountToReverseStr = number_format((float) $amountToReverseStr, 4, '.', '');

        if (bccomp($amountToReverseStr, '0.0000', 4) <= 0) {
            throw new InvalidArgumentException('Reversal amount must be positive.');
        }

        $availableInGrant = number_format((float) $grant->remaining_amount, 4, '.', '');
        $currentConsumed = number_format((float) $grant->consumed_amount, 4, '.', '');

        if (bccomp($amountToReverseStr, $availableInGrant, 4) <= 0) {
            // Full amount can be deducted from remaining grant lot
            $newRemaining = bcsub($availableInGrant, $amountToReverseStr, 4);
            $newConsumed = bcadd($currentConsumed, $amountToReverseStr, 4);

            $grant->remaining_amount = $newRemaining;
            $grant->consumed_amount = $newConsumed;
            if (bccomp($newRemaining, '0.0000', 4) === 0) {
                $grant->status = WalletPromotionGrant::STATUS_FULLY_CONSUMED;
            }
            $grant->save();

            return '0.0000'; // No deficit
        }

        // Partial or no remaining amount in grant lot: deficit created
        $deficit = bcsub($amountToReverseStr, $availableInGrant, 4);
        $newConsumed = bcadd($currentConsumed, $availableInGrant, 4);

        $grant->remaining_amount = '0.0000';
        $grant->consumed_amount = $newConsumed;
        $grant->status = WalletPromotionGrant::STATUS_FULLY_CONSUMED;
        $grant->save();

        return $deficit;
    }
}
