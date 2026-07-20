<?php

namespace Webkul\Fulfillment\Providers\CJ;

use Webkul\Fulfillment\Contracts\ExternalCapabilities;

class CJCapabilities implements ExternalCapabilities
{
    public function supportsWebhook(): bool
    {
        return true;
    }

    public function supportsPolling(): bool
    {
        return false;
    }

    public function supportsCancel(): bool
    {
        return false;
    }

    public function supportsPartialShipment(): bool
    {
        return true;
    }

    public function supportsTrackingUpdates(): bool
    {
        return true;
    }
}
