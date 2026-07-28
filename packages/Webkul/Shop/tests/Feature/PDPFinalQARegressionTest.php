<?php

namespace Webkul\Shop\Tests\Feature;

use Webkul\Product\Models\Product;
use Webkul\Shop\Tests\ShopTestCase;

class PDPFinalQARegressionTest extends ShopTestCase
{
    public function test_simple_product_full_pdp_rendering_and_flow(): void
    {
        $product = Product::factory()->create([
            'type' => 'simple',
            'status' => 1,
            'visible_individually' => 1,
        ]);

        $response = $this->get(route('shop.product_or_category.index', $product->url_key));

        $response->assertStatus(200);
        $response->assertSee('Supplier Fulfillment & Dispatch Transparency');
        $response->assertSee('v-mobile-sticky-bar');
        $response->assertSee('application/ld+json');
    }

    public function test_configurable_product_pdp_rendering(): void
    {
        $product = Product::factory()->create([
            'type' => 'configurable',
            'status' => 1,
            'visible_individually' => 1,
        ]);

        $response = $this->get(route('shop.product_or_category.index', $product->url_key));

        $response->assertStatus(200);
        $response->assertSee('Select Options');
    }

    public function test_aliexpress_imported_product_pdp_rendering(): void
    {
        $product = Product::factory()->create([
            'type' => 'simple',
            'sku' => 'ALIEXPRESS-QA-SKU-99001',
            'status' => 1,
            'visible_individually' => 1,
        ]);

        $response = $this->get(route('shop.product_or_category.index', $product->url_key));

        $response->assertStatus(200);
        $response->assertSee('International Overseas Warehouse (Express Freight)');
    }

    public function test_out_of_stock_product_pdp_rendering(): void
    {
        $product = Product::factory()->create([
            'type' => 'simple',
            'status' => 1,
            'visible_individually' => 1,
        ]);

        $product->inventories()->delete();

        $response = $this->get(route('shop.product_or_category.index', $product->url_key));

        $response->assertStatus(200);
        $response->assertSee('Out of Stock');
    }
}
