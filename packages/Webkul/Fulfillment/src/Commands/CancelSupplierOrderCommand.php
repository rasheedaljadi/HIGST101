<?php

namespace Webkul\Fulfillment\Commands;

class CancelSupplierOrderCommand
{
    public function __construct(
        public int $procurementSessionId,
        public string $reason,
        public string $correlationId,
        public string $causationId,
        public ?string $traceId = null,
        public ?string $spanId = null
    ) {}
}
