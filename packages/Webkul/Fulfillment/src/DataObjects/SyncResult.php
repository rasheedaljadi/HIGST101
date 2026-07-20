<?php

namespace Webkul\Fulfillment\DataObjects;

class SyncResult
{
    public function __construct(
        public readonly int $processedCount,
        public readonly int $changedCount,
        public readonly int $eventsCount,
        public readonly array $errors,
        public readonly array $warnings,
        public readonly SyncCursor $newCursor,
        public readonly array $changeSets = []
    ) {}
}
