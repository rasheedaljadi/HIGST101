<?php

namespace App\Services\Pricing\Stages;

use App\Models\HigestPricingRule;
use App\Services\Pricing\DTO\PricingContext;

class FreightAdjustmentStage implements PricingPipelineStageInterface
{
    public function process(
        float $currentAmount,
        HigestPricingRule $rule,
        PricingContext $context,
        array $breakdown,
    ): array {
        $shipping = max((float) $context->shippingCost, 0.0);
        $newAmount = $currentAmount + $shipping;

        $breakdown['freight_adjustment'] = [
            'shipping_cost' => $shipping,
            'subtotal_after_freight' => $newAmount,
        ];

        return [
            'amount' => $newAmount,
            'breakdown' => $breakdown,
        ];
    }
}
