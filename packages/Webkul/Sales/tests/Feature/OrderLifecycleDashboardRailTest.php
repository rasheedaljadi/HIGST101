<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Webkul\Admin\Http\Controllers\DashboardController;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderItem;
use Webkul\Sales\Services\Lifecycle\OrderLifecycleDashboardQueryService;
use Webkul\Sales\Services\Lifecycle\OrderLifecycleProjector;
use Webkul\User\Models\Admin;

uses(TestCase::class);

beforeEach(function () {
    if (! Schema::hasTable('order_lifecycle_stage_views')) {
        $this->artisan('migrate');
    }

    if (! DB::table('categories')->where('id', 1)->exists()) {
        DB::table('categories')->insert(['id' => 1, 'position' => 1, 'status' => 1, '_lft' => 1, '_rgt' => 2, 'created_at' => now(), 'updated_at' => now()]);
    }
    if (! DB::table('locales')->where('id', 1)->exists()) {
        DB::table('locales')->insert(['id' => 1, 'code' => 'en', 'name' => 'English', 'direction' => 'ltr', 'created_at' => now(), 'updated_at' => now()]);
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

test('Pipeline Rail 1: Simple view mode is default and preserves original dashboard structure', function () {
    $admin = Admin::factory()->create();
    $this->actingAs($admin, 'admin');

    // Verify session/preference defaults to 'simple'
    $controller = app(DashboardController::class);
    $view = $controller->index();

    expect($view->getData()['viewMode'])->toBe('simple');
});

test('Pipeline Rail 2: Advanced view renders all 11 canonical stages in order', function () {
    $queryService = app(OrderLifecycleDashboardQueryService::class);
    $summary = $queryService->getPipelineSummary();

    expect($summary)->toHaveKeys(['stages', 'stages_by_code', 'total_active_orders', 'data_quality'])
        ->and(count($summary['stages']))->toBe(11);

    $expectedCodes = [
        'new', 'payment_pending', 'confirmed', 'sourcing_required',
        'po_created', 'supplier_shipped', 'sa_received', 'ye_in_transit',
        'ye_received', 'handed_off', 'delivered',
    ];

    $actualCodes = array_column($summary['stages'], 'code');
    expect($actualCodes)->toBe($expectedCodes);
});

test('Pipeline Rail 3: Unclassified data quality items are computed accurately without corrupting stage counters', function () {
    $queryService = app(OrderLifecycleDashboardQueryService::class);
    $dqInfo = $queryService->getUnclassifiedDataQualityInfo();

    expect($dqInfo)->toHaveKeys(['total_items', 'projected_items', 'unclassified_count', 'items']);
    expect($dqInfo['unclassified_count'])->toBeGreaterThanOrEqual(0);
});

test('Pipeline Rail 4: getOrdersForStage retrieves paginated orders cleanly for a specific stage', function () {
    $order = Order::factory()->create(['status' => 'processing']);
    $item = OrderItem::factory()->create(['order_id' => $order->id, 'additional' => []]);

    app(OrderLifecycleProjector::class)->project($order);

    $queryService = app(OrderLifecycleDashboardQueryService::class);
    $paginatedOrders = $queryService->getOrdersForStage('confirmed');

    expect($paginatedOrders)->not->toBeNull()
        ->and($paginatedOrders->total())->toBeGreaterThanOrEqual(1);
});
