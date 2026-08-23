<?php

/**
 * Intelligent Migration Reconciler and Applier for database 'higest'
 */

require_once __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

Config::set('database.connections.mysql.database', 'higest');
DB::purge('mysql');
DB::reconnect('mysql');

echo "===============================================================\n";
echo "RECONCILING AND APPLYING MIGRATIONS ON 'higest'\n";
echo "===============================================================\n\n";

$migrated = DB::table('migrations')->pluck('migration')->toArray();

// Collect all migration paths
$migrationPaths = [
    database_path('migrations'),
];
$packageDirs = glob(base_path('packages/Webkul/*/src/Database/Migrations'));
$migrationPaths = array_merge($migrationPaths, $packageDirs);

$allFiles = [];
foreach ($migrationPaths as $path) {
    if (is_dir($path)) {
        foreach (glob($path.'/*.php') as $file) {
            $allFiles[basename($file, '.php')] = $file;
        }
    }
}
ksort($allFiles);

// Align refunds table columns if missing base_discount_amount
if (Schema::hasTable('refunds') && ! Schema::hasColumn('refunds', 'base_discount_amount')) {
    Schema::table('refunds', function (Blueprint $table) {
        $table->string('increment_id')->nullable();
        $table->string('state')->nullable();
        $table->boolean('email_sent')->default(0);
        $table->integer('total_qty')->nullable();
        $table->string('base_currency_code')->nullable();
        $table->string('channel_currency_code')->nullable();
        $table->string('order_currency_code')->nullable();
        $table->decimal('adjustment_refund', 12, 4)->default(0)->nullable();
        $table->decimal('base_adjustment_refund', 12, 4)->default(0)->nullable();
        $table->decimal('adjustment_fee', 12, 4)->default(0)->nullable();
        $table->decimal('base_adjustment_fee', 12, 4)->default(0)->nullable();
        $table->decimal('sub_total', 12, 4)->default(0)->nullable();
        $table->decimal('base_sub_total', 12, 4)->default(0)->nullable();
        $table->decimal('grand_total', 12, 4)->default(0)->nullable();
        $table->decimal('shipping_amount', 12, 4)->default(0)->nullable();
        $table->decimal('base_shipping_amount', 12, 4)->default(0)->nullable();
        $table->decimal('tax_amount', 12, 4)->default(0)->nullable();
        $table->decimal('base_tax_amount', 12, 4)->default(0)->nullable();
        $table->decimal('discount_percent', 12, 4)->default(0)->nullable();
        $table->decimal('discount_amount', 12, 4)->default(0)->nullable();
        $table->decimal('base_discount_amount', 12, 4)->default(0)->nullable();
    });
    echo "Aligned missing refunds table columns.\n";
}

// Align customers table columns if missing customer_group_id
if (Schema::hasTable('customers') && ! Schema::hasColumn('customers', 'customer_group_id')) {
    Schema::table('customers', function (Blueprint $table) {
        $table->string('gender', 50)->nullable();
        $table->date('date_of_birth')->nullable();
        $table->string('phone')->nullable();
        $table->string('image')->nullable();
        $table->tinyInteger('status')->default(1);
        $table->string('password')->nullable();
        $table->string('api_token', 80)->nullable();
        $table->integer('customer_group_id')->unsigned()->nullable();
        $table->boolean('subscribed_to_news_letter')->default(0);
        $table->boolean('is_verified')->default(0);
        $table->tinyInteger('is_suspended')->unsigned()->default(0);
        $table->string('token')->nullable();
        $table->text('notes')->nullable();
        $table->string('remember_token', 100)->nullable();
    });
    echo "Aligned missing customers table columns.\n";
}

$maxBatch = (int) DB::table('migrations')->max('batch');
$currentBatch = $maxBatch + 1;

$appliedCount = 0;
$reconciledCount = 0;

foreach ($allFiles as $name => $filePath) {
    if (in_array($name, $migrated)) {
        continue;
    }

    // Require migration file and get instance
    $migration = require $filePath;

    // Check if migration is anonymous or named class
    if (! is_object($migration)) {
        // Try to guess class name from file
        $class = Str::studly(implode('_', array_slice(explode('_', $name), 4)));
        if (class_exists($class)) {
            $migration = new $class;
        }
    }

    try {
        echo "Processing migration: $name ... ";
        $migration->up();
        DB::table('migrations')->insert([
            'migration' => $name,
            'batch' => $currentBatch,
        ]);
        echo "APPLIED\n";
        $appliedCount++;
    } catch (Throwable $e) {
        $msg = $e->getMessage();
        // If error is "already exists", "Duplicate column name", "Duplicate key name", "Duplicate entry", or "Can't DROP"
        if (
            str_contains($msg, 'already exists') ||
            str_contains($msg, 'Duplicate column name') ||
            str_contains($msg, 'Duplicate key name') ||
            str_contains($msg, 'Duplicate entry') ||
            str_contains($msg, 'already in use') ||
            str_contains($msg, "Can't DROP") ||
            str_contains($msg, 'check that it exists')
        ) {
            echo "ALREADY EXISTS/DROPPED IN SCHEMA -> RECONCILED\n";
            DB::table('migrations')->insert([
                'migration' => $name,
                'batch' => 1,
            ]);
            $reconciledCount++;
        } else {
            echo "FAILED: $msg\n";
            throw $e;
        }
    }
}

echo "\nMigration Reconcile & Run Complete:\n";
echo "  - Newly Applied Migrations: $appliedCount\n";
echo "  - Reconciled Existing Schema Migrations: $reconciledCount\n";
echo '  - Total DB Migrations Now: '.DB::table('migrations')->count()."\n";
