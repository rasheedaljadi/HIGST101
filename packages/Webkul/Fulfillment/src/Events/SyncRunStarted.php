<?php

namespace Webkul\Fulfillment\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SyncRunStarted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $runId,
        public string $provider,
        public int $event_version = 1
    ) {}
}
