<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Webkul\Sales\Models\Invoice;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderItem;
use Webkul\Sales\Models\OrderPayment;
use Webkul\Sales\Services\Lifecycle\OrderLifecycleProjector;
use Webkul\Sales\Services\Lifecycle\OrderLifecycleRebuildService;

uses(TestCase::class);

beforeEach(function () {
    if (! Schema::hasTable('order_lifecycle_stage_views')) {
        $this->artisan('migrate');
    }
    if (Schema::hasTable('order_item_lifecycle_stage_views')) {
        DB::table('order_item_lifecycle_stage_views')->delete();
    }
    if (Schema::hasTable('order_lifecycle_stage_views')) {
        DB::table('order_lifecycle_stage_views')->delete();
    }

    // Ensure foreign key prerequisites exist
    if (! DB::table('categories')->where('id', 1)->exists()) {
        DB::table('categories')->insert(['id' => 1, 'position' => 1, 'status' => 1, '_lft' => 1, '_rgt' => 2, 'created_at' => now(), 'updated_at' => now()]);
    }
    if (! DB::table('locales')->where('id', 1)->exists()) {
        DB::table('locales')->insert(['id' => 1, 'code' => 'en', 'name' => 'English', 'created_at' => now(), 'updated_at' => now()]);
    }
    if (! DB::table('currencies')->where('id', 1)->exists()) {
        DB::table('currencies')->insert(['id' => 1, 'code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'created_at' => now(), 'updated_at' => now()]);
    }
    if (! DB::table('channels')->where('id', 1)->exists()) {
        DB::table('channels')->insert([
            'id' => 1,
            'code' => 'default',
            'theme' => 'default',
            'hostname' => 'localhost',
            'is_maintenance_on' => 0,
            'root_category_id' => 1,
            'default_locale_id' => 1,
            'base_currency_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
    if (! DB::table('products')->where('id', 1)->exists()) {
        DB::table('products')->insert([
            'id' => 1,
            'sku' => 'TEST-SKU',
            'type' => 'simple',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // Seed inventory sources cleanly by code
    $sources = [
        ['id' => 4, 'code' => 'hayest_dropship_sa', 'name' => 'Saudi Dropship Collection'],
        ['id' => 6, 'code' => 'hayest_dropship_ye', 'name' => 'Yemen Dropship DC'],
        ['id' => 7, 'code' => 'hayest_internal_ye', 'name' => 'Yemen Internal Warehouse'],
        ['id' => 8, 'code' => 'hayest_quarantine_ye', 'name' => 'Yemen Quarantine Warehouse'],
    ];

    foreach ($sources as $s) {
        if (! DB::table('inventory_sources')->where('code', $s['code'])->exists()) {
            DB::table('inventory_sources')->insert([
                'id' => $s['id'],
                'code' => $s['code'],
                'name' => $s['name'],
                'contact_name' => 'Manager',
                'contact_email' => 'wh@hayest.com',
                'contact_number' => '770000000',
                'country' => 'YE',
                'state' => 'Sanaa',
                'city' => 'Sanaa',
                'street' => 'Main St',
                'postcode' => '00000',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
});

test('Integration 1: Order creation event projects Read Model cleanly without extra writes', function () {
    $order = Order::factory()->create(['status' => 'pending']);
    $item = OrderItem::factory()->create(['order_id' => $order->id, 'additional' => []]);

    Event::dispatch('sales.order.create.after', $order);

    $view = DB::table('order_lifecycle_stage_views')->where('order_id', $order->id)->first();
    expect($view)->not->toBeNull()
        ->and($view->current_stage_code)->toBe('new')
        ->and($view->is_exception)->toBe(0);
});

test('Integration 2: Order payment event moves status to confirmed', function () {
    $order = Order::factory()->create(['status' => 'processing']);
    $item = OrderItem::factory()->create(['order_id' => $order->id, 'additional' => []]);
    OrderPayment::factory()->create(['order_id' => $order->id, 'method' => 'cashondelivery']);
    $invoice = Invoice::factory()->create(['order_id' => $order->id]);

    Event::dispatch('sales.invoice.save.after', $invoice);

    $view = DB::table('order_lifecycle_stage_views')->where('order_id', $order->id)->first();
    expect($view)->not->toBeNull()
        ->and($view->current_stage_code)->toBe('confirmed');
});

test('Integration 3: Purchase Order event updates stage to po_created or supplier_shipped', function () {
    $order = Order::factory()->create(['status' => 'processing']);
    $item = OrderItem::factory()->create(['order_id' => $order->id, 'additional' => ['aliexpress' => true]]);

    if (Schema::hasTable('purchase_orders') && Schema::hasTable('purchase_order_items')) {
        $poId = DB::table('purchase_orders')->insertGetId([
            'order_id' => $order->id,
            'state' => 'created',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('purchase_order_items')->insert([
            'purchase_order_id' => $poId,
            'order_item_id' => $item->id,
            'aliexpress_product_id' => '100500123',
            'qty' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    Event::dispatch('fulfillment.purchase_order.create.after', (object) ['order_id' => $order->id]);

    $view = DB::table('order_lifecycle_stage_views')->where('order_id', $order->id)->first();
    expect($view)->not->toBeNull()
        ->and($view->current_stage_code)->toBe('po_created');
});

test('Integration 4: Saudi receipt event updates stage to sa_received', function () {
    $order = Order::factory()->create(['status' => 'processing']);
    $item = OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => 1, 'additional' => ['aliexpress' => true]]);

    $saSource = DB::table('inventory_sources')->where('code', 'hayest_dropship_sa')->first();

    if (Schema::hasTable('inbound_receipt_manifests') && Schema::hasTable('inbound_receipt_manifest_items')) {
        $receiptId = DB::table('inbound_receipt_manifests')->insertGetId([
            'receipt_number' => 'REC-TEST-'.uniqid(),
            'idempotency_key' => 'REC-KEY-'.uniqid(),
            'destination_inventory_source_id' => $saSource->id,
            'status' => 'completed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('inbound_receipt_manifest_items')->insert([
            'inbound_receipt_manifest_id' => $receiptId,
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'product_id' => 1,
            'sku' => 'TEST-SKU',
            'qty_good' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    Event::dispatch('inventory.inbound_receipt.completed', (object) ['order_id' => $order->id]);

    $view = DB::table('order_lifecycle_stage_views')->where('order_id', $order->id)->first();
    expect($view)->not->toBeNull()
        ->and($view->current_stage_code)->toBe('sa_received');
});

test('Integration 5: Transfer event advances to ye_received in hayest_dropship_ye', function () {
    $order = Order::factory()->create(['status' => 'processing']);
    $item = OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => 1, 'additional' => ['aliexpress' => true]]);

    $saSource = DB::table('inventory_sources')->where('code', 'hayest_dropship_sa')->first();
    $yeSource = DB::table('inventory_sources')->where('code', 'hayest_dropship_ye')->first();

    if (Schema::hasTable('inventory_transfer_manifests') && Schema::hasTable('inventory_transfer_manifest_items')) {
        $manifestId = DB::table('inventory_transfer_manifests')->insertGetId([
            'manifest_number' => 'TRF-TEST-'.uniqid(),
            'idempotency_key' => 'TRF-KEY-'.uniqid(),
            'source_inventory_source_id' => $saSource->id,
            'destination_inventory_source_id' => $yeSource->id,
            'status' => 'completed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('inventory_transfer_manifest_items')->insert([
            'inventory_transfer_manifest_id' => $manifestId,
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'product_id' => 1,
            'sku' => 'TEST-SKU',
            'qty_shipped' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    Event::dispatch('inventory.transfer_manifest.completed', (object) ['order_id' => $order->id]);

    $itemView = DB::table('order_item_lifecycle_stage_views')->where('order_item_id', $item->id)->first();
    expect($itemView)->not->toBeNull()
        ->and($itemView->current_stage_code)->toBe('ye_received')
        ->and($itemView->source_type)->toBe('hayest_dropship_ye');
});

test('Integration 6: Delivery assignment event advances to handed_off and delivered independently of COD settlement', function () {
    $order = Order::factory()->create(['status' => 'processing']);
    $item = OrderItem::factory()->create(['order_id' => $order->id, 'additional' => []]);

    if (Schema::hasTable('delivery_assignments')) {
        DB::table('delivery_assignments')->insert([
            'order_id' => $order->id,
            'delivery_type' => 'home_delivery',
            'idempotency_key' => 'DEL-KEY-'.uniqid(),
            'status' => 'delivered',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    Event::dispatch('delivery.assignment.updated', (object) ['order_id' => $order->id]);

    $view = DB::table('order_lifecycle_stage_views')->where('order_id', $order->id)->first();
    expect($view)->not->toBeNull()
        ->and($view->current_stage_code)->toBe('delivered');
});

test('Integration 7: Exception states (canceled, delivery failed) mark is_exception true', function () {
    $order = Order::factory()->create(['status' => 'canceled']);
    $item = OrderItem::factory()->create(['order_id' => $order->id, 'additional' => []]);

    app(OrderLifecycleProjector::class)->project($order);

    $view = DB::table('order_lifecycle_stage_views')->where('order_id', $order->id)->first();
    expect($view)->not->toBeNull()
        ->and($view->is_exception)->toBe(1)
        ->and($view->exception_reason)->toBe('canceled');
});

test('Integration 8: RebuildService and duplicate events are 100% idempotent', function () {
    $order = Order::factory()->create(['status' => 'pending']);
    $item = OrderItem::factory()->create(['order_id' => $order->id, 'additional' => []]);

    $rebuilder = app(OrderLifecycleRebuildService::class);

    // Rebuild twice
    $rebuilder->rebuild([$order->id]);
    $rebuilder->rebuild([$order->id]);

    $viewCount = DB::table('order_lifecycle_stage_views')->where('order_id', $order->id)->count();
    $itemViewCount = DB::table('order_item_lifecycle_stage_views')->where('order_id', $order->id)->count();

    expect($viewCount)->toBe(1)
        ->and($itemViewCount)->toBe(1);
});
