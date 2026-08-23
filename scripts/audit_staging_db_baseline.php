<?php

$projDir = is_dir(__DIR__.'/../vendor') ? realpath(__DIR__.'/..') : '/home/highest-ye/htdocs/highest-ye.store';
require $projDir.'/vendor/autoload.php';
$app = require_once $projDir.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$tables = [
    'orders', 'order_items', 'invoices', 'shipments', 'refunds',
    'procurement_demands', 'procurement_batches', 'supplier_purchase_orders',
    'supplier_purchase_order_items', 'external_platform_orders',
    'inventory_sources', 'product_inventories', 'aliexpress_tokens',
];

$counts = [];
foreach ($tables as $t) {
    $counts[$t] = DB::table($t)->count();
}

$spo35 = DB::table('supplier_purchase_orders')->where('id', 35)->first();
$epo26 = DB::table('external_platform_orders')->where('id', 26)->first();

echo json_encode([
    'counts' => $counts,
    'spo35' => (array) $spo35,
    'epo26' => (array) $epo26,
], JSON_PRETTY_PRINT);
