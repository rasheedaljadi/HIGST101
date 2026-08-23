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

$count = DB::table('refunds')->count();
echo "refunds rows: $count\n";
