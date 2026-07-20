<?php

namespace Webkul\Fulfillment\DataObjects;

class ProviderSyncCapabilities
{
    public function __construct(
        public readonly int $version = 1,
        public readonly bool $supportsIncremental = true,
        public readonly bool $supportsFullSync = true,
        public readonly bool $supportsUpdatedSince = true,
        public readonly bool $supportsPageToken = true,
        public readonly bool $supportsCursor = true
    ) {}

    public function toArray(): array
    {
        return [
            'version'                  => $this->version,
            'supports_incremental'     => $this->supportsIncremental,
            'supports_full_sync'       => $this->supportsFullSync,
            'supports_updated_since'   => $this->supportsUpdatedSince,
            'supports_page_token'      => $this->supportsPageToken,
            'supports_cursor'          => $this->supportsCursor,
        ];
    }
}
