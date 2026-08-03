<?php

namespace App\Services\Pricing\DTO;

/**
 * Value object encapsulating provider, original list cost, channel, shipping, and fee context
 * for price calculation pipelines.
 */
final class PricingContext
{
    public function __construct(
        public string $sourceProvider = 'aliexpress',
        public string $currency = 'USD',
        public ?float $acquisitionOriginalCost = null,
        public ?string $sourceDiscountPolicy = null,
        public ?float $displayReferenceCost = null,
        public ?int $channelId = null,
        public ?string $countryCode = 'SA',
        public float $shippingCost = 0.0,
        public float $extraFees = 0.0,
        public array $metadata = [],
    ) {}
}
