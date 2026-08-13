<?php

namespace Webkul\Wallet\Events;

use Illuminate\Queue\SerializesModels;

class WalletTopUpApprovedForPromotion
{
    use SerializesModels;

    public function __construct(
        public readonly object $topup,
        public readonly object $wallet,
        public readonly string $eventKey
    ) {}
}
