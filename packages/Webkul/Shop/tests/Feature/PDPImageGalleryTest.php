<?php

namespace Webkul\Shop\Tests\Feature;

use Webkul\Product\Models\Product;
use Webkul\Product\Models\ProductImage as ProductImageModel;
use Webkul\Product\ProductImage as ProductImageHelper;
use Webkul\Shop\Tests\ShopTestCase;

class PDPImageGalleryTest extends ShopTestCase
{
    public function test_existing_product_image_urls_contain_required_presets_and_fallback(): void
    {
        $productImageHelper = app(ProductImageHelper::class);
        $product = Product::factory()->create([
            'type' => 'simple',
        ]);

        $galleryImages = $productImageHelper->getGalleryImages($product);

        $this->assertIsArray($galleryImages);
        $this->assertNotEmpty($galleryImages);
        $firstImage = $galleryImages[0];

        $this->assertArrayHasKey('small_image_url', $firstImage);
        $this->assertArrayHasKey('medium_image_url', $firstImage);
        $this->assertArrayHasKey('large_image_url', $firstImage);
        $this->assertArrayHasKey('original_image_url', $firstImage);
        $this->assertArrayHasKey('fallback_url', $firstImage);

        $this->assertStringContainsString('/cache/small/', $firstImage['small_image_url']);
        $this->assertStringContainsString('/cache/medium/', $firstImage['medium_image_url']);
        $this->assertStringContainsString('/cache/large/', $firstImage['large_image_url']);
    }

    public function test_aliexpress_imported_product_image_fallback_handling(): void
    {
        $product = Product::factory()->create([
            'type' => 'simple',
            'sku' => 'ALIEXPRESS-TEST-SKU-1001',
        ]);

        $productImageHelper = app(ProductImageHelper::class);
        $galleryImages = $productImageHelper->getGalleryImages($product);

        $this->assertNotEmpty($galleryImages);
        $this->assertNotNull($galleryImages[0]['fallback_url']);
    }

    public function test_missing_cache_asset_returns_200_ok_fallback(): void
    {
        $response = $this->get('/cache/large/product/999999/non_existent_image.jpg');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('image/webp', $response->headers->get('Content-Type'));
    }

    public function test_missing_image_product_returns_placeholder_urls(): void
    {
        $product = Product::factory()->create([
            'type' => 'simple',
        ]);

        // Clear relationship images
        $product->setRelation('images', collect([]));

        $productImageHelper = app(ProductImageHelper::class);
        $baseImage = $productImageHelper->getProductBaseImage($product);

        $this->assertIsArray($baseImage);
        $this->assertStringContainsString('placeholder', strtolower($baseImage['large_image_url']));
    }
}
