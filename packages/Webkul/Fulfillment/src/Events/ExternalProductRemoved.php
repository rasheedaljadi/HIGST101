<?php

namespace Webkul\Fulfillment\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;

class ExternalProductRemoved
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $productId,
        public string $supplierProductId,
        public string $provider,
        public int $event_version = 1
    ) {}
}
