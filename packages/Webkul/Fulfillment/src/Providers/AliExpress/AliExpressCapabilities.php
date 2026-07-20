<?php

namespace Webkul\Fulfillment\Providers\AliExpress;

use Webkul\Fulfillment\Contracts\ExternalCapabilities;

class AliExpressCapabilities implements ExternalCapabilities
{
    public function supportsWebhook(): bool
    {
        return true;
    }

    public function supportsPolling(): bool
    {
        return true;
    }

    public function supportsCancel(): bool
    {
        return true;
    }

    public function supportsPartialShipment(): bool
    {
        return false;
    }

    public function supportsTrackingUpdates(): bool
    {
        return true;
    }
}
