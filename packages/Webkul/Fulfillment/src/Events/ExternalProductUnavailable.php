<?php

namespace Webkul\Fulfillment\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExternalProductUnavailable
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $productId,
        public string $supplierProductId,
        public string $provider,
        public string $reason,
        public int $event_version = 1
    ) {}
}
