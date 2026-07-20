<?php

namespace App\Services\AliExpress\DTO;

/**
 * Normalized representation of an AliExpress product, decoupled from the
 * raw `ds.product.get` payload shape. Produced by the mapper and consumed
 * by the importer.
 *
 * The top-level text fields (title, description, …) hold the PRIMARY language
 * (English), which is also the language the structural fields — axes, variants,
 * images — are derived from (English axis names like "Color"/"Size" keep the
 * attribute system stable and dictionary-matchable). Per-locale display text
 * fetched in the store's other languages (e.g. real Arabic seller content) is
 * carried in {@see self::$localizedText}, keyed by locale code.
 */
final class NormalizedProduct
{
    /**
     * @param  string[]  $imageUrls  Main gallery image URLs.
     * @param  NormalizedVariantAxis[]  $axes  Configurable axes (empty for simple products).
     * @param  NormalizedVariant[]  $variants  One entry per AliExpress SKU.
     * @param  string[]  $videoUrls  Product video URLs (e.g. mp4), may be empty.
     * @param  array<string, array{title:string, description:string, shortDescription:string, metaTitle:?string, metaKeywords:?string, metaDescription:?string}>  $localizedText
     *                                                                                                                                                                            Per-locale display text keyed by locale code (e.g. "ar", "en").
     */
    public function __construct(
        public string $aliexpressProductId,
        public string $title,
        public string $description,
        public string $shortDescription,
        public ?string $metaTitle,
        public ?string $metaKeywords,
        public ?string $metaDescription,
        public array $imageUrls,
        public array $axes,
        public array $variants,
        public bool $isConfigurable,
        public string $currency,
        public array $videoUrls = [],
        public ?int $aliexpressCategoryId = null,
        public array $localizedText = [],
        public ?string $externalProductVersion = null,
        public ?\Illuminate\Support\Carbon $providerUpdatedAt = null,
    ) {}

    /**
     * The display text for a locale, falling back to the primary (English)
     * fields when that locale was not fetched / has no content.
     *
     * @return array{title:string, description:string, shortDescription:string, metaTitle:?string, metaKeywords:?string, metaDescription:?string}
     */
    public function textForLocale(string $locale): array
    {
        return $this->localizedText[$locale] ?? [
            'title' => $this->title,
            'description' => $this->description,
            'shortDescription' => $this->shortDescription,
            'metaTitle' => $this->metaTitle,
            'metaKeywords' => $this->metaKeywords,
            'metaDescription' => $this->metaDescription,
        ];
    }
}
