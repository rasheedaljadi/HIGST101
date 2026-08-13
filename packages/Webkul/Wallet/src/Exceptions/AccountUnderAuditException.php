<?php

namespace Webkul\Wallet\Exceptions;

use Exception;

class AccountUnderAuditException extends Exception
{
    public function __construct(string $message = 'This wallet account is currently under audit review and promotional operations are temporarily restricted.', int $code = 403, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
