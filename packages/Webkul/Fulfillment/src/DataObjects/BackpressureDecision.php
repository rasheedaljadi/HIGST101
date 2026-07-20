<?php

namespace Webkul\Fulfillment\DataObjects;

class BackpressureDecision
{
    public const ACTION_PROCEED = 'PROCEED';
    public const ACTION_THROTTLE = 'THROTTLE';
    public const ACTION_STOP = 'STOP';

    public function __construct(
        public readonly string $action,
        public readonly int $delaySeconds,
        public readonly string $reason,
        public readonly ?string $metric = null,
        public readonly mixed $current = null,
        public readonly mixed $threshold = null
    ) {}

    public static function proceed(string $reason = 'All checks passed'): self
    {
        return new self(self::ACTION_PROCEED, 0, $reason);
    }

    public static function throttle(int $delaySeconds, string $reason, string $metric, mixed $current, mixed $threshold): self
    {
        return new self(self::ACTION_THROTTLE, $delaySeconds, $reason, $metric, $current, $threshold);
    }

    public static function stop(string $reason, string $metric, mixed $current, mixed $threshold): self
    {
        return new self(self::ACTION_STOP, 0, $reason, $metric, $current, $threshold);
    }

    public function shouldProceed(): bool
    {
        return $this->action === self::ACTION_PROCEED;
    }

    public function shouldThrottle(): bool
    {
        return $this->action === self::ACTION_THROTTLE;
    }

    public function shouldStop(): bool
    {
        return $this->action === self::ACTION_STOP;
    }
}
