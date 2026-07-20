<?php

namespace Webkul\Fulfillment\Events\Procurement;

class ProcurementValidated
{
    public function __construct(
        public int $sessionId,
        public string $status,
        public string $priceDecision,
        public string $stockDecision,
        public string $correlationId,
        public string $causationId,
        public string $eventVersion = '1',
        public string $schemaVersion = '1'
    ) {}
}
