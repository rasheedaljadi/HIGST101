<?php

namespace App\Services\Pricing\Stages;

use App\Models\HigestPricingRule;
use App\Services\Pricing\DTO\PricingContext;

interface PricingPipelineStageInterface
{
    /**
     * Process a calculation stage in the pipeline.
     *
     * @param  float  $currentAmount  Accumulated amount from preceding stages.
     * @param  HigestPricingRule  $rule  The applied merchant pricing rule.
     * @param  PricingContext  $context  Provider, shipping, and fee context.
     * @param  array  $breakdown  Accumulated stage log.
     * @return array{amount: float, breakdown: array} Modified amount and breakdown.
     */
    public function process(
        float $currentAmount,
        HigestPricingRule $rule,
        PricingContext $context,
        array $breakdown,
    ): array;
}
