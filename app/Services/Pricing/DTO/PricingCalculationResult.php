<?php

namespace App\Services\Pricing\DTO;

/**
 * Result object returned by the PricingEngine pipeline containing the calculated
 * regular selling price, promotional special price (if source has a discount),
 * financial margins, and stage breakdown log.
 */
final class PricingCalculationResult
{
    /**
     * @param  float  $acquisitionCost  Effective acquisition cost (sale price from source).
     * @param  float|null  $acquisitionOriginalCost  Source list price before discount.
     * @param  float  $sellingPrice  Regular customer selling price (Bagisto `price`).
     * @param  float|null  $specialPrice  Discounted selling price (Bagisto `special_price`), or null if no discount.
     * @param  float  $marginAmount  Profit amount on effective sale price.
     * @param  float  $marginPercentage  Margin percentage on effective acquisition cost.
     * @param  array<string, mixed>  $breakdown  Stage-by-stage pipeline output log.
     */
    public function __construct(
        public float $acquisitionCost,
        public ?float $acquisitionOriginalCost,
        public float $sellingPrice,
        public ?float $specialPrice,
        public float $marginAmount,
        public float $marginPercentage,
        public array $breakdown = [],
    ) {}
}
