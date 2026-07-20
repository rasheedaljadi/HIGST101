<?php

namespace Webkul\Fulfillment\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;

class SyncPaused
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $runId,
        public int $event_version = 1
    ) {}
}
