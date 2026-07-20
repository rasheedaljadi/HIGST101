<?php

namespace Webkul\Fulfillment\Events\Procurement;

class ProcurementFailed
{
    public function __construct(
        public int $sessionId,
        public int $purchaseOrderId,
        public string $errorMessage,
        public string $correlationId,
        public string $causationId,
        public string $eventVersion = '1',
        public string $schemaVersion = '1'
    ) {}
}
