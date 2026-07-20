<?php

namespace Webkul\Fulfillment\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;

class SyncResumed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $runId,
        public int $event_version = 1
    ) {}
}
