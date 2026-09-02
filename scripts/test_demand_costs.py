import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$demands = DB::table('procurement_demands')
    ->leftJoin('orders', 'procurement_demands.order_id', '=', 'orders.id')
    ->leftJoin('order_items', 'procurement_demands.order_item_id', '=', 'order_items.id')
    ->select(
        'procurement_demands.id',
        'procurement_demands.supplier_sku_id',
        'procurement_demands.product_id',
        'procurement_demands.variant_product_id',
        'procurement_demands.source_snapshot',
        'order_items.price as customer_price',
        'order_items.base_price as customer_base_price'
    )
    ->orderByDesc('procurement_demands.id')
    ->limit(10)
    ->get();

foreach ($demands as $d) {
    $snap = json_decode($d->source_snapshot, true);
    $snapCost = $snap['unit_cost'] ?? null;
    
    // Get cost from product_attribute_values
    $targetProdId = $d->variant_product_id ?: $d->product_id;
    $costRow = DB::table('product_attribute_values')
        ->where('product_id', $targetProdId)
        ->where('attribute_id', 12)
        ->first();
    $systemCost = $costRow ? $costRow->float_value : null;

    echo "Demand #{$d->id} (SKU: {$d->supplier_sku_id}):\n";
    echo "  - Customer Price (سعر البيع للعميل): \${$d->customer_price}\n";
    echo "  - System Cost (التكلفة وفقاً للنظام): \${$systemCost}\n";
    echo "  - AE Cost in Snapshot (تكلفة الشراء في علي إكسبرس): \${$snapCost}\n";
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_demand_costs.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php test_demand_costs.php && rm test_demand_costs.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
