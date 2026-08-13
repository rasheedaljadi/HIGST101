<?php

namespace Webkul\Wallet\Events;

use Illuminate\Queue\SerializesModels;

class CustomerRegisteredForPromotion
{
    use SerializesModels;

    public function __construct(
        public readonly object $customer,
        public readonly string $eventKey
    ) {}
}
