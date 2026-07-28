<?php

namespace Webkul\Shop\Tests\Feature;

use Webkul\Product\Models\Product;
use Webkul\Shop\Tests\ShopTestCase;

class PDPSEOAnalyticsTest extends ShopTestCase
{
    public function test_pdp_renders_json_ld_product_and_offer_schemas(): void
    {
        $product = Product::factory()->create([
            'type' => 'simple',
            'status' => 1,
            'visible_individually' => 1,
        ]);

        $response = $this->get(route('shop.product_or_category.index', $product->url_key));

        $response->assertStatus(200);
        $response->assertSee('application/ld+json');
        $response->assertSee('https://schema.org/');
        $response->assertSee('"@type": "Product"', false);
        $response->assertSee('"@type": "Offer"', false);
        $response->assertSee('"@type": "BreadcrumbList"', false);
    }

    public function test_pdp_renders_lcp_image_preload_link(): void
    {
        $product = Product::factory()->create([
            'type' => 'simple',
            'status' => 1,
            'visible_individually' => 1,
        ]);

        $response = $this->get(route('shop.product_or_category.index', $product->url_key));

        $response->assertStatus(200);
        $response->assertSee('rel="preload" as="image"', false);
        $response->assertSee('fetchpriority="high"', false);
    }
}
