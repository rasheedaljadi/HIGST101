<?php

namespace Webkul\Shop\Tests\Feature;

use Webkul\Product\Models\Product;
use Webkul\Shop\Tests\ShopTestCase;

class PDPMobileExperienceTest extends ShopTestCase
{
    public function test_pdp_renders_mobile_sticky_bar_component(): void
    {
        $product = Product::factory()->create([
            'type' => 'simple',
            'status' => 1,
            'visible_individually' => 1,
        ]);

        $response = $this->get(route('shop.product_or_category.index', $product->url_key));

        $response->assertStatus(200);
        $response->assertSee('v-mobile-sticky-bar');
        $response->assertSee('primary-pdp-cta-container');
    }

    public function test_mobile_sticky_bar_renders_for_configurable_products(): void
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
}
