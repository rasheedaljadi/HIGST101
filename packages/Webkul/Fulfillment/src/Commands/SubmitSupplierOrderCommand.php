<?php

namespace Webkul\Fulfillment\Commands;

class SubmitSupplierOrderCommand
{
    public function __construct(
        public int $procurementSessionId,
        public string $correlationId,
        public string $causationId,
        public ?string $traceId = null,
        public ?string $spanId = null
    ) {}
}
