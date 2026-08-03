<?php

namespace App\Services\Pricing\Stages;

use App\Models\HigestPricingRule;
use App\Services\Pricing\DTO\PricingContext;

class FeeAdjustmentStage implements PricingPipelineStageInterface
{
    public function process(
        float $currentAmount,
        HigestPricingRule $rule,
        PricingContext $context,
        array $breakdown,
    ): array {
        $extra = max((float) $context->extraFees, 0.0);
        $newAmount = $currentAmount + $extra;

        $breakdown['fee_adjustment'] = [
            'extra_fees' => $extra,
            'subtotal_after_fees' => $newAmount,
        ];

        return [
            'amount' => $newAmount,
            'breakdown' => $breakdown,
        ];
    }
}
