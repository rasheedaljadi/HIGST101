<?php

namespace Webkul\Wallet\Exceptions;

use RuntimeException;

class InsufficientWalletBalanceException extends RuntimeException
{
    public function __construct(float $required = 0, float $available = 0)
    {
        parent::__construct(
            "Insufficient wallet balance. Required: {$required}, Available: {$available}"
        );
    }
}
