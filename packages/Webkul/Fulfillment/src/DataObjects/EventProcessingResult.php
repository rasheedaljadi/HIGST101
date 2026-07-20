<?php

namespace Webkul\Fulfillment\DataObjects;

class EventProcessingResult
{
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_DUPLICATE = 'duplicate';
    public const STATUS_STALE = 'stale';

    protected string $status;

    public function __construct(string $status)
    {
        $this->status = $status;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function isAccepted(): bool
    {
        return $this->status === self::STATUS_ACCEPTED;
    }

    public function isDuplicate(): bool
    {
        return $this->status === self::STATUS_DUPLICATE;
    }

    public function isStale(): bool
    {
        return $this->status === self::STATUS_STALE;
    }
}
