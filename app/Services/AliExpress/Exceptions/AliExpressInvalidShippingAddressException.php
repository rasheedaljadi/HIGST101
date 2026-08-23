<?php

namespace App\Services\AliExpress\Exceptions;

use DomainException;

class AliExpressInvalidShippingAddressException extends DomainException
{
    public function __construct(
        public readonly string $errorCode = 'ALIEXPRESS_SA_NATIONAL_ADDRESS_INVALID_OR_MISSING',
        string $message = 'Saudi Arabia shipping address requires a valid 8-character Short National Address code (4 letters + 4 digits, e.g. ABCD1234).',
        ?\Throwable $previous = null
    ) {
        parent::__construct("[{$errorCode}] {$message}", 0, $previous);
    }
}
