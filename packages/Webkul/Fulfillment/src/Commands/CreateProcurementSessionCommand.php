<?php

namespace Webkul\Fulfillment\Commands;

class CreateProcurementSessionCommand
{
    public function __construct(
        public int $purchaseOrderId,
        public int $orderAllocationId,
        public string $correlationId,
        public string $causationId,
        public ?string $traceId = null,
        public ?string $spanId = null
    ) {}
}
