<?php

namespace Webkul\Fulfillment\Exceptions;

class RateLimitExceededException extends \Exception
{
    protected int $retryAfter;

    public function __construct(int $retryAfter = 60, string $message = "Rate limit exceeded", int $code = 429, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->retryAfter = $retryAfter;
    }

    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }
}
