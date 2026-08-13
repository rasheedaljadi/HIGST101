<?php

namespace Webkul\Wallet\Events;

use Illuminate\Queue\SerializesModels;

class OrderPaymentConfirmedForPromotion
{
    use SerializesModels;

    public function __construct(
        public readonly object $order,
        public readonly object $invoice,
        public readonly string $eventKey
    ) {}
}
