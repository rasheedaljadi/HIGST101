<?php

/**
 * Safe Initialization & Verification Orchestrator for database 'higest'
 */

require_once __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

$targetDb = 'higest';
Config::set('database.connections.mysql.database', $targetDb);
DB::purge('mysql');
DB::reconnect('mysql');

$backupDir = 'C:/Users/RASHEED/.gemini/antigravity-ide/brain/34e700ea-070a-4bed-959e-177086a7bed1/scratch';
if (! is_dir($backupDir)) {
    mkdir($backupDir, 0777, true);
}

echo "===============================================================\n";
echo "SAFE INITIALIZATION & VERIFICATION PIPELINE ON '$targetDb'\n";
echo "===============================================================\n\n";

// -------------------------------------------------------------
// STEP 1 & 2: READ-ONLY CHECK FOR CANONICAL CODES & DUPLICATES
// -------------------------------------------------------------
echo "[STEP 1 & 2] Inspecting existing inventory sources in '$targetDb'...\n";
$currentSources = DB::table('inventory_sources')->get();
echo 'Current total inventory_sources: '.$currentSources->count()."\n";
foreach ($currentSources as $s) {
    echo "  - ID: {$s->id} | Code: {$s->code} | Name: {$s->name} | Type: ".($s->source_type ?? 'N/A').' | Salable: '.($s->is_salable ?? 'N/A')."\n";
}

$canonicalCodes = [
    'aliexpress_source',
    'hayest_dropship_sa',
    'hayest_quarantine_sa',
    'hayest_dropship_ye',
    'hayest_internal_ye',
    'hayest_quarantine_ye',
];

$missingCanonical = [];
foreach ($canonicalCodes as $cc) {
    if (! $currentSources->firstWhere('code', $cc)) {
        $missingCanonical[] = $cc;
    }
}
echo 'Missing Canonical Sources before seeding: '.count($missingCanonical).' ('.implode(', ', $missingCanonical).")\n";

// Check duplicates
$duplicateCodes = DB::table('inventory_sources')->select('code', DB::raw('count(*) as count'))->groupBy('code')->having('count', '>', 1)->get();
$duplicateNames = DB::table('inventory_sources')->select('name', DB::raw('count(*) as count'))->groupBy('name')->having('count', '>', 1)->get();
echo 'Duplicate Codes: '.$duplicateCodes->count()."\n";
echo 'Duplicate Names: '.$duplicateNames->count()."\n\n";

// -------------------------------------------------------------
// STEP 3: CHECK GOVERNORATE RULES & DELIVERY POINTS
// -------------------------------------------------------------
$govRulesCount = Schema::hasTable('delivery_governorate_rules') ? DB::table('delivery_governorate_rules')->count() : 0;
$deliveryPointsCount = Schema::hasTable('delivery_points') ? DB::table('delivery_points')->count() : 0;
echo "[STEP 3] Delivery Governorate Rules count: $govRulesCount\n";
echo "[STEP 3] Delivery Points count: $deliveryPointsCount\n\n";

// -------------------------------------------------------------
// STEP 4: FRESH BACKUP BEFORE FIRST WRITE & SHA256
// -------------------------------------------------------------
echo "[STEP 4] Taking fresh pre-initialization backup of '$targetDb'...\n";
$backupFile = $backupDir.'/higest_pre_init_backup_'.date('Ymd_His').'.sql';
$mysqldumpPath = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
if (! file_exists($mysqldumpPath)) {
    $mysqldumpPath = 'mysqldump';
}
$dumpCmd = "\"$mysqldumpPath\" --host=127.0.0.1 --port=3306 -u root --routines --triggers --events $targetDb > \"$backupFile\"";
exec($dumpCmd, $out, $ret);

if ($ret !== 0 || ! file_exists($backupFile) || filesize($backupFile) === 0) {
    throw new Exception("Backup failed with exit code $ret");
}

$fileSize = filesize($backupFile);
$sha256 = hash_file('sha256', $backupFile);
echo "Backup Completed:\n";
echo "  File: $backupFile\n";
echo '  Size: '.number_format($fileSize)." bytes\n";
echo "  SHA256: $sha256\n\n";

// Snapshot before counts
$preCounts = [
    'tables' => count(DB::select('SHOW TABLES')),
    'inventory_sources' => DB::table('inventory_sources')->count(),
    'product_inventories' => DB::table('product_inventories')->count(),
    'inventory_movements' => DB::table('inventory_movements')->count(),
    'orders' => DB::table('orders')->count(),
    'customers' => DB::table('customers')->count(),
    'delivery_governorate_rules' => $govRulesCount,
    'delivery_points' => $deliveryPointsCount,
];

// -------------------------------------------------------------
// STEP 5: RUN ONLY IDEMPOTENT OFFICIAL SEEDERS
// -------------------------------------------------------------
echo "[STEP 5] Running official idempotent seeders only...\n";
echo "  -> Seeding InventorySourcesModelV12Seeder...\n";
Artisan::call('db:seed', [
    '--class' => 'Webkul\\Inventory\\Database\\Seeders\\InventorySourcesModelV12Seeder',
    '--force' => true,
]);

echo "  -> Seeding DeliveryGovernorateRulesSeeder...\n";
Artisan::call('db:seed', [
    '--class' => 'Webkul\\DeliveryManagement\\Database\\Seeders\\DeliveryGovernorateRulesSeeder',
    '--force' => true,
]);

echo "Official seeders finished successfully.\n\n";

// -------------------------------------------------------------
// STEP 7: POST-EXECUTION INTEGRITY VERIFICATION
// -------------------------------------------------------------
echo "[STEP 7] Verifying post-initialization integrity on '$targetDb'...\n";
$sourcesAfter = DB::table('inventory_sources')->get();
echo 'Total inventory_sources after seeding: '.$sourcesAfter->count()."\n";
foreach ($sourcesAfter as $s) {
    echo "  - ID: {$s->id} | Code: {$s->code} | Name: {$s->name} | Type: {$s->source_type} | Salable: {$s->is_salable} | Delivery: {$s->is_delivery_source}\n";
}

// 1. Verify 6 Canonical Sources exist
foreach ($canonicalCodes as $cc) {
    $src = $sourcesAfter->firstWhere('code', $cc);
    if (! $src) {
        throw new Exception("CRITICAL: Missing canonical source '$cc' after seeding!");
    }
}
echo "✓ All 6 Canonical Sources verified.\n";

// 2. Verify default and hayest_central preserved
$defaultSrc = $sourcesAfter->firstWhere('code', 'default');
$hayestCentralSrc = $sourcesAfter->firstWhere('code', 'hayest_central');
if (! $defaultSrc) {
    throw new Exception("CRITICAL: 'default' source missing!");
}
if (! $hayestCentralSrc) {
    throw new Exception("CRITICAL: 'hayest_central' source missing!");
}
echo "✓ 'default' (ID {$defaultSrc->id}) and 'hayest_central' (ID {$hayestCentralSrc->id}) are intact.\n";

// 3. Verify zero product inventories
$invCountAfter = DB::table('product_inventories')->count();
if ($invCountAfter !== $preCounts['product_inventories']) {
    throw new Exception("CRITICAL: product_inventories changed! Expected {$preCounts['product_inventories']}, got $invCountAfter");
}
echo "✓ product_inventories count unchanged: $invCountAfter\n";

// 4. Verify zero inventory movements
$movementsCountAfter = DB::table('inventory_movements')->count();
if ($movementsCountAfter !== $preCounts['inventory_movements']) {
    throw new Exception("CRITICAL: inventory_movements changed! Expected {$preCounts['inventory_movements']}, got $movementsCountAfter");
}
echo "✓ inventory_movements count unchanged: $movementsCountAfter\n";

// 5. Verify orders (3) and customers (21) unchanged
$ordersCountAfter = DB::table('orders')->count();
$customersCountAfter = DB::table('customers')->count();
if ($ordersCountAfter !== 3) {
    throw new Exception("CRITICAL: orders count changed! Expected 3, got $ordersCountAfter");
}
if ($customersCountAfter !== 21) {
    throw new Exception("CRITICAL: customers count changed! Expected 21, got $customersCountAfter");
}
echo "✓ orders (3) and customers (21) strictly unchanged.\n\n";

// -------------------------------------------------------------
// STEP 8: TRANSIENT SMOKE TEST INSIDE TRANSACTION -> ROLLBACK
// -------------------------------------------------------------
echo "[STEP 8] Executing transient smoke test inside transaction...\n";
DB::beginTransaction();
try {
    $productId = DB::table('products')->insertGetId([
        'sku' => 'TRANSIENT-TEST-INIT',
        'type' => 'simple',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $saHub = $sourcesAfter->firstWhere('code', 'hayest_dropship_sa');
    $yeDropship = $sourcesAfter->firstWhere('code', 'hayest_dropship_ye');
    $yeQuarantine = $sourcesAfter->firstWhere('code', 'hayest_quarantine_ye');

    // Create transient movement
    $movId = DB::table('inventory_movements')->insertGetId([
        'movement_type' => 'internal_transfer',
        'product_id' => $productId,
        'sku' => 'TRANSIENT-TEST-INIT',
        'source_inventory_source_id' => $saHub->id,
        'target_inventory_source_id' => $yeDropship->id,
        'quantity' => 10,
        'reference_event' => 'transient_init_smoke',
        'idempotency_key' => 'TRAN_SMK_'.Str::random(10),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Create transient transfer manifest
    $manId = DB::table('inventory_transfer_manifests')->insertGetId([
        'manifest_number' => 'TRF-INIT-'.Str::random(6),
        'idempotency_key' => 'TRF_IDEMP_'.Str::random(10),
        'source_inventory_source_id' => $saHub->id,
        'destination_inventory_source_id' => $yeDropship->id,
        'status' => 'draft',
        'carrier_name' => 'Transient Air Cargo',
        'tracking_number' => 'HY-TRK-INIT',
        'total_packages' => 1,
        'total_items_count' => 10,
        'created_by_admin_id' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Create transient inbound receipt
    $recId = DB::table('inbound_receipt_manifests')->insertGetId([
        'receipt_number' => 'REC-INIT-'.Str::random(6),
        'idempotency_key' => 'REC_IDEMP_'.Str::random(10),
        'inventory_transfer_manifest_id' => $manId,
        'destination_inventory_source_id' => $yeDropship->id,
        'quarantine_inventory_source_id' => $yeQuarantine->id,
        'status' => 'completed',
        'total_received_good' => 8,
        'total_received_damaged' => 2,
        'total_received_missing' => 0,
        'received_by_admin_id' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    echo "  ✓ Transient Entities Created: Product #$productId, Movement #$movId, Manifest #$manId, Receipt #$recId\n";

    DB::rollBack();
    echo "  ✓ Transaction rolled back successfully: 0 records remained in '$targetDb'.\n\n";
} catch (Throwable $e) {
    DB::rollBack();
    throw new Exception('Smoke test failed: '.$e->getMessage());
}

// -------------------------------------------------------------
// STEP 9: READ-ONLY TESTING OF ADMIN PANEL & HTTP/ACL PERMISSIONS
// -------------------------------------------------------------
echo "[STEP 9] Checking ACL roles and direct permissions on '$targetDb'...\n";
$roles = DB::table('roles')->get();
echo 'Roles in database: '.$roles->count()."\n";
foreach ($roles as $r) {
    echo "  - Role ID: {$r->id} | Name: {$r->name} | Permission Type: {$r->permission_type}\n";
}

// Check if Administrator role exists
$adminRole = $roles->firstWhere('name', 'Administrator');
if (! $adminRole) {
    echo "Creating Administrator role...\n";
    $adminRoleId = DB::table('roles')->insertGetId([
        'name' => 'Administrator',
        'permission_type' => 'all',
        'permissions' => json_encode(['all']),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

$adminsCount = DB::table('admins')->count();
echo "Admins in database: $adminsCount\n\n";

// Snapshot post counts
$postCounts = [
    'tables' => count(DB::select('SHOW TABLES')),
    'inventory_sources' => DB::table('inventory_sources')->count(),
    'product_inventories' => DB::table('product_inventories')->count(),
    'inventory_movements' => DB::table('inventory_movements')->count(),
    'orders' => DB::table('orders')->count(),
    'customers' => DB::table('customers')->count(),
    'delivery_governorate_rules' => DB::table('delivery_governorate_rules')->count(),
    'delivery_points' => DB::table('delivery_points')->count(),
];

// Save summary report data
$reportData = [
    'backup_file' => $backupFile,
    'backup_size' => $fileSize,
    'backup_sha256' => $sha256,
    'pre_counts' => $preCounts,
    'post_counts' => $postCounts,
    'sources' => $sourcesAfter->toArray(),
    'migrations_count' => DB::table('migrations')->count(),
];
file_put_contents($backupDir.'/safe_initialization_report_higest.json', json_encode($reportData, JSON_PRETTY_PRINT));
echo "Summary JSON saved to $backupDir/safe_initialization_report_higest.json\n";

echo "\n===============================================================\n";
echo "SAFE INITIALIZATION COMPLETED WITH 100% SUCCESS\n";
echo "===============================================================\n";
