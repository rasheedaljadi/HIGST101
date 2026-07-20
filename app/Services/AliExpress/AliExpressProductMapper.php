<?php

namespace App\Services\AliExpress;

use App\Exceptions\AliExpress\AliExpressImportException;
use App\Services\AliExpress\DTO\NormalizedProduct;
use App\Services\AliExpress\DTO\NormalizedVariant;
use App\Services\AliExpress\DTO\NormalizedVariantAxis;
use Illuminate\Support\Str;

/**
 * Converts a raw `aliexpress.ds.product.get` response body into a
 * {@see NormalizedProduct} DTO.
 *
 * This is the single place in the codebase that knows AliExpress payload
 * field names. Because the payload shape varies by account and category,
 * every field is read through the tolerant {@see self::firstOf()} helper,
 * which tries several dot-style key paths and returns the first present
 * value. Adjusting to a real payload should only ever touch this class.
 */
class AliExpressProductMapper
{
    /**
     * Map a decoded `ds.product.get` body into a NormalizedProduct.
     *
     * @param  array<string, mixed>  $body  Decoded API response body.
     * @param  string  $id  The AliExpress product id this body was fetched for.
     *
     * @throws AliExpressImportException when the body carries no product base info (Req 4.4).
     */
    public function map(array $body, string $id): NormalizedProduct
    {
        // The live ds.product.get response wraps everything in a top-level
        // "aliexpress_ds_product_get_response" envelope. Unwrap it so the
        // "result.*" paths below resolve for both the wrapped (real API) and
        // already-unwrapped (fixture/test) shapes.
        if (isset($body['aliexpress_ds_product_get_response']) && is_array($body['aliexpress_ds_product_get_response'])) {
            $body = $body['aliexpress_ds_product_get_response'];
        }

        $base = $this->firstOf($body, [
            'result.ae_item_base_info_dto',
            'ae_item_base_info_dto',
            'result.aeop_ae_product',
            'aeop_ae_product',
        ]);

        // No base info means the product was not found for this id (Req 4.4).
        if (! is_array($base) || $base === []) {
            throw new AliExpressImportException(
                'The requested AliExpress product was not found.',
                ['aliexpress_product_id' => $id]
            );
        }

        $title = (string) ($this->firstOf($base, ['subject', 'title', 'product_title']) ?? '');
        $description = (string) ($this->firstOf($base, ['detail', 'description', 'mobile_detail']) ?? '');

        // Bagisto rejects an empty description; fall back to the title.
        if (trim(strip_tags($description)) === '') {
            $description = $title;
        }

        $shortDescription = $this->firstOf($base, ['short_description', 'sub_title', 'subTitle']);
        if (! is_string($shortDescription) || trim($shortDescription) === '') {
            // Derive a short description by truncating the (stripped) description.
            $shortDescription = Str::limit(trim(strip_tags($description)), 150, '');
        }
        $shortDescription = (string) $shortDescription;

        // The description is often pure HTML/images, so the stripped short
        // description can come out empty. Bagisto rejects saving a product with
        // an empty short_description, so fall back to the title.
        if (trim(strip_tags($shortDescription)) === '') {
            $shortDescription = $title;
        }

        // SEO fields: meta_title falls back to the title; the others stay nullable (Req 7.3).
        $metaTitle = $this->firstOf($base, ['meta_title', 'seo_title']);
        $metaTitle = is_string($metaTitle) && trim($metaTitle) !== '' ? $metaTitle : $title;

        $metaKeywords = $this->nullableString($this->firstOf($base, ['meta_keywords', 'keywords']));
        $metaDescription = $this->nullableString($this->firstOf($base, ['meta_description', 'seo_description']));

        $imageUrls = $this->extractGalleryImages($body);

        $videoUrls = $this->extractVideos($body);

        [$axes, $variants] = $this->extractAxesAndVariants($body, $base, $id);

        $isConfigurable = count($variants) > 1 && $axes !== [];

        // The price currency reflects the store's base currency (the importer
        // requests prices in it). Fall back to the configured default.
        $currency = $this->resolveCurrency();

        $aliexpressCategoryId = $this->firstOf($base, ['category_id', 'categoryId']);
        $aliexpressCategoryId = is_numeric($aliexpressCategoryId) ? (int) $aliexpressCategoryId : null;

        $externalProductVersion = (string) ($this->firstOf($base, ['gmt_modified', 'modified_time', 'update_time', 'version']) ?? '');
        $providerUpdatedAt = null;
        $updatedAtRaw = $this->firstOf($base, ['gmt_modified', 'modified_time', 'update_time']);
        if ($updatedAtRaw) {
            try {
                $providerUpdatedAt = \Illuminate\Support\Carbon::parse($updatedAtRaw);
            } catch (\Throwable $e) {
                // Ignore
            }
        }

        return new NormalizedProduct(
            $id,
            $title,
            $description,
            $shortDescription,
            $metaTitle,
            $metaKeywords,
            $metaDescription,
            $imageUrls,
            $axes,
            $variants,
            $isConfigurable,
            $currency,
            $videoUrls,
            $aliexpressCategoryId,
            localizedText: [],
            externalProductVersion: $externalProductVersion,
            providerUpdatedAt: $providerUpdatedAt
        );
    }

    /**
     * Extract ONLY the display-text fields (title, description, short
     * description, SEO) from a secondary-language `ds.product.get` body.
     *
     * Used to enrich a {@see NormalizedProduct} with real per-locale content
     * (e.g. Arabic seller text) without re-deriving structure (axes/variants/
     * images) — those always come from the primary-language map().
     *
     * Returns null when the body carries no usable base info, so the caller can
     * silently fall back to the primary language for that locale.
     *
     * @param  array<string, mixed>  $body
     * @return array{title:string, description:string, shortDescription:string, metaTitle:?string, metaKeywords:?string, metaDescription:?string}|null
     */
    public function extractText(array $body): ?array
    {
        if (isset($body['aliexpress_ds_product_get_response']) && is_array($body['aliexpress_ds_product_get_response'])) {
            $body = $body['aliexpress_ds_product_get_response'];
        }

        $base = $this->firstOf($body, [
            'result.ae_item_base_info_dto',
            'ae_item_base_info_dto',
            'result.aeop_ae_product',
            'aeop_ae_product',
        ]);

        if (! is_array($base) || $base === []) {
            return null;
        }

        $title = (string) ($this->firstOf($base, ['subject', 'title', 'product_title']) ?? '');

        // No title means nothing usable for this locale.
        if (trim($title) === '') {
            return null;
        }

        $description = (string) ($this->firstOf($base, ['detail', 'description', 'mobile_detail']) ?? '');
        if (trim(strip_tags($description)) === '') {
            $description = $title;
        }

        $shortDescription = $this->firstOf($base, ['short_description', 'sub_title', 'subTitle']);
        if (! is_string($shortDescription) || trim($shortDescription) === '') {
            $shortDescription = Str::limit(trim(strip_tags($description)), 150, '');
        }
        $shortDescription = (string) $shortDescription;
        if (trim(strip_tags($shortDescription)) === '') {
            $shortDescription = $title;
        }

        $metaTitle = $this->firstOf($base, ['meta_title', 'seo_title']);
        $metaTitle = is_string($metaTitle) && trim($metaTitle) !== '' ? $metaTitle : $title;

        return [
            'title' => $title,
            'description' => $description,
            'shortDescription' => $shortDescription,
            'metaTitle' => $metaTitle,
            'metaKeywords' => $this->nullableString($this->firstOf($base, ['meta_keywords', 'keywords'])),
            'metaDescription' => $this->nullableString($this->firstOf($base, ['meta_description', 'seo_description'])),
        ];
    }

    /**
     * Extract approved product video URLs from the multimedia info node.
     *
     * AliExpress nests videos under
     * `ae_multimedia_info_dto.ae_video_dtos.ae_video_d_t_o[]`, each with a
     * `media_url` (e.g. an .mp4) and a `media_status`. Only `approved` videos
     * are imported.
     *
     * @param  array<string, mixed>  $body
     * @return string[]
     */
    protected function extractVideos(array $body): array
    {
        $videoList = $this->normalizeList($this->firstOf($body, [
            'result.ae_multimedia_info_dto.ae_video_dtos.ae_video_d_t_o',
            'ae_multimedia_info_dto.ae_video_dtos.ae_video_d_t_o',
        ]));

        $urls = [];

        foreach ($videoList as $video) {
            if (! is_array($video)) {
                continue;
            }

            $status = $this->nullableString($this->firstOf($video, ['media_status']));

            // When a status is present, only import approved videos; tolerate
            // its absence (some payloads omit it).
            if ($status !== null && strtolower($status) !== 'approved') {
                continue;
            }

            $url = $this->nullableString($this->firstOf($video, ['media_url', 'video_url', 'url']));

            if ($url !== null) {
                $urls[] = $url;
            }
        }

        return array_values(array_unique($urls));
    }

    /**
     * Extract the main gallery image URLs from the multimedia info node.
     *
     * AliExpress returns these as a single `;`-separated string.
     *
     * @param  array<string, mixed>  $body
     * @return string[]
     */
    protected function extractGalleryImages(array $body): array
    {
        $imageString = $this->firstOf($body, [
            'result.ae_multimedia_info_dto.image_urls',
            'ae_multimedia_info_dto.image_urls',
            'result.ae_multimedia_info_dto.image_url',
            'ae_multimedia_info_dto.image_url',
        ]);

        if (! is_string($imageString) || $imageString === '') {
            return [];
        }

        $urls = array_map('trim', explode(';', $imageString));

        return array_values(array_unique(array_filter($urls, fn ($url) => $url !== '')));
    }

    /**
     * Build the configurable axes and per-SKU variants from the SKU info node.
     *
     * @param  array<string, mixed>  $body
     * @param  array<string, mixed>  $base
     * @return array{0: NormalizedVariantAxis[], 1: NormalizedVariant[]}
     */
    protected function extractAxesAndVariants(array $body, array $base, string $id): array
    {
        $prefix = (string) config('aliexpress.import.attribute_code_prefix', 'ae_');

        $skuList = $this->firstOf($body, [
            'result.ae_item_sku_info_dtos.ae_item_sku_info_d_t_o',
            'ae_item_sku_info_dtos.ae_item_sku_info_d_t_o',
            'result.aeop_ae_product_s_k_us.aeop_ae_product_sku',
            'aeop_ae_product_s_k_us.aeop_ae_product_sku',
        ]);

        $skuList = $this->normalizeList($skuList);

        // Accumulate axes keyed by AliExpress property name, preserving order
        // and distinct values.
        $axesByName = [];
        $variants = [];

        foreach ($skuList as $sku) {
            if (! is_array($sku)) {
                continue;
            }

            $skuId = (string) ($this->firstOf($sku, ['sku_id', 'id', 'sku_code']) ?? '');
            $price = (float) ($this->firstOf($sku, ['offer_sale_price', 'sku_price', 'offer_bulk_sale_price', 'price']) ?? 0);
            $stock = (int) ($this->firstOf($sku, ['sku_available_stock', 'ipm_sku_stock', 'sku_stock']) ?? 0);

            // Original/list price (sku_price) drives the discount: when it is
            // higher than the sale price, the importer stores it as the regular
            // price and the sale price as Bagisto's special_price.
            $listPrice = (float) ($this->firstOf($sku, ['sku_price', 'price']) ?? 0);
            $originalPrice = $listPrice > $price ? $listPrice : null;

            $properties = $this->normalizeList($this->firstOf($sku, [
                'ae_sku_property_dtos.ae_sku_property_d_t_o',
                'aeop_s_k_u_property.aeop_sku_property',
            ]));

            $optionsByAxis = [];
            $variantImages = [];

            foreach ($properties as $property) {
                if (! is_array($property)) {
                    continue;
                }

                $name = $this->nullableString($this->firstOf($property, ['sku_property_name', 'property_name']));
                $value = $this->nullableString($this->firstOf($property, [
                    'sku_property_value',
                    'property_value_definition_name',
                    'property_value',
                ]));

                if ($name === null || $value === null) {
                    continue;
                }

                $optionsByAxis[$name] = $value;

                if (! isset($axesByName[$name])) {
                    $axesByName[$name] = [
                        'code' => $prefix.Str::slug($name, '_'),
                        'values' => [],
                    ];
                }

                if (! in_array($value, $axesByName[$name]['values'], true)) {
                    $axesByName[$name]['values'][] = $value;
                }

                $skuImage = $this->nullableString($this->firstOf($property, ['sku_image', 'image']));
                if ($skuImage !== null) {
                    $variantImages[] = $skuImage;
                }
            }

            $variants[] = new NormalizedVariant(
                $skuId,
                $price,
                $stock,
                $optionsByAxis,
                array_values(array_unique($variantImages)),
                $originalPrice,
            );
        }

        // Fallback: a payload with base info but no parsed SKUs still yields a
        // single simple variant so the importer can create a simple product.
        if ($variants === []) {
            $variants[] = new NormalizedVariant(
                $id,
                (float) ($this->firstOf($base, ['sale_price', 'price', 'offer_sale_price']) ?? 0),
                (int) ($this->firstOf($base, ['available_stock', 'stock', 'total_available_stock']) ?? 0),
                [],
                [],
            );
        }

        $axes = [];
        foreach ($axesByName as $name => $data) {
            $axes[] = new NormalizedVariantAxis($name, $data['code'], $data['values']);
        }

        return [$axes, $variants];
    }

    /**
     * Resolve the price currency: the store's base currency (the importer
     * requests AliExpress prices in it). Falls back to the configured default
     * when the base currency cannot be resolved (e.g. outside a booted store).
     */
    private function resolveCurrency(): string
    {
        try {
            $base = core()->getBaseCurrencyCode();

            if (is_string($base) && $base !== '') {
                return $base;
            }
        } catch (\Throwable $e) {
            // Fall through to the configured default.
        }

        return (string) config('aliexpress.import.target_currency', 'USD');
    }

    /**
     * Return the first present (non-null, non-empty-string) value found by
     * trying each dot-style key path in order. Tolerates absent keys.
     *
     * @param  array<string, mixed>  $array
     * @param  string[]  $paths
     */
    private function firstOf(array $array, array $paths): mixed
    {
        foreach ($paths as $path) {
            $value = data_get($array, $path);

            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    /**
     * Normalize a value that may be a list, a single associative item, or
     * absent into a plain array of items.
     *
     * @return array<int, mixed>
     */
    private function normalizeList(mixed $value): array
    {
        if (! is_array($value) || $value === []) {
            return [];
        }

        // A list (sequential keys) is already in the desired shape; a single
        // associative item is wrapped into a one-element list.
        return array_is_list($value) ? $value : [$value];
    }

    /**
     * Coerce a value into a trimmed non-empty string, or null when absent/blank.
     */
    private function nullableString(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
