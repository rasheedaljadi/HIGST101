<?php

namespace App\Exceptions\AliExpress;

use Exception;
use Throwable;

/**
 * Raised for all handled AliExpress import failure modes.
 *
 * Carries a human-readable (Arabic-localized) message plus an optional
 * context array used for safe logging (ids, codes, counts — never secrets).
 */
class AliExpressImportException extends Exception
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(string $message, protected array $context = [], ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    /**
     * Additional, secret-free context for logging.
     *
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return $this->context;
    }
}
