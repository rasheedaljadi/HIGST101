<?php

namespace Webkul\Fulfillment\Commands;

class ReserveAllocationCommand
{
    public function __construct(
        public int $orderId,
        public int $orderItemId,
        public string $allocationType,
        public string $sourceCode,
        public int $quantity,
        public string $correlationId,
        public string $causationId
    ) {}
}
