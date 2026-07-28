<?php

namespace Webkul\Fulfillment\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SyncPaused
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $runId,
        public int $event_version = 1
    ) {}
}
