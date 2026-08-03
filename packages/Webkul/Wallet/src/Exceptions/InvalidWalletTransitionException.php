<?php

namespace Webkul\Wallet\Exceptions;

use RuntimeException;

class InvalidWalletTransitionException extends RuntimeException
{
    public function __construct(string $from, string $to)
    {
        parent::__construct("Invalid wallet status transition from '{$from}' to '{$to}'.");
    }
}
