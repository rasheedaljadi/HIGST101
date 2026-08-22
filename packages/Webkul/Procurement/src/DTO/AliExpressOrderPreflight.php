<?php

namespace Webkul\Procurement\DTO;

class AliExpressOrderPreflight
{
    /**
     * @param  array<string, mixed>  $rawDetails
     */
    public function __construct(
        public readonly bool $isSuccess,
        public readonly bool $isDeliverableToDestination,
        public readonly string $destinationCountry,
        public readonly ?string $shippingServiceName = null,
        public readonly float $shippingCost = 0.0,
        public readonly string $shippingCurrency = 'USD',
        public readonly ?int $minDeliveryDays = null,
        public readonly ?int $maxDeliveryDays = null,
        public readonly bool $trackingAvailable = false,
        public readonly ?string $resolvedSkuAttr = null,
        public readonly ?string $errorCode = null,
        public readonly ?string $errorMessage = null,
        public readonly array $rawDetails = []
    ) {}
}
