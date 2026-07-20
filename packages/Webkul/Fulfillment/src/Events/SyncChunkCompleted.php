<?php

namespace Webkul\Fulfillment\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;

class SyncChunkCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $runId,
        public int $chunkNumber,
        public int $processed,
        public int $changed,
        public int $published,
        public int $stale,
        public int $replayed,
        public int $durationMs,
        public int $event_version = 1
    ) {}
}
