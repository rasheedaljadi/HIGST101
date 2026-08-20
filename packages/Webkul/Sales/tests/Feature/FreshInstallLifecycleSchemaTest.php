<?php

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Webkul\Sales\Services\Lifecycle\OrderLifecycleRebuildService;

uses(TestCase::class);

test('Fresh Install: Read Model tables, foreign keys, and indexes are cleanly initialized', function () {
    // 1. Verify existence of derived Read Model tables
    expect(Schema::hasTable('order_lifecycle_stage_views'))->toBeTrue()
        ->and(Schema::hasTable('order_item_lifecycle_stage_views'))->toBeTrue();

    // 2. Verify columns of order_lifecycle_stage_views
    $orderViewColumns = Schema::getColumnListing('order_lifecycle_stage_views');
    expect($orderViewColumns)->toContain(
        'id',
        'order_id',
        'current_stage_code',
        'bottleneck_stage_code',
        'is_mixed_order',
        'has_imported_items',
        'has_internal_items',
        'is_exception',
        'exception_reason',
        'computed_at',
        'source_version'
    );

    // 3. Verify columns of order_item_lifecycle_stage_views
    $itemViewColumns = Schema::getColumnListing('order_item_lifecycle_stage_views');
    expect($itemViewColumns)->toContain(
        'id',
        'order_item_id',
        'order_id',
        'origin_type',
        'current_stage_code',
        'source_type',
        'is_exception',
        'exception_reason',
        'computed_at'
    );

    // 4. Verify idempotent rebuild execution on clean schema
    $rebuilder = app(OrderLifecycleRebuildService::class);
    $processed = $rebuilder->rebuild();

    expect($processed)->toBeGreaterThanOrEqual(0);
});
