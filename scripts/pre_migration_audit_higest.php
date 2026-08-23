<?php

/**
 * Pre-Migration Inspection and Backup Script for database 'higest'
 */

require_once __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

$targetDb = 'higest';
$backupDir = 'C:/Users/RASHEED/.gemini/antigravity-ide/brain/34e700ea-070a-4bed-959e-177086a7bed1/scratch';
if (! is_dir($backupDir)) {
    mkdir($backupDir, 0777, true);
}
$backupFile = $backupDir.'/higest_backup_'.date('Ymd_His').'.sql';

echo "===============================================================\n";
echo "STEP 1 & 2: BACKUP & PRE-MIGRATION AUDIT OF DATABASE '$targetDb'\n";
echo "===============================================================\n\n";

// 1. Take Backup using mysqldump
$mysqldumpPath = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
if (! file_exists($mysqldumpPath)) {
    $mysqldumpPath = 'mysqldump';
}

$dumpCmd = "\"$mysqldumpPath\" --host=127.0.0.1 --port=3306 -u root --routines --triggers --events $targetDb > \"$backupFile\"";
echo "Executing backup command...\n";
exec($dumpCmd, $output, $returnVar);

if ($returnVar !== 0 || ! file_exists($backupFile) || filesize($backupFile) === 0) {
    throw new Exception("Backup failed with exit code $returnVar");
}

$fileSize = filesize($backupFile);
$sha256 = hash_file('sha256', $backupFile);

echo "Backup successful:\n";
echo "  File: $backupFile\n";
echo '  Size: '.number_format($fileSize).' bytes ('.round($fileSize / 1024 / 1024, 2)." MB)\n";
echo "  SHA256: $sha256\n\n";

// Switch DB connection to targetDb
Config::set('database.connections.mysql.database', $targetDb);
DB::purge('mysql');
DB::reconnect('mysql');

// 2. Collect Table Counts & Schema
$tables = DB::select('SHOW TABLES');
$tableKey = 'Tables_in_'.$targetDb;

$tableCounts = [];
$totalRows = 0;
foreach ($tables as $t) {
    $tableName = $t->$tableKey;
    try {
        $count = DB::table($tableName)->count();
        $tableCounts[$tableName] = $count;
        $totalRows += $count;
    } catch (Throwable $e) {
        $tableCounts[$tableName] = 'ERROR: '.$e->getMessage();
    }
}

echo "Database Summary:\n";
echo '  Total Tables: '.count($tables)."\n";
echo '  Total Rows: '.number_format($totalRows)."\n\n";

// 3. Migrations status in targetDb
$migrations = DB::table('migrations')->orderBy('id', 'asc')->get();
echo 'Migrations in database: '.$migrations->count()."\n";
echo "Last 10 migrations:\n";
foreach ($migrations->slice(-10) as $m) {
    echo "  - [batch {$m->batch}] {$m->migration}\n";
}
echo "\n";

// 4. Read-only Audit of Key Entities:
echo "--- Read-only Entity Audit ---\n";

// A. Inventory Sources
echo "\n[Inventory Sources]:\n";
if (Schema::hasTable('inventory_sources')) {
    $sources = DB::table('inventory_sources')->get();
    echo 'Total Sources: '.$sources->count()."\n";
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
} else {
    echo "  inventory_sources table does not exist.\n";
}

// B. Orders & Allocations
echo "\n[Orders & Allocations]:\n";
if (Schema::hasTable('orders')) {
    $ordersCount = DB::table('orders')->count();
    $orderItemsCount = Schema::hasTable('order_items') ? DB::table('order_items')->count() : 0;
    $orderAllocationsCount = Schema::hasTable('order_allocations') ? DB::table('order_allocations')->count() : 0;
    echo "  Orders: $ordersCount\n";
    echo "  Order Items: $orderItemsCount\n";
    echo "  Order Allocations: $orderAllocationsCount\n";
} else {
    echo "  orders table does not exist.\n";
}

// C. Delivery Assignments & Points
echo "\n[Delivery Assignments & Points]:\n";
if (Schema::hasTable('delivery_assignments')) {
    $assignCount = DB::table('delivery_assignments')->count();
    $pointsCount = Schema::hasTable('delivery_points') ? DB::table('delivery_points')->count() : 0;
    $rulesCount = Schema::hasTable('delivery_governorate_rules') ? DB::table('delivery_governorate_rules')->count() : 0;
    echo "  Delivery Assignments: $assignCount\n";
    echo "  Delivery Points: $pointsCount\n";
    echo "  Governorate Rules: $rulesCount\n";
} else {
    echo "  delivery_assignments table does not exist.\n";
}

// D. Stock Balances
echo "\n[Product Inventories & Movements]:\n";
if (Schema::hasTable('product_inventories')) {
    $invCount = DB::table('product_inventories')->count();
    $totalQty = DB::table('product_inventories')->sum('qty');
    $movementsCount = Schema::hasTable('inventory_movements') ? DB::table('inventory_movements')->count() : 0;
    echo "  Product Inventories Records: $invCount\n";
    echo "  Total Inventory Quantity Sum: $totalQty\n";
    echo "  Inventory Movements Records: $movementsCount\n";
} else {
    echo "  product_inventories table does not exist.\n";
}

// Save detailed audit JSON
$auditData = [
    'backup_file' => $backupFile,
    'backup_size' => $fileSize,
    'backup_sha256' => $sha256,
    'database' => $targetDb,
    'table_counts' => $tableCounts,
    'migrations_count' => $migrations->count(),
    'migrations' => $migrations->pluck('migration')->toArray(),
];
file_put_contents($backupDir.'/pre_migration_audit_higest.json', json_encode($auditData, JSON_PRETTY_PRINT));
echo "\nAudit data saved to $backupDir/pre_migration_audit_higest.json\n";
