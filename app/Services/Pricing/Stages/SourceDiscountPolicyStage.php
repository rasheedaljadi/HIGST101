<?php

namespace App\Services\Pricing\Stages;

use App\Enums\SourceDiscountPolicy;
use App\Models\HigestPricingRule;
use App\Services\Pricing\DTO\PricingContext;

/**
 * Pipeline Stage 2: Evaluates merchant source discount policy (PASS_TO_CUSTOMER vs ABSORB_BY_HIGEST).
 *
 * Sets $context->displayReferenceCost for regular price calculations if PASS_TO_CUSTOMER is active,
 * without EVER mutating the financial $currentAmount (acquisition_cost).
 */
class SourceDiscountPolicyStage implements PricingPipelineStageInterface
{
    public function process(
        float $currentAmount,
        HigestPricingRule $rule,
        PricingContext $context,
        array $breakdown,
    ): array {
        $policy = $rule->source_discount_policy?->value
            ?? $context->sourceDiscountPolicy
            ?? SourceDiscountPolicy::PASS_TO_CUSTOMER->value;

        if ($policy === SourceDiscountPolicy::PASS_TO_CUSTOMER->value
            && $context->acquisitionOriginalCost !== null
            && $context->acquisitionOriginalCost > $currentAmount
        ) {
            $context->displayReferenceCost = $context->acquisitionOriginalCost;
        } else {
            $context->displayReferenceCost = null;
        }

        $breakdown['source_discount_policy'] = [
            'policy' => $policy,
            'acquisition_original_cost' => $context->acquisitionOriginalCost,
            'acquisition_cost' => $currentAmount,
            'display_reference_cost' => $context->displayReferenceCost,
        ];

        return [
            'amount' => $currentAmount,
            'breakdown' => $breakdown,
        ];
    }
}
