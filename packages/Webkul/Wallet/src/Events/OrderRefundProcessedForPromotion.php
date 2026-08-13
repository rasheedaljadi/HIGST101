<?php

namespace Webkul\Wallet\Events;

use Illuminate\Queue\SerializesModels;

class OrderRefundProcessedForPromotion
{
    use SerializesModels;

    public function __construct(
        public readonly object $refund,
        public readonly object $order,
        public readonly string $eventKey
    ) {}
}
