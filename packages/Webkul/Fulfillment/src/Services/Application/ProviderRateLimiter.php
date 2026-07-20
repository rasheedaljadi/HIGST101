<?php

namespace Webkul\Fulfillment\Services\Application;

use Illuminate\Support\Facades\RateLimiter;
use Webkul\Fulfillment\Exceptions\RateLimitExceededException;

class ProviderRateLimiter
{
    /**
     * Attempt a hit and check rate limit. Throws exception if exceeded.
     */
    public static function checkAndHit(string $provider, string $endpoint, string $operation, int $maxAttempts = 10, int $decaySeconds = 1): void
    {
        $key = "rate_limit:{$provider}:{$endpoint}:{$operation}";

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            throw new RateLimitExceededException($seconds ?: 60, "Rate limit exceeded for provider [{$provider}] on endpoint [{$endpoint}]");
        }

        RateLimiter::hit($key, $decaySeconds);
    }
}
