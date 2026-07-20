<?php

namespace Webkul\Fulfillment\DataObjects;

class NormalizedExternalProductBatch
{
    /**
     * @param array $products Array of normalized products
     */
    public function __construct(
        public readonly string $provider,
        public readonly array $products,
        public readonly ?string $next_page_token = null,
        public readonly bool $has_more = false
    ) {}

    public function toArray(): array
    {
        return [
            'provider'        => $this->provider,
            'products'        => $this->products,
            'next_page_token' => $this->next_page_token,
            'has_more'        => $this->has_more,
        ];
    }
}
