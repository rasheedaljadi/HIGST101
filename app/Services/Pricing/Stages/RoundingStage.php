<?php

namespace App\Services\Pricing\Stages;

use App\Models\HigestPricingRule;
use App\Services\Pricing\DTO\PricingContext;

class RoundingStage implements PricingPipelineStageInterface
{
    public function process(
        float $currentAmount,
        HigestPricingRule $rule,
        PricingContext $context,
        array $breakdown,
    ): array {
        $roundedRegular = round(max($currentAmount, 0.0), 2);
        $rawSpecial = $breakdown['_special_price'] ?? null;
        $roundedSpecial = $rawSpecial !== null ? round(max((float) $rawSpecial, 0.0), 2) : null;

        unset($breakdown['_special_price']);

        $breakdown['rounding'] = [
            'raw_amount' => $currentAmount,
            'final_selling_price' => $roundedRegular,
            'final_special_price' => $roundedSpecial,
        ];

        return [
            'amount' => $roundedRegular,
            'breakdown' => array_merge($breakdown, [
                '_rounded_special_price' => $roundedSpecial,
            ]),
        ];
    }
}
