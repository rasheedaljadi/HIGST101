<?php

namespace Webkul\Fulfillment\DataObjects;

class ProjectionDecision
{
    public const STATUS_APPLY = 'APPLY';
    public const STATUS_STALE = 'STALE';
    public const STATUS_REPLAY = 'REPLAY';
    public const STATUS_UNSAFE_VERSION_JUMP = 'UNSAFE_VERSION_JUMP';

    public function __construct(
        public string $status,
        public string $reason,
        public ?string $currentVersion = null,
        public ?string $incomingVersion = null
    ) {}

    public static function apply(string $reason = 'Event is newer'): self
    {
        return new self(self::STATUS_APPLY, $reason);
    }

    public static function stale(string $reason, ?string $current = null, ?string $incoming = null): self
    {
        return new self(self::STATUS_STALE, $reason, $current, $incoming);
    }

    public static function replay(string $reason, ?string $current = null, ?string $incoming = null): self
    {
        return new self(self::STATUS_REPLAY, $reason, $current, $incoming);
    }

    public static function unsafeJump(string $reason, ?string $current = null, ?string $incoming = null): self
    {
        return new self(self::STATUS_UNSAFE_VERSION_JUMP, $reason, $current, $incoming);
    }

    public function shouldApply(): bool
    {
        return $this->status === self::STATUS_APPLY;
    }

    public function isUnsafeJump(): bool
    {
        return $this->status === self::STATUS_UNSAFE_VERSION_JUMP;
    }
}
