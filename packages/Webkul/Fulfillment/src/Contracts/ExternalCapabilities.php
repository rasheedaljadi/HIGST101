<?php

namespace Webkul\Fulfillment\Contracts;

interface ExternalCapabilities
{
    /**
     * Supports receiving webhook updates.
     */
    public function supportsWebhook(): bool;

    /**
     * Supports active status polling.
     */
    public function supportsPolling(): bool;

    /**
     * Supports cancelling orders remotely.
     */
    public function supportsCancel(): bool;

    /**
     * Supports partial shipments.
     */
    public function supportsPartialShipment(): bool;

    /**
     * Supports live tracking updates.
     */
    public function supportsTrackingUpdates(): bool;
}
