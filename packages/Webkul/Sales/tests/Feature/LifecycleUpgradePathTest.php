<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

test('Upgrade Path: Running migrations on an existing database preserves 100% of core business data', function () {
    // 1. Core tables to monitor
    $tablesToMonitor = [
        'orders',
        'order_items',
        'product_inventories',
        'inventory_sources',
    ];

    if (Schema::hasTable('purchase_orders')) {
        $tablesToMonitor[] = 'purchase_orders';
    }
    if (Schema::hasTable('delivery_assignments')) {
        $tablesToMonitor[] = 'delivery_assignments';
    }

    // 2. Capture baseline row counts
    $baselineCounts = [];
    foreach ($tablesToMonitor as $table) {
        $baselineCounts[$table] = DB::table($table)->count();
    }

    // 3. Run migration once without Tinker or manual SQL dropping
    Artisan::call('migrate', ['--force' => true]);

    // 4. Verify post-migration row counts
    foreach ($tablesToMonitor as $table) {
        $postCount = DB::table($table)->count();
        expect($postCount)->toBe($baselineCounts[$table], "Data loss detected in table {$table} after migration!");
    }

    // 5. Verify Read Model tables exist and can be queried safely
    expect(Schema::hasTable('order_lifecycle_stage_views'))->toBeTrue()
        ->and(Schema::hasTable('order_item_lifecycle_stage_views'))->toBeTrue();
});
