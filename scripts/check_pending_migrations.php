<?php

require_once __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

Config::set('database.connections.mysql.database', 'higest');
DB::purge('mysql');
DB::reconnect('mysql');

$migrated = DB::table('migrations')->pluck('migration')->toArray();

// Get all migration files across packages
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

echo 'Total migration files: '.count($allFiles)."\n";
echo 'Total migrated in DB: '.count($migrated)."\n\n";

$pending = [];
foreach ($allFiles as $name => $path) {
    if (! in_array($name, $migrated)) {
        $pending[] = $name;
    }
}

echo 'Pending Migrations ('.count($pending)."):\n";
foreach ($pending as $p) {
    echo "  - $p\n";
}
