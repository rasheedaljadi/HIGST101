<?php

namespace Webkul\Procurement\DTO;

class VerifiedExternalOrderCreated
{
    /**
     * @param  array<string, mixed>  $responseMetadata
     */
    public function __construct(
        public readonly string $externalOrderId,
        public readonly ?string $providerRequestId = null,
        public readonly string $providerStatus = 'WAIT_BUYER_PAY',
        public readonly array $responseMetadata = []
    ) {}
}
