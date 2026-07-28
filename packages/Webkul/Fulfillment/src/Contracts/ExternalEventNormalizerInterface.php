<?php

namespace Webkul\Fulfillment\Contracts;

use Webkul\Fulfillment\DataObjects\NormalizedExternalEvent;

interface ExternalEventNormalizerInterface
{
    /**
     * Map raw external system payload into a standard NormalizedExternalEvent DTO.
     */
    public function normalize(array $payload): NormalizedExternalEvent;
}
