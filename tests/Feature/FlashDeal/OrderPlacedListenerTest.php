<?php

use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Webkul\FlashDeal\Models\FlashDeal;
use Webkul\FlashDeal\Models\FlashDealProduct;
use Webkul\Product\Models\Product;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderItem;

uses(TestCase::class);

it('increments sold_qty when an order containing a flash deal product is saved', function () {
    // 1. Create product and active flash deal
    $product = Product::factory()->create();

    $deal = FlashDeal::factory()->active()->create();

    $flashDealProduct = FlashDealProduct::factory()->create([
        'flash_deal_id' => $deal->id,
        'product_id' => $product->id,
        'allocation_qty' => 50,
        'sold_qty' => 0,
    ]);

    // 2. Create order with item
    $order = Order::factory()->create();

    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'qty_ordered' => 3,
    ]);

    $order->setRelation('items', collect([$orderItem]));

    // 3. Fire the event
    Event::dispatch('checkout.order.save.after', $order);

    // 4. Assert sold_qty incremented to 3
    expect($flashDealProduct->fresh()->sold_qty)->toBe(3);
});

it('handles non-flash deal products gracefully without errors', function () {
    $regularProduct = Product::factory()->create();

    $order = Order::factory()->create();

    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $regularProduct->id,
        'qty_ordered' => 5,
    ]);

    $order->setRelation('items', collect([$orderItem]));

    // Fire event and ensure no exceptions
    Event::dispatch('checkout.order.save.after', $order);

    expect(true)->toBeTrue();
});
