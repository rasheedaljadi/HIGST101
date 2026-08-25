import sys
import paramiko
import json

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')

HOST = '76.13.79.242'
USER = 'highest-ye'
PASS = 'YoK2PBV1fo82yujX2tDq'
APP_DIR = '/home/highest-ye/htdocs/highest-ye.store'

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(HOST, username=USER, password=PASS, timeout=20)
sftp = client.open_sftp()

php_script = """<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Illuminate\\Support\\Facades\\DB;
use Webkul\\Procurement\\Models\\SupplierPurchaseOrder;
use Webkul\\Procurement\\Models\\ExternalPlatformOrder;
use Webkul\\Sales\\Models\\Order;

$poNumber = 'SPO-20260825-MIRP1F-01';

$spo = SupplierPurchaseOrder::with(['items', 'batch'])
    ->where('purchase_order_number', $poNumber)
    ->first();

if (!$spo) {
    echo json_encode(['error' => "SPO {$poNumber} not found!"]);
    exit(0);
}

$platformOrders = ExternalPlatformOrder::where('supplier_purchase_order_id', $spo->id)
    ->get();

// Get demand allocations and customer orders
$spoItemIds = $spo->items->pluck('id')->all();
$allocations = DB::table('procurement_demand_allocations')
    ->whereIn('supplier_purchase_order_item_id', $spoItemIds)
    ->get();

$demandIds = $allocations->pluck('procurement_demand_id')->unique()->all();
$demands = DB::table('procurement_demands')->whereIn('id', $demandIds)->get();

$orderIds = $demands->pluck('order_id')->unique()->all();
$customerOrders = Order::with(['items', 'payment'])->whereIn('id', $orderIds)->get();

$customerOrdersData = [];
foreach ($customerOrders as $cOrder) {
    $itemsData = [];
    foreach ($cOrder->items as $it) {
        $itemsData[] = [
            'item_id' => $it->id,
            'sku' => $it->sku,
            'name' => $it->name,
            'qty_ordered' => $it->qty_ordered,
            'price' => $it->price,
            'base_price' => $it->base_price,
            'total' => $it->total,
            'base_total' => $it->base_total,
            'tax_amount' => $it->tax_amount,
            'base_tax_amount' => $it->base_tax_amount,
            'discount_amount' => $it->discount_amount,
            'additional' => $it->additional,
        ];
    }

    $customerOrdersData[] = [
        'order_id' => $cOrder->id,
        'increment_id' => $cOrder->increment_id,
        'status' => $cOrder->status,
        'order_currency' => $cOrder->order_currency_code,
        'base_currency' => $cOrder->base_currency_code,
        'grand_total' => $cOrder->grand_total,
        'base_grand_total' => $cOrder->base_grand_total,
        'sub_total' => $cOrder->sub_total,
        'base_sub_total' => $cOrder->base_sub_total,
        'tax_amount' => $cOrder->tax_amount,
        'base_tax_amount' => $cOrder->base_tax_amount,
        'shipping_amount' => $cOrder->shipping_amount,
        'base_shipping_amount' => $cOrder->base_shipping_amount,
        'payment_method' => $cOrder->payment?->method,
        'items' => $itemsData,
    ];
}

$output = [
    'spo' => $spo->toArray(),
    'platform_orders' => $platformOrders->toArray(),
    'customer_orders' => $customerOrdersData,
    'demands' => $demands,
    'allocations' => $allocations,
];

echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
"""

with sftp.open(f"{APP_DIR}/inspect_spo_deep.php", 'w') as f:
    f.write(php_script)
sftp.close()

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php inspect_spo_deep.php && rm inspect_spo_deep.php")
print(stdout.read().decode('utf-8', errors='replace'))
client.close()
