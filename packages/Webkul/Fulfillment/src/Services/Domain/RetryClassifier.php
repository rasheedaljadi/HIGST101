<?php

namespace Webkul\Fulfillment\Services\Domain;

class RetryClassifier
{
    /**
     * Determine if an error code/message is transient and retryable.
     */
    public function isRetryable(string $code, string $message): bool
    {
        $transientCodes = ['500', '10001', 'RATE_LIMIT', 'TIMEOUT', 'CONNECTION_ERROR'];
        if (in_array(strtoupper($code), $transientCodes, true)) {
            return true;
        }

        $transientMessages = ['timeout', 'connection', 'rate limit', 'throttled', 'server error'];
        foreach ($transientMessages as $msg) {
            if (stripos($message, $msg) !== false) {
                return true;
            }
        }

        return false;
    }
}
