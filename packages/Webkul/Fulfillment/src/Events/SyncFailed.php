<?php

namespace Webkul\Fulfillment\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;

class SyncFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $runId,
        public string $errorMessage,
        public int $event_version = 1
    ) {}
}
