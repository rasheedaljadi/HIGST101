<?php

namespace Webkul\Fulfillment\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SyncCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $runId,
        public array $statistics,
        public array $healthSnapshot,
        public int $event_version = 1
    ) {}
}
