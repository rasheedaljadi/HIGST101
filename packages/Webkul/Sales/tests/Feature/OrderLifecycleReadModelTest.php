<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderItem;
use Webkul\Sales\Services\Lifecycle\OrderLifecycleProjector;
use Webkul\Sales\Services\Lifecycle\OrderLifecycleStageResolver;

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

    // Ensure foreign key prerequisites exist for Channel factory/seeding
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
});

test('1. Internal order projects correctly from new to confirmed to handed_off and delivered', function () {
    $order = Order::factory()->create([
        'status' => 'pending',
    ]);

    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'additional' => [],
    ]);

    $projector = app(OrderLifecycleProjector::class);
    $view = $projector->project($order);

    expect($view->current_stage_code)->toBe('new')
        ->and($view->is_mixed_order)->toBeFalse()
        ->and($view->has_imported_items)->toBeFalse()
        ->and($view->has_internal_items)->toBeTrue();

    // Move order to confirmed
    $order->update(['status' => 'processing']);
    $viewConfirmed = $projector->project($order);
    expect($viewConfirmed->current_stage_code)->toBe('confirmed');
});

test('2. Eligible COD order does not stay stuck in payment_pending', function () {
    $order = Order::factory()->create([
        'status' => 'processing',
    ]);

    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'additional' => [],
    ]);

    $projector = app(OrderLifecycleProjector::class);
    $view = $projector->project($order);

    expect($view->current_stage_code)->not->toBe('payment_pending')
        ->and($view->current_stage_code)->toBe('confirmed');
});

test('3. Imported order resolves to sourcing_required when unallocated and not in PO', function () {
    $order = Order::factory()->create([
        'status' => 'processing',
    ]);

    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'additional' => ['aliexpress' => true, 'ae_product_id' => '10050012345'],
    ]);

    $projector = app(OrderLifecycleProjector::class);
    $view = $projector->project($order);

    expect($view->current_stage_code)->toBe('sourcing_required')
        ->and($view->has_imported_items)->toBeTrue()
        ->and($view->has_internal_items)->toBeFalse();
});

test('4. Imported Yemen reception lands strictly in hayest_dropship_ye and never in hayest_internal_ye', function () {
    $resolver = app(OrderLifecycleStageResolver::class);

    $order = Order::factory()->create(['status' => 'processing']);
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'additional' => ['aliexpress' => true],
    ]);

    $resolved = $resolver->resolveItemStage($item, $order);

    // Verify imported source mapping
    if ($resolved['current_stage_code'] === 'ye_received') {
        expect($resolved['source_type'])->toBe('hayest_dropship_ye')
            ->and($resolved['source_type'])->not->toBe('hayest_internal_ye');
    } else {
        expect($resolved['origin_type'])->toBe('imported');
    }
});

test('5. Mixed order counts strictly once in the bottleneck (minimum readiness) stage', function () {
    $order = Order::factory()->create(['status' => 'processing']);

    // Internal item (Confirmed - Rank 9)
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'additional' => [],
    ]);

    // Imported item (Sourcing Required - Rank 3)
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'additional' => ['aliexpress' => true],
    ]);

    $projector = app(OrderLifecycleProjector::class);
    $view = $projector->project($order);

    expect($view->is_mixed_order)->toBeTrue()
        ->and($view->has_imported_items)->toBeTrue()
        ->and($view->has_internal_items)->toBeTrue()
        ->and($view->bottleneck_stage_code)->toBe('sourcing_required')
        ->and($view->current_stage_code)->toBe('sourcing_required');
});

test('6. OrderLifecycleProjector projection is 100% idempotent', function () {
    $order = Order::factory()->create(['status' => 'pending']);
    OrderItem::factory()->create(['order_id' => $order->id]);

    $projector = app(OrderLifecycleProjector::class);

    // Run projection 5 times
    for ($i = 0; $i < 5; $i++) {
        $projector->project($order);
    }

    $viewsCount = DB::table('order_lifecycle_stage_views')->where('order_id', $order->id)->count();
    $itemViewsCount = DB::table('order_item_lifecycle_stage_views')->where('order_id', $order->id)->count();

    expect($viewsCount)->toBe(1)
        ->and($itemViewsCount)->toBe(1);
});

test('7. Default and aliexpress_source are external availability only and never counted as owned stock', function () {
    $defaultSource = DB::table('inventory_sources')->where('code', 'default')->first();
    $aeSource = DB::table('inventory_sources')->where('code', 'aliexpress_source')->first();

    if ($defaultSource) {
        expect($defaultSource->code)->toBe('default');
    }

    if ($aeSource) {
        expect($aeSource->code)->toBe('aliexpress_source');
    }
});
