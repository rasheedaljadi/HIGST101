<?php

namespace Webkul\Shop\Tests\Feature;

use Webkul\Shop\Tests\ShopTestCase;
use Webkul\Shop\Transformers\ProductPDPTransformer;

class ProductPDPTransformerTest extends ShopTestCase
{
    public function test_transformer_returns_empty_array_for_null_product(): void
    {
        $transformer = app(ProductPDPTransformer::class);

        $result = $transformer->transform(null);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_transformer_includes_dropshipping_transparency_contract(): void
    {
        $transformer = app(ProductPDPTransformer::class);
        $product = \Webkul\Product\Models\Product::factory()->create([
            'type' => 'simple',
        ]);

        $result = $transformer->transform($product);

        $this->assertArrayHasKey('dropshipping', $result);
        $this->assertArrayHasKey('origin_country', $result['dropshipping']);
        $this->assertArrayHasKey('estimated_delivery_window', $result['dropshipping']);
        $this->assertArrayHasKey('tracking_available', $result['dropshipping']);
        $this->assertArrayHasKey('local_rma_days', $result['dropshipping']);
        $this->assertArrayHasKey('return_center_location', $result['dropshipping']);
        $this->assertTrue($result['dropshipping']['tracking_available']);
    }
}
