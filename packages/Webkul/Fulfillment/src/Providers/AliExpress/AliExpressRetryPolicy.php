<?php

namespace Webkul\Fulfillment\Providers\AliExpress;

use Webkul\Fulfillment\Contracts\ExternalRetryPolicy;

class AliExpressRetryPolicy implements ExternalRetryPolicy
{
    /**
     * Get retry backoff delays in seconds.
     *
     * @return array<int>
     */
    public function delays(): array
    {
        return [5, 20, 60];
    }

    /**
     * Get maximum retry limit.
     *
     * @return int
     */
    public function maxAttempts(): int
    {
        return 3;
    }

    /**
     * Determine if processing should retry for the given exception.
     *
     * @param  \Exception  $exception
     * @return bool
     */
    public function shouldRetry(\Exception $exception): bool
    {
        $message = strtolower($exception->getMessage());

        // Permanent failures shouldn't retry
        $nonRetryable = [
            'invalid token',
            'unauthorized',
            'product removed',
            'product not found',
            'address invalid',
            'invalid shipping address',
            'permission denied',
        ];

        foreach ($nonRetryable as $needle) {
            if (str_contains($message, $needle)) {
                return false;
            }
        }

        return true;
    }
}
