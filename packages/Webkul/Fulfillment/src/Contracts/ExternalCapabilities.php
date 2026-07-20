<?php

namespace Webkul\Fulfillment\Contracts;

interface ExternalCapabilities
{
    /**
     * Supports receiving webhook updates.
     *
     * @return bool
     */
    public function supportsWebhook(): bool;

    /**
     * Supports active status polling.
     *
     * @return bool
     */
    public function supportsPolling(): bool;

    /**
     * Supports cancelling orders remotely.
     *
     * @return bool
     */
    public function supportsCancel(): bool;

    /**
     * Supports partial shipments.
     *
     * @return bool
     */
    public function supportsPartialShipment(): bool;

    /**
     * Supports live tracking updates.
     *
     * @return bool
     */
    public function supportsTrackingUpdates(): bool;
}
