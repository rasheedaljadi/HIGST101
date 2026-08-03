<?php

namespace App\Services\Pricing;

use App\Models\HigestPricingRule;
use App\Services\Pricing\DTO\PricingCalculationResult;
use App\Services\Pricing\DTO\PricingContext;
use App\Services\Pricing\Stages\BaseAcquisitionCostStage;
use App\Services\Pricing\Stages\FeeAdjustmentStage;
use App\Services\Pricing\Stages\FreightAdjustmentStage;
use App\Services\Pricing\Stages\MarginCalculationStage;
use App\Services\Pricing\Stages\PricingPipelineStageInterface;
use App\Services\Pricing\Stages\RoundingStage;
use App\Services\Pricing\Stages\SourceDiscountPolicyStage;

/**
 * Modular Pipeline-Based Pricing Engine.
 *
 * Runs a composable chain of pricing stages:
 *   Base Acquisition Cost → Source Discount Policy → Freight Adjustments → Fee Adjustments → Margin Calculation → Rounding
 *
 * Produces a PricingCalculationResult carrying regular selling price, special promotional price,
 * profit amount, margin %, and full stage breakdown log.
 */
class PricingEngine
{
    /**
     * Pipeline stages executed in order.
     *
     * @var PricingPipelineStageInterface[]
     */
    protected array $stages;

    public function __construct(?array $stages = null)
    {
        $this->stages = $stages ?? [
            new BaseAcquisitionCostStage,
            new SourceDiscountPolicyStage,
            new FreightAdjustmentStage,
            new FeeAdjustmentStage,
            new MarginCalculationStage,
            new RoundingStage,
        ];
    }

    /**
     * Execute the pricing pipeline for an acquisition cost and pricing rule.
     *
     * @param  float  $acquisitionCost  Raw source acquisition cost.
     * @param  HigestPricingRule  $rule  Resolved pricing rule.
     * @param  PricingContext|null  $context  Source provider, original list cost, shipping, and fee context.
     */
    public function calculate(
        float $acquisitionCost,
        HigestPricingRule $rule,
        ?PricingContext $context = null,
    ): PricingCalculationResult {
        $context = $context ?? new PricingContext;
        $currentAcquisitionCost = max($acquisitionCost, 0.0);
        $breakdown = [];

        foreach ($this->stages as $stage) {
            $result = $stage->process($currentAcquisitionCost, $rule, $context, $breakdown);
            $currentAcquisitionCost = $result['amount'];
            $breakdown = $result['breakdown'];
        }

        $specialPrice = $breakdown['_rounded_special_price'] ?? null;
        unset($breakdown['_rounded_special_price']);

        $sellingPrice = $currentAcquisitionCost;
        // Effective buying price for customer is specialPrice if present, else sellingPrice
        $effectiveCustomerPrice = $specialPrice ?? $sellingPrice;
        $marginAmount = round($effectiveCustomerPrice - $acquisitionCost, 2);
        $marginPct = $acquisitionCost > 0 ? round(($marginAmount / $acquisitionCost) * 100, 2) : 0.0;

        return new PricingCalculationResult(
            acquisitionCost: $acquisitionCost,
            acquisitionOriginalCost: $context->acquisitionOriginalCost,
            sellingPrice: $sellingPrice,
            specialPrice: $specialPrice,
            marginAmount: $marginAmount,
            marginPercentage: $marginPct,
            breakdown: $breakdown,
        );
    }
}
