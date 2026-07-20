<?php

namespace Webkul\Fulfillment\Contracts;

interface ExternalRetryPolicy
{
    /**
     * Get delay sequence array in seconds (e.g. [5, 20, 60]).
     *
     * @return array<int>
     */
    public function delays(): array;

    /**
     * Get maximum retry limit.
     *
     * @return int
     */
    public function maxAttempts(): int;

    /**
     * Determine if processing should retry for the given exception.
     *
     * @param  \Exception  $exception
     * @return bool
     */
    public function shouldRetry(\Exception $exception): bool;
}
