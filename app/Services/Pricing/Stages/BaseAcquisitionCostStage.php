<?php

namespace App\Services\Pricing\Stages;

use App\Models\HigestPricingRule;
use App\Services\Pricing\DTO\PricingContext;

/**
 * Pipeline Stage 1: Initializes base acquisition cost log.
 */
class BaseAcquisitionCostStage implements PricingPipelineStageInterface
{
    public function process(
        float $currentAmount,
        HigestPricingRule $rule,
        PricingContext $context,
        array $breakdown,
    ): array {
        $breakdown['base_acquisition_cost'] = $currentAmount;

        return [
            'amount' => $currentAmount,
            'breakdown' => $breakdown,
        ];
    }
}
