<?php

namespace Webkul\Fulfillment\Services\Application;

use Illuminate\Support\Facades\Cache;

class ProviderCircuitBreaker
{
    private const STATE_CLOSED = 'CLOSED';
    private const STATE_OPEN = 'OPEN';
    private const STATE_HALF_OPEN = 'HALF_OPEN';

    private const FAILURE_THRESHOLD = 5;
    private const COOLDOWN_SECONDS = 300; // 5 minutes

    /**
     * Check if the circuit is open. If open, returns true (must fail fast).
     */
    public static function isBlocked(string $provider, string $endpoint, string $operation): bool
    {
        $stateKey = self::stateKey($provider, $endpoint, $operation);
        $state = Cache::get($stateKey, self::STATE_CLOSED);

        if ($state === self::STATE_OPEN) {
            $openTimeKey = self::openTimeKey($provider, $endpoint, $operation);
            $openedAt = Cache::get($openTimeKey);

            if ($openedAt && (time() - $openedAt) > self::COOLDOWN_SECONDS) {
                // Cooldown period expired, transition to HALF_OPEN to test
                Cache::put($stateKey, self::STATE_HALF_OPEN);
                return false;
            }

            return true; // Still open (blocked)
        }

        return false;
    }

    /**
     * Record a successful invocation, resetting failure counts and closing the circuit.
     */
    public static function recordSuccess(string $provider, string $endpoint, string $operation): void
    {
        $stateKey = self::stateKey($provider, $endpoint, $operation);
        $failureKey = self::failureKey($provider, $endpoint, $operation);

        Cache::forget($failureKey);
        Cache::put($stateKey, self::STATE_CLOSED);
    }

    /**
     * Record a failed invocation, incrementing counts. Trips the circuit if threshold is reached.
     */
    public static function recordFailure(string $provider, string $endpoint, string $operation): void
    {
        $stateKey = self::stateKey($provider, $endpoint, $operation);
        $state = Cache::get($stateKey, self::STATE_CLOSED);

        if ($state === self::STATE_HALF_OPEN) {
            // Failure in half-open immediately trips circuit back to open
            self::trip($provider, $endpoint, $operation);
            return;
        }

        $failureKey = self::failureKey($provider, $endpoint, $operation);
        $failures = (int) Cache::get($failureKey, 0) + 1;
        Cache::put($failureKey, $failures, self::COOLDOWN_SECONDS);

        if ($failures >= self::FAILURE_THRESHOLD) {
            self::trip($provider, $endpoint, $operation);
        }
    }

    private static function trip(string $provider, string $endpoint, string $operation): void
    {
        $stateKey = self::stateKey($provider, $endpoint, $operation);
        $openTimeKey = self::openTimeKey($provider, $endpoint, $operation);

        Cache::put($stateKey, self::STATE_OPEN);
        Cache::put($openTimeKey, time());
    }

    private static function stateKey(string $p, string $e, string $o): string
    {
        return "cb:state:{$p}:{$e}:{$o}";
    }

    private static function failureKey(string $p, string $e, string $o): string
    {
        return "cb:failures:{$p}:{$e}:{$o}";
    }

    private static function openTimeKey(string $p, string $e, string $o): string
    {
        return "cb:opentime:{$p}:{$e}:{$o}";
    }
}
