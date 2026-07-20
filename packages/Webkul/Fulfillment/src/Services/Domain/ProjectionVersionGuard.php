<?php

namespace Webkul\Fulfillment\Services\Domain;

use Webkul\Fulfillment\DataObjects\ProjectionDecision;

class ProjectionVersionGuard
{
    /**
     * Determine if the incoming event should be applied to the projection.
     */
    public static function shouldApply(object $projection, array $payload): ProjectionDecision
    {
        // 1. Compare by external_variant_version (if present)
        if (isset($payload['external_variant_version']) && $projection->external_variant_version !== null) {
            $incomingVersion = (int) $payload['external_variant_version'];
            $currentVersion = (int) $projection->external_variant_version;

            if ($incomingVersion < $currentVersion) {
                return ProjectionDecision::stale(
                    "Incoming version [{$incomingVersion}] is older than current version [{$currentVersion}]",
                    (string) $currentVersion,
                    (string) $incomingVersion
                );
            }

            if ($incomingVersion === $currentVersion) {
                return ProjectionDecision::replay(
                    "Incoming version [{$incomingVersion}] is equal to current version [{$currentVersion}] (Replay)",
                    (string) $currentVersion,
                    (string) $incomingVersion
                );
            }

            // Detect unsafe version jump (potential bug check - dynamic config limit)
            $limit = (int) config('aliexpress.sync.max_version_jump', 100);
            $jump = $incomingVersion - $currentVersion;
            if ($jump > $limit) {
                return ProjectionDecision::unsafeJump(
                    "Unsafe version jump detected: jumped from {$currentVersion} to {$incomingVersion} (diff = {$jump}) exceeding limit {$limit}",
                    (string) $currentVersion,
                    (string) $incomingVersion
                );
            }

            return ProjectionDecision::apply("Incoming version [{$incomingVersion}] is newer");
        }

        // 2. Compare by provider_updated_at (if present)
        if (isset($payload['provider_updated_at']) && $projection->provider_updated_at !== null) {
            $incomingTime = new \Carbon\Carbon($payload['provider_updated_at']);
            $currentTime = new \Carbon\Carbon($projection->provider_updated_at);

            if ($incomingTime->lt($currentTime)) {
                return ProjectionDecision::stale(
                    "Incoming updated time [{$incomingTime}] is older than current [{$currentTime}]",
                    $currentTime->toIso8601String(),
                    $incomingTime->toIso8601String()
                );
            }

            if ($incomingTime->eq($currentTime)) {
                return ProjectionDecision::replay(
                    "Incoming updated time [{$incomingTime}] is equal to current [{$currentTime}] (Replay)",
                    $currentTime->toIso8601String(),
                    $incomingTime->toIso8601String()
                );
            }

            return ProjectionDecision::apply("Incoming timestamp [{$incomingTime}] is newer");
        }

        // 3. Fallback to occurred_at (if present)
        if (isset($payload['occurred_at']) && isset($projection->updated_at)) {
            $incomingTime = new \Carbon\Carbon($payload['occurred_at']);
            $currentTime = new \Carbon\Carbon($projection->updated_at);

            if ($incomingTime->lt($currentTime)) {
                return ProjectionDecision::stale(
                    "Incoming occurred_at time [{$incomingTime}] is older than projection updated_at [{$currentTime}]",
                    $currentTime->toIso8601String(),
                    $incomingTime->toIso8601String()
                );
            }

            if ($incomingTime->eq($currentTime)) {
                return ProjectionDecision::replay(
                    "Incoming occurred_at time [{$incomingTime}] is equal to projection updated_at [{$currentTime}] (Replay)",
                    $currentTime->toIso8601String(),
                    $incomingTime->toIso8601String()
                );
            }

            return ProjectionDecision::apply("Incoming occurred_at [{$incomingTime}] is newer");
        }

        return ProjectionDecision::apply("No version or timestamp information to check, applying by default");
    }
}
