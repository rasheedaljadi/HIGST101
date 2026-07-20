<?php

namespace Webkul\Fulfillment\Contracts;

use Webkul\Fulfillment\DataObjects\NormalizedExternalEvent;

interface ExternalEventNormalizerInterface
{
    /**
     * Map raw external system payload into a standard NormalizedExternalEvent DTO.
     *
     * @param  array  $payload
     * @return \Webkul\Fulfillment\DataObjects\NormalizedExternalEvent
     */
    public function normalize(array $payload): NormalizedExternalEvent;
}
