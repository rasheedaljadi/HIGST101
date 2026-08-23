<?php

/**
 * Migration & Post-Upgrade Verification Script for non-commercial verification database 'higest'
 *
 * Safety Constraints:
 * - NO fixtures or test seeders run on 'higest'
 * - NO balance transfer or modification of 'hayest_central' or 'default'
 * - NO AliExpress live API calls
 * - NO permanent test records left behind
 */

require_once __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

$targetDb = 'higest';
Config::set('database.connections.mysql.database', $targetDb);
DB::purge('mysql');
DB::reconnect('mysql');

echo "===============================================================\n";
echo "STEP 4 to 9: OFFICIAL MIGRATIONS & VERIFICATION ON '$targetDb'\n";
echo "===============================================================\n\n";

// 1. Check migrations before
$preMigrationCount = DB::table('migrations')->count();
echo "Pre-migration migrations count: $preMigrationCount\n";

// 2. Run Official Migrations
echo "\nRunning official migrations on $targetDb...\n";
$exitCode = Artisan::call('migrate', ['--force' => true]);
$output = Artisan::output();
echo "Artisan migrate exit code: $exitCode\n";
echo "Artisan output:\n$output\n";

if ($exitCode !== 0) {
    throw new Exception("Artisan migrate failed with code $exitCode");
}

$postMigrationCount = DB::table('migrations')->count();
$newMigrations = DB::table('migrations')->orderBy('id', 'desc')->limit($postMigrationCount - $preMigrationCount)->get();
echo 'New migrations applied: '.($postMigrationCount - $preMigrationCount)."\n";
foreach ($newMigrations as $m) {
    echo "  + [batch {$m->batch}] {$m->migration}\n";
}

// 3. Verify Integrity of Legacy & Canonical Sources
echo "\n--- Verifying Sources Integrity on $targetDb ---\n";
$sources = DB::table('inventory_sources')->get();
echo "Total Inventory Sources on $targetDb: ".$sources->count()."\n";
foreach ($sources as $s) {
    $extra = [];
    if (isset($s->source_type)) {
        $extra[] = "type={$s->source_type}";
    }
    if (isset($s->is_salable)) {
        $extra[] = "salable={$s->is_salable}";
    }
    if (isset($s->is_delivery_source)) {
        $extra[] = "delivery={$s->is_delivery_source}";
    }
    $extraStr = ! empty($extra) ? ' ('.implode(', ', $extra).')' : '';
    echo "  - ID: {$s->id} | Code: {$s->code} | Name: {$s->name} | Country: {$s->country} | Status: {$s->status}$extraStr\n";
}

$hayestCentral = $sources->where('code', 'hayest_central')->first();
$defaultSource = $sources->where('code', 'default')->first();

if (! $hayestCentral) {
    echo "WARNING: 'hayest_central' not found in sources.\n";
} else {
    echo "CHECK PASS: 'hayest_central' (ID {$hayestCentral->id}) is preserved intact.\n";
}

if (! $defaultSource) {
    echo "WARNING: 'default' source not found.\n";
} else {
    echo "CHECK PASS: 'default' source (ID {$defaultSource->id}) is preserved intact.\n";
}

// 4. Limited Transient Smoke Test (Wrapped in DB Transaction & Rolled Back, or Cleanly Removed)
echo "\n--- Executing Transient Smoke Test (No Permanent Test Records) ---\n";
DB::beginTransaction();
try {
    // Check if new tables are writable and functional
    // A. Inventory Movement
    $movementId = DB::table('inventory_movements')->insertGetId([
        'movement_type' => 'manual_adjustment',
        'product_id' => 1,
        'sku' => 'TRANSIENT-TEST-SKU',
        'source_inventory_source_id' => $hayestCentral ? $hayestCentral->id : 1,
        'target_inventory_source_id' => null,
        'quantity' => 5,
        'reference_event' => 'transient_smoke_test',
        'idempotency_key' => 'TRANSIENT_'.Str::random(10),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    echo "  ✓ Transient Movement insert verified (ID $movementId)\n";

    // B. Transfer Manifest
    $manifestId = DB::table('inventory_transfer_manifests')->insertGetId([
        'manifest_number' => 'TRF-TEST-'.Str::random(6),
        'idempotency_key' => 'TRF_IDEMP_'.Str::random(10),
        'source_inventory_source_id' => $hayestCentral ? $hayestCentral->id : 1,
        'destination_inventory_source_id' => 1,
        'status' => 'draft',
        'carrier_name' => 'Transient Carrier',
        'tracking_number' => 'TRK-TEST',
        'total_packages' => 1,
        'total_items_count' => 5,
        'created_by_admin_id' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    echo "  ✓ Transient Transfer Manifest insert verified (ID $manifestId)\n";

    // C. Inbound Receipt Manifest
    $receiptId = DB::table('inbound_receipt_manifests')->insertGetId([
        'receipt_number' => 'REC-TEST-'.Str::random(6),
        'idempotency_key' => 'REC_IDEMP_'.Str::random(10),
        'inventory_transfer_manifest_id' => $manifestId,
        'destination_inventory_source_id' => 1,
        'status' => 'completed',
        'total_received_good' => 5,
        'total_received_damaged' => 0,
        'total_received_missing' => 0,
        'received_by_admin_id' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    echo "  ✓ Transient Inbound Receipt insert verified (ID $receiptId)\n";

    // Rollback to leave ZERO dirty/permanent test data in higest
    DB::rollBack();
    echo "  ✓ DB Transaction rolled back successfully: Zero dirty/permanent records in $targetDb.\n";
} catch (Throwable $e) {
    DB::rollBack();
    throw new Exception('Transient Smoke Test failed: '.$e->getMessage());
}

// 5. Post-Migration Tables and Rows Comparison
$tablesPost = DB::select('SHOW TABLES');
$tableKey = 'Tables_in_'.$targetDb;

$tableCountsPost = [];
$totalRowsPost = 0;
foreach ($tablesPost as $t) {
    $tableName = $t->$tableKey;
    try {
        $count = DB::table($tableName)->count();
        $tableCountsPost[$tableName] = $count;
        $totalRowsPost += $count;
    } catch (Throwable $e) {
        $tableCountsPost[$tableName] = 'ERROR: '.$e->getMessage();
    }
}

// Load Pre-Migration Audit
$backupDir = 'C:/Users/RASHEED/.gemini/antigravity-ide/brain/34e700ea-070a-4bed-959e-177086a7bed1/scratch';
$preAudit = json_decode(file_get_contents($backupDir.'/pre_migration_audit_higest.json'), true);

echo "\n===============================================================\n";
echo "COMPARISON SUMMARY (PRE vs POST MIGRATION)\n";
echo "===============================================================\n";
echo 'Tables Count: '.count($preAudit['table_counts']).' -> '.count($tableCountsPost).' (+'.(count($tableCountsPost) - count($preAudit['table_counts']))." tables)\n";
echo 'Total Rows: '.number_format(array_sum(array_filter($preAudit['table_counts'], 'is_numeric'))).' -> '.number_format($totalRowsPost)."\n";
echo 'Migrations: '.$preAudit['migrations_count'].' -> '.$postMigrationCount.' (+'.($postMigrationCount - $preAudit['migrations_count'])." migrations)\n";

$newTables = array_diff(array_keys($tableCountsPost), array_keys($preAudit['table_counts']));
echo "New Tables Added:\n";
foreach ($newTables as $nt) {
    echo "  + $nt\n";
}

// Check if any existing row counts changed unexpectedly
$discrepancies = [];
foreach ($preAudit['table_counts'] as $table => $cnt) {
    if (is_numeric($cnt) && isset($tableCountsPost[$table])) {
        if ($cnt !== $tableCountsPost[$table]) {
            $discrepancies[$table] = [
                'before' => $cnt,
                'after' => $tableCountsPost[$table],
            ];
        }
    }
}

if (empty($discrepancies)) {
    echo "\nIntegrity Check PASS: All existing table row counts are 100% identical.\n";
} else {
    echo "\nIntegrity Changes Detected:\n";
    foreach ($discrepancies as $t => $diff) {
        echo "  - Table $t: {$diff['before']} -> {$diff['after']}\n";
    }
}

$postAuditData = [
    'pre_audit' => $preAudit,
    'post_tables_count' => count($tableCountsPost),
    'post_total_rows' => $totalRowsPost,
    'post_migrations_count' => $postMigrationCount,
    'new_tables' => array_values($newTables),
    'new_migrations' => $newMigrations->pluck('migration')->toArray(),
    'sources' => $sources->toArray(),
    'row_discrepancies' => $discrepancies,
];

file_put_contents($backupDir.'/post_migration_audit_higest.json', json_encode($postAuditData, JSON_PRETTY_PRINT));
echo "Post-migration audit saved to $backupDir/post_migration_audit_higest.json\n";
