<?php

namespace Webkul\Procurement\Exceptions;

use DomainException;
use Throwable;

class AliExpressAuthorizationUnavailableException extends DomainException
{
    public function __construct(
        string $message = 'AliExpress OAuth authorization context is unavailable or expired.',
        public readonly string $errorCode = 'ALIEXPRESS_AUTHORIZATION_CONTEXT_UNAVAILABLE',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
