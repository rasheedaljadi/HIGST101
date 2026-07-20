<?php

namespace Webkul\Fulfillment\Commands;

class CancelAllocationCommand
{
    public function __construct(
        public int $allocationId,
        public int $quantity,
        public string $reason,
        public string $correlationId,
        public string $causationId
    ) {}
}
