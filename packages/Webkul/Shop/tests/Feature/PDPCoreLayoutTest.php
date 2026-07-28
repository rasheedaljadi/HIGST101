<?php

namespace Webkul\Shop\Tests\Feature;

use Webkul\Product\Models\Product;
use Webkul\Shop\Tests\ShopTestCase;

class PDPCoreLayoutTest extends ShopTestCase
{
    public function test_pdp_renders_stock_meter_and_dropshipping_transparency_components(): void
    {
        $product = Product::factory()->create([
            'type' => 'simple',
            'status' => 1,
            'visible_individually' => 1,
        ]);

        $response = $this->get(route('shop.product_or_category.index', $product->url_key));

        $response->assertStatus(200);
        $response->assertSee('Supplier Fulfillment & Dispatch Transparency');
        $response->assertSee('Item Origin');
        $response->assertSee('Estimated Delivery');
        $response->assertSee('Parcel Tracking');
        $response->assertSee('Return Policy');
    }

    public function test_pdp_renders_out_of_stock_indicator_when_qty_is_zero(): void
    {
        $product = Product::factory()->create([
            'type' => 'simple',
            'status' => 1,
            'visible_individually' => 1,
        ]);

        // Ensure 0 inventory
        $product->inventories()->delete();

        $response = $this->get(route('shop.product_or_category.index', $product->url_key));

        $response->assertStatus(200);
        $response->assertSee('Out of Stock');
    }
}
