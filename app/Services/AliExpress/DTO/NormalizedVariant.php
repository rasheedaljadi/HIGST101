<?php

namespace App\Services\AliExpress\DTO;

/**
 * A single AliExpress SKU normalized into price, stock, and axis options.
 */
final class NormalizedVariant
{
    /**
     * @param  string  $skuId  AliExpress SKU id.
     * @param  float  $price  Sale price in USD (offer_sale_price).
     * @param  int  $stock  Available stock quantity.
     * @param  array<string, string>  $optionsByAxis  [axisName => optionLabel].
     * @param  string[]  $imageUrls  Variant-specific image URLs (may be empty).
     * @param  float|null  $originalPrice  Original/list price in USD (sku_price) when higher than the sale price; null when there is no discount.
     */
    public function __construct(
        public string $skuId,
        public float $price,
        public int $stock,
        public array $optionsByAxis,
        public array $imageUrls,
        public ?float $originalPrice = null,
        public ?string $version = null,
    ) {}
}
