<?php

namespace Webkul\Fulfillment\Contracts;

use Webkul\Fulfillment\DataObjects\SyncCursor;
use Webkul\Fulfillment\DataObjects\NormalizedExternalProductBatch;
use Webkul\Fulfillment\DataObjects\ProviderSyncCapabilities;

interface SyncProviderInterface
{
    /**
     * Get the provider capabilities.
     */
    public function getCapabilities(): ProviderSyncCapabilities;

    /**
     * Fetch a batch of external products based on cursor.
     */
    public function fetchProductsBatch(SyncCursor $cursor, int $batchSize): NormalizedExternalProductBatch;
}
