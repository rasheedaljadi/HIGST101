<?php

namespace Webkul\Procurement\DTO;

class AliExpressOrderSnapshot
{
    /**
     * @param  array<string, mixed>  $rawResponse
     */
    public function __construct(
        public readonly string $externalOrderId,
        public readonly string $orderStatus,
        public readonly ?string $trackingNumber = null,
        public readonly ?string $carrierName = null,
        public readonly ?string $rawStatus = null,
        public readonly array $rawResponse = [],
        public readonly ?int $overTimeLeft = null,
        public readonly ?string $paymentDeadlineAt = null
    ) {}
}
