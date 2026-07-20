<?php

namespace Webkul\Fulfillment\Commands;

class RefreshAccessTokenCommand
{
    public function __construct(
        public int $providerAccountId,
        public string $correlationId,
        public string $causationId
    ) {}
}
