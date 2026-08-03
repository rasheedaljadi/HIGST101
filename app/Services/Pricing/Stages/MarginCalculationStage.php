<?php

namespace App\Services\Pricing\Stages;

use App\Models\HigestPricingRule;
use App\Services\Pricing\DTO\PricingContext;

class MarginCalculationStage implements PricingPipelineStageInterface
{
    public function process(
        float $currentAmount,
        HigestPricingRule $rule,
        PricingContext $context,
        array $breakdown,
    ): array {
        $addedMargin = match ($rule->type) {
            'percentage' => $currentAmount * ((float) $rule->value / 100),
            'fixed' => (float) $rule->value,
            default => 0.0,
        };

        $effectiveSalePrice = $currentAmount + $addedMargin;

        $regularPrice = null;
        $specialPrice = null;

        // If displayReferenceCost is set (e.g. PASS_TO_CUSTOMER mode), calculate crossed-out regular price.
        if ($context->displayReferenceCost !== null && $context->displayReferenceCost > $currentAmount) {
            $originalBaseCost = $context->displayReferenceCost + (float) $context->shippingCost + (float) $context->extraFees;
            $originalAddedMargin = match ($rule->type) {
                'percentage' => $originalBaseCost * ((float) $rule->value / 100),
                'fixed' => (float) $rule->value,
                default => 0.0,
            };

            $regularPrice = $originalBaseCost + $originalAddedMargin;
            $specialPrice = $effectiveSalePrice;
        } else {
            $regularPrice = $effectiveSalePrice;
            $specialPrice = null;
        }

        $breakdown['margin_calculation'] = [
            'rule_id' => $rule->id,
            'rule_name' => $rule->name,
            'rule_type' => $rule->type,
            'rule_value' => (float) $rule->value,
            'rule_version' => $rule->version,
            'source_discount_policy' => $rule->source_discount_policy?->value ?? $context->sourceDiscountPolicy,
            'added_margin' => round($addedMargin, 4),
            'calculated_regular_price' => round($regularPrice, 4),
            'calculated_special_price' => $specialPrice !== null ? round($specialPrice, 4) : null,
        ];

        return [
            'amount' => $regularPrice,
            'breakdown' => array_merge($breakdown, [
                '_special_price' => $specialPrice,
            ]),
        ];
    }
}
