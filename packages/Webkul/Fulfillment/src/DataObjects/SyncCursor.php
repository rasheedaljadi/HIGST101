<?php

namespace Webkul\Fulfillment\DataObjects;

use Webkul\Fulfillment\Enums\SyncStrategy;

class SyncCursor
{
    public function __construct(
        public readonly int $version,
        public readonly string $provider,
        public readonly ?string $updated_since,
        public readonly ?string $page_token,
        public readonly ?string $last_product_id,
        public readonly ?string $api_cursor,
        public readonly SyncStrategy $sync_strategy
    ) {}

    public static function createDefault(string $provider, SyncStrategy $strategy = SyncStrategy::INCREMENTAL): self
    {
        return new self(
            1,
            $provider,
            null,
            null,
            null,
            null,
            $strategy
        );
    }

    public function withNextPage(?string $pageToken): self
    {
        return new self(
            $this->version,
            $this->provider,
            $this->updated_since,
            $pageToken,
            $this->last_product_id,
            $this->api_cursor,
            $this->sync_strategy
        );
    }

    public function withLastProductId(?string $lastProductId): self
    {
        return new self(
            $this->version,
            $this->provider,
            $this->updated_since,
            $this->page_token,
            $lastProductId,
            $this->api_cursor,
            $this->sync_strategy
        );
    }

    public function withUpdatedSince(?string $time): self
    {
        return new self(
            $this->version,
            $this->provider,
            $time,
            $this->page_token,
            $this->last_product_id,
            $this->api_cursor,
            $this->sync_strategy
        );
    }

    public function toArray(): array
    {
        return [
            'version'         => $this->version,
            'provider'        => $this->provider,
            'updated_since'   => $this->updated_since,
            'page_token'      => $this->page_token,
            'last_product_id' => $this->last_product_id,
            'api_cursor'      => $this->api_cursor,
            'sync_strategy'   => $this->sync_strategy->value,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (int) ($data['version'] ?? 1),
            $data['provider'] ?? '',
            $data['updated_since'] ?? null,
            $data['page_token'] ?? null,
            $data['last_product_id'] ?? null,
            $data['api_cursor'] ?? null,
            SyncStrategy::from($data['sync_strategy'] ?? SyncStrategy::INCREMENTAL->value)
        );
    }
}
