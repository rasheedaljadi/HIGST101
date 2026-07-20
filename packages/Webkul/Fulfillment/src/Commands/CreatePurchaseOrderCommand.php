<?php

namespace Webkul\Fulfillment\Commands;

class CreatePurchaseOrderCommand
{
    public function __construct(
        public int $orderId,
        public int $orderAllocationId,
        public string $providerCode,
        public string $correlationId,
        public string $causationId
    ) {}
}
