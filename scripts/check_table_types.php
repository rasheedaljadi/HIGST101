<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$orderCols = DB::select("SHOW COLUMNS FROM orders");
echo "=== ORDERS TABLE COLUMNS ===\n";
foreach ($orderCols as $col) {
    if (in_array($col->Field, ['id', 'status'])) {
        echo "Field: {$col->Field}, Type: {$col->Type}, Key: {$col->Key}\n";
    }
}

$invoiceCols = DB::select("SHOW COLUMNS FROM invoices");
echo "\n=== INVOICES TABLE COLUMNS ===\n";
foreach ($invoiceCols as $col) {
    if (in_array($col->Field, ['id', 'state', 'order_id'])) {
        echo "Field: {$col->Field}, Type: {$col->Type}, Key: {$col->Key}\n";
    }
}
