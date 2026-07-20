<?php

use App\Exceptions\AliExpress\AliExpressImportException;
use App\Services\AliExpress\AliExpressProductMapper;
use App\Services\AliExpress\DTO\NormalizedProduct;
use App\Services\AliExpress\DTO\NormalizedVariant;
use App\Services\AliExpress\DTO\NormalizedVariantAxis;
use Tests\Unit\AliExpress\MapperTestCase;

uses(MapperTestCase::class);

/*
|--------------------------------------------------------------------------
| AliExpressProductMapper unit tests
|--------------------------------------------------------------------------
|
| Covers Requirements 4.4, 6.1, 6.2, 7.3, 9.2, 9.3: mapping a raw
| `aliexpress.ds.product.get` body into a NormalizedProduct DTO — textual
| content + SEO fallback, configurable axes/variants, gallery parsing,
| simple-vs-configurable type detection, and the "product not found" guard.
|
*/

/**
 * Decode a JSON fixture from the fixtures directory into an array body.
 *
 * @return array<string, mixed>
 */
function loadMapperFixture(string $name): array
{
    $path = __DIR__.'/fixtures/'.$name;

    return json_decode(file_get_contents($path), true);
}

/**
 * Find a variant by its AliExpress sku id within a NormalizedProduct.
 */
function variantBySkuId(NormalizedProduct $product, string $skuId): NormalizedVariant
{
    foreach ($product->variants as $variant) {
        if ($variant->skuId === $skuId) {
            return $variant;
        }
    }

    throw new RuntimeException("Variant {$skuId} not found");
}

beforeEach(function () {
    $this->mapper = new AliExpressProductMapper;
});

it('maps textual content and SEO fields for the configurable fixture', function () {
    $product = $this->mapper->map(loadMapperFixture('ds_product_get_configurable.json'), '1005001111111111');

    expect($product)->toBeInstanceOf(NormalizedProduct::class)
        ->and($product->aliexpressProductId)->toBe('1005001111111111')
        ->and($product->title)->toBe('Wireless Bluetooth Headphones Premium')
        ->and($product->description)->toContain('active noise cancelling')
        ->and($product->shortDescription)->toBe('Premium wireless headphones with noise cancelling')
        ->and($product->metaTitle)->toBe('Wireless Bluetooth Headphones Premium')
        ->and($product->metaKeywords)->toBe('headphones, wireless, bluetooth')
        ->and($product->metaDescription)->toBe('Buy premium wireless bluetooth headphones with noise cancelling.')
        ->and($product->currency)->toBe('USD');
});

it('falls back to the title for meta_title when absent', function () {
    $product = $this->mapper->map(loadMapperFixture('ds_product_get_simple.json'), '1005002222222222');

    expect($product->metaTitle)->toBe($product->title)
        ->and($product->metaTitle)->toBe('Stainless Steel Water Bottle 500ml');
});

it('derives a short description from the description when absent', function () {
    $product = $this->mapper->map(loadMapperFixture('ds_product_get_simple.json'), '1005002222222222');

    // The simple fixture has no short_description; it is derived from the
    // stripped description text (Req 7.2).
    expect($product->shortDescription)->toBe('Insulated stainless steel water bottle keeps drinks cold for 24 hours.')
        ->and($product->shortDescription)->not->toContain('<strong>');
});

it('resolves two distinct configurable axes with prefixed codes', function () {
    $product = $this->mapper->map(loadMapperFixture('ds_product_get_configurable.json'), '1005001111111111');

    expect($product->axes)->toHaveCount(2);

    $axesByName = collect($product->axes)->keyBy('name');

    expect($axesByName)->toHaveKeys(['Color', 'Size']);

    $color = $axesByName->get('Color');
    $size = $axesByName->get('Size');

    expect($color)->toBeInstanceOf(NormalizedVariantAxis::class)
        ->and($color->code)->toBe('ae_color')
        ->and($color->values)->toBe(['Red', 'Blue'])
        ->and($size->code)->toBe('ae_size')
        ->and($size->values)->toBe(['S', 'M']);
});

it('maps one variant per SKU with price, stock, options, and images', function () {
    $product = $this->mapper->map(loadMapperFixture('ds_product_get_configurable.json'), '1005001111111111');

    expect($product->variants)->toHaveCount(4);

    $redSmall = variantBySkuId($product, 'sku-red-s');

    expect($redSmall->price)->toBe(29.99)
        ->and($redSmall->stock)->toBe(10)
        ->and($redSmall->optionsByAxis)->toBe(['Color' => 'Red', 'Size' => 'S'])
        ->and($redSmall->imageUrls)->toBe(['https://img.example.com/red.jpg']);

    $blueMedium = variantBySkuId($product, 'sku-blue-m');

    expect($blueMedium->price)->toBe(32.00)
        ->and($blueMedium->stock)->toBe(8)
        ->and($blueMedium->optionsByAxis)->toBe(['Color' => 'Blue', 'Size' => 'M'])
        ->and($blueMedium->imageUrls)->toBe(['https://img.example.com/blue.jpg']);
});

it('parses the gallery image urls from the semicolon-separated string and de-duplicates', function () {
    $product = $this->mapper->map(loadMapperFixture('ds_product_get_configurable.json'), '1005001111111111');

    expect($product->imageUrls)->toBe([
        'https://img.example.com/g1.jpg',
        'https://img.example.com/g2.jpg',
        'https://img.example.com/g3.jpg',
    ]);
});

it('flags the multi-SKU multi-axis fixture as configurable', function () {
    $product = $this->mapper->map(loadMapperFixture('ds_product_get_configurable.json'), '1005001111111111');

    expect($product->isConfigurable)->toBeTrue();
});

it('maps the single-SKU fixture as a simple product', function () {
    $product = $this->mapper->map(loadMapperFixture('ds_product_get_simple.json'), '1005002222222222');

    expect($product->isConfigurable)->toBeFalse()
        ->and($product->axes)->toBe([])
        ->and($product->variants)->toHaveCount(1);

    $variant = $product->variants[0];

    expect($variant->skuId)->toBe('sku-bottle-default')
        ->and($variant->price)->toBe(12.75)
        ->and($variant->stock)->toBe(42)
        ->and($variant->optionsByAxis)->toBe([])
        ->and($variant->imageUrls)->toBe([]);
});

it('parses gallery images for the simple fixture', function () {
    $product = $this->mapper->map(loadMapperFixture('ds_product_get_simple.json'), '1005002222222222');

    expect($product->imageUrls)->toBe([
        'https://img.example.com/bottle1.jpg',
        'https://img.example.com/bottle2.jpg',
    ]);
});

it('throws when the body carries no product base info', function () {
    $this->mapper->map([], '1005003333333333');
})->throws(AliExpressImportException::class);

it('throws when the result has no base info node', function () {
    $this->mapper->map(['result' => ['ae_multimedia_info_dto' => ['image_urls' => 'x.jpg']]], '1005003333333333');
})->throws(AliExpressImportException::class);

/*
|--------------------------------------------------------------------------
| Video import + discount mapping (real-payload shapes)
|--------------------------------------------------------------------------
|
| The live ds.product.get response wraps everything in an
| "aliexpress_ds_product_get_response" envelope, nests videos under
| ae_multimedia_info_dto.ae_video_dtos.ae_video_d_t_o[], and exposes both a
| sale price (offer_sale_price) and a higher list price (sku_price) when the
| product is discounted. These tests assert the mapper handles all three.
|
*/

it('extracts approved video urls from the wrapped multimedia node', function () {
    $body = [
        'aliexpress_ds_product_get_response' => [
            'result' => [
                'ae_item_base_info_dto' => [
                    'subject' => 'Product With Video',
                    'detail' => '<p>desc</p>',
                ],
                'ae_multimedia_info_dto' => [
                    'image_urls' => 'https://img.example.com/a.jpg',
                    'ae_video_dtos' => [
                        'ae_video_d_t_o' => [
                            [
                                'media_status' => 'approved',
                                'media_type' => 'video',
                                'media_url' => 'https://video.aliexpress-media.com/play/123.mp4',
                            ],
                            [
                                'media_status' => 'pending',
                                'media_type' => 'video',
                                'media_url' => 'https://video.aliexpress-media.com/play/456.mp4',
                            ],
                        ],
                    ],
                ],
                'ae_item_sku_info_dtos' => [
                    'ae_item_sku_info_d_t_o' => [
                        'sku_id' => 's1',
                        'offer_sale_price' => '10.00',
                        'sku_available_stock' => 5,
                    ],
                ],
            ],
        ],
    ];

    $product = $this->mapper->map($body, '900000000001');

    // Only the approved video is imported; the pending one is skipped.
    expect($product->videoUrls)->toBe(['https://video.aliexpress-media.com/play/123.mp4']);
});

it('maps a discount: list price becomes original, sale price is the variant price', function () {
    $body = [
        'aliexpress_ds_product_get_response' => [
            'result' => [
                'ae_item_base_info_dto' => [
                    'subject' => 'Discounted Product',
                    'detail' => '<p>desc</p>',
                ],
                'ae_multimedia_info_dto' => [
                    'image_urls' => 'https://img.example.com/a.jpg',
                ],
                'ae_item_sku_info_dtos' => [
                    'ae_item_sku_info_d_t_o' => [
                        'sku_id' => 's1',
                        'offer_sale_price' => '11.19',
                        'sku_price' => '33.92',
                        'sku_available_stock' => 7,
                    ],
                ],
            ],
        ],
    ];

    $product = $this->mapper->map($body, '900000000002');
    $variant = $product->variants[0];

    expect($variant->price)->toBe(11.19)
        ->and($variant->originalPrice)->toBe(33.92);
});

it('leaves originalPrice null when there is no discount', function () {
    $body = [
        'aliexpress_ds_product_get_response' => [
            'result' => [
                'ae_item_base_info_dto' => [
                    'subject' => 'Full Price Product',
                    'detail' => '<p>desc</p>',
                ],
                'ae_multimedia_info_dto' => [
                    'image_urls' => 'https://img.example.com/a.jpg',
                ],
                'ae_item_sku_info_dtos' => [
                    'ae_item_sku_info_d_t_o' => [
                        'sku_id' => 's1',
                        'offer_sale_price' => '20.00',
                        'sku_price' => '20.00',
                        'sku_available_stock' => 3,
                    ],
                ],
            ],
        ],
    ];

    $product = $this->mapper->map($body, '900000000003');

    expect($product->variants[0]->originalPrice)->toBeNull();
});

it('returns no videos when the payload has none', function () {
    $product = $this->mapper->map(loadMapperFixture('ds_product_get_simple.json'), '900000000004');

    expect($product->videoUrls)->toBe([]);
});
