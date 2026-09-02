import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$query = DB::table('procurement_demands')
    ->leftJoin('orders', 'procurement_demands.order_id', '=', 'orders.id')
    ->leftJoin('order_items', 'procurement_demands.order_item_id', '=', 'order_items.id')
    ->select(
        'procurement_demands.id as demand_id',
        'orders.increment_id as order_increment_id',
        'procurement_demands.supplier_sku_id',
        'procurement_demands.qty_required_external',
        'procurement_demands.qty_batched',
        'procurement_demands.state',
        'order_items.price as customer_selling_price',
        DB::raw("(
            SELECT pav.float_value 
            FROM product_attribute_values pav 
            JOIN attributes a ON a.id = pav.attribute_id 
            WHERE a.code = 'cost' 
              AND pav.product_id = COALESCE(procurement_demands.variant_product_id, procurement_demands.product_id) 
            LIMIT 1
        ) as system_cost"),
        DB::raw("JSON_UNQUOTE(JSON_EXTRACT(procurement_demands.source_snapshot, '$.unit_cost')) as supplier_snapshot_cost")
    )
    ->orderByDesc('procurement_demands.id')
    ->limit(10)
    ->get();

foreach ($query as $row) {
    $custPrice = (float) $row->customer_selling_price;
    $sysCost = (float) ($row->system_cost ?? $row->supplier_snapshot_cost);
    $aeCost = (float) $row->supplier_snapshot_cost;
    
    echo "Demand #{$row->demand_id} (Order: {$row->order_increment_id}):\n";
    echo "  1. سعر البيع للعميل: \${$custPrice}\n";
    echo "  2. التكلفة وفقاً للنظام: \${$sysCost}\n";
    echo "  3. تكلفة الشراء في علي إكسبرس (عند الإنشاء): \${$aeCost}\n";
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_datagrid_query.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php test_datagrid_query.php && rm test_datagrid_query.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
