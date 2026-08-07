<?php

use Webkul\Admin\Tests\AdminTestCase;
use Webkul\Product\Models\Product;

uses(AdminTestCase::class);

it('prevents unauthorized access to flash deals admin routes', function () {
    $this->get(route('admin.marketing.promotions.flash_deals.index'))
        ->assertRedirect();
});

it('allows an authorized admin to access flash deals listing', function () {
    $this->loginAsAdmin();

    $this->get(route('admin.marketing.promotions.flash_deals.index'))
        ->assertOk();
});

it('allows an authorized admin to create a flash deal with products', function () {
    $this->loginAsAdmin();

    $product = Product::factory()->create();

    $data = [
        'title' => 'Black Friday Flash Deal',
        'status' => 1,
        'starts_at' => now()->format('Y-m-d H:i:s'),
        'ends_at' => now()->addHours(12)->format('Y-m-d H:i:s'),
        'products' => [
            [
                'product_id' => $product->id,
                'flash_price' => 49.99,
                'allocation_qty' => 100,
            ],
        ],
    ];

    $response = $this->post(route('admin.marketing.promotions.flash_deals.store'), $data);

    $response->assertRedirect(route('admin.marketing.promotions.flash_deals.index'));

    $this->assertDatabaseHas('flash_deals', [
        'title' => 'Black Friday Flash Deal',
        'status' => 1,
    ]);

    $this->assertDatabaseHas('flash_deal_products', [
        'product_id' => $product->id,
        'flash_price' => 49.99,
        'allocation_qty' => 100,
        'sold_qty' => 0,
    ]);
});

it('validates that ends_at must be after starts_at', function () {
    $this->loginAsAdmin();

    $product = Product::factory()->create();

    $data = [
        'title' => 'Invalid Date Deal',
        'status' => 1,
        'starts_at' => now()->addHours(10)->format('Y-m-d H:i:s'),
        'ends_at' => now()->addHours(2)->format('Y-m-d H:i:s'),
        'products' => [
            [
                'product_id' => $product->id,
                'flash_price' => 10.00,
                'allocation_qty' => 10,
            ],
        ],
    ];

    $response = $this->post(route('admin.marketing.promotions.flash_deals.store'), $data);

    $response->assertSessionHasErrors(['ends_at']);
});
