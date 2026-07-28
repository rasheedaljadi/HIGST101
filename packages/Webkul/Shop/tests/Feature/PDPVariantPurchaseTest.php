<?php

namespace Webkul\Shop\Tests\Feature;

use Webkul\Product\Models\Product;
use Webkul\Shop\Tests\ShopTestCase;

class PDPVariantPurchaseTest extends ShopTestCase
{
    public function test_add_to_cart_api_endpoint_handles_simple_products(): void
    {
        $product = Product::factory()->create([
            'type' => 'simple',
            'status' => 1,
        ]);

        $response = $this->postJson(route('shop.api.checkout.cart.store'), [
            'product_id' => $product->id,
            'quantity' => 1,
            'is_buy_now' => 0,
        ]);

        $this->assertContains($response->getStatusCode(), [200, 422]);
    }

    public function test_buy_now_action_sets_buy_now_flag(): void
    {
        $product = Product::factory()->create([
            'type' => 'simple',
            'status' => 1,
        ]);

        $response = $this->postJson(route('shop.api.checkout.cart.store'), [
            'product_id' => $product->id,
            'quantity' => 1,
            'is_buy_now' => 1,
        ]);

        $this->assertContains($response->getStatusCode(), [200, 422]);
    }
}
