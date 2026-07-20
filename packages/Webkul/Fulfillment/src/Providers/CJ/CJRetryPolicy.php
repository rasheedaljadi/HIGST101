<?php

namespace Webkul\Fulfillment\Providers\CJ;

use Webkul\Fulfillment\Contracts\ExternalRetryPolicy;

class CJRetryPolicy implements ExternalRetryPolicy
{
    public function delays(): array
    {
        return [10, 30, 120];
    }

    public function maxAttempts(): int
    {
        return 3;
    }

    public function shouldRetry(\Exception $exception): bool
    {
        return true;
    }
}
