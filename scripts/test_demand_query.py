import remote_ssh_helper as r

client = r.get_ssh_client()
sftp = client.open_sftp()

php_script = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$query = DB::table('procurement_demands')
    ->leftJoin('orders', 'procurement_demands.order_id', '=', 'orders.id')
    ->leftJoin('order_items', 'procurement_demands.order_item_id', '=', 'order_items.id')
    ->select(
        'procurement_demands.id as demand_id',
        'orders.increment_id as order_increment_id',
        'order_items.price as customer_selling_price',
        DB::raw("(
            SELECT pav.float_value 
            FROM product_attribute_values pav 
            JOIN attributes a ON a.id = pav.attribute_id 
            WHERE a.code = 'cost' 
              AND pav.product_id = COALESCE(procurement_demands.variant_product_id, procurement_demands.product_id) 
            LIMIT 1
        ) as system_cost"),
        DB::raw("JSON_UNQUOTE(JSON_EXTRACT(procurement_demands.source_snapshot, '$.unit_cost')) as aliexpress_cost"),
        DB::raw("(
            SELECT api.base_shipping_cost 
            FROM aliexpress_product_imports api 
            WHERE api.id = JSON_UNQUOTE(JSON_EXTRACT(procurement_demands.source_snapshot, '$.import_id'))
               OR api.aliexpress_product_id = procurement_demands.supplier_product_id
               OR api.product_id = procurement_demands.product_id
               OR api.product_id = (SELECT p.parent_id FROM products p WHERE p.id = COALESCE(procurement_demands.variant_product_id, procurement_demands.product_id))
            ORDER BY api.id DESC
            LIMIT 1
        ) as shipping_cost"),
        DB::raw("(
            SELECT api.shipping_company 
            FROM aliexpress_product_imports api 
            WHERE api.id = JSON_UNQUOTE(JSON_EXTRACT(procurement_demands.source_snapshot, '$.import_id'))
               OR api.aliexpress_product_id = procurement_demands.supplier_product_id
               OR api.product_id = procurement_demands.product_id
               OR api.product_id = (SELECT p.parent_id FROM products p WHERE p.id = COALESCE(procurement_demands.variant_product_id, procurement_demands.product_id))
            ORDER BY api.id DESC
            LIMIT 1
        ) as shipping_company")
    )
    ->orderByDesc('procurement_demands.id')
    ->take(10)
    ->get();

echo "Testing Query Results (Count: " . $query->count() . ")\\n";
echo "----------------------------------------------------------------------------------------------------\\n";
printf("%-10s | %-12s | %-12s | %-14s | %-14s | %-16s\\n", "Demand ID", "Selling Price", "System Cost", "Shipping Cost", "Cost + Shipping", "Company");
echo "----------------------------------------------------------------------------------------------------\\n";

foreach ($query as $row) {
    $sysCost = (float) ($row->system_cost ?? $row->aliexpress_cost ?? 0);
    $shipCost = $row->shipping_cost !== null ? (float) $row->shipping_cost : 0.0;
    $isChoice = (stripos($row->shipping_company ?? '', 'selection') !== false || stripos($row->shipping_company ?? '', 'choice') !== false);
    $effectiveShip = $isChoice ? 0.0 : $shipCost;
    $costWithShip = $sysCost + $effectiveShip;

    printf("%-10d | $%-11.2f | $%-11.2f | %-14s | $%-15.2f | %-16s\\n", 
        $row->demand_id, 
        (float)$row->customer_selling_price, 
        $sysCost, 
        $row->shipping_cost !== null ? ($isChoice ? '$0.00 (Choice)' : '$' . number_format($shipCost, 2)) : 'N/A', 
        $costWithShip,
        substr($row->shipping_company ?? 'N/A', 0, 16)
    );
}
"""

with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_demand_shipping_query.php", "w") as f:
    f.write(php_script)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_demand_shipping_query.php && rm test_demand_shipping_query.php")
print(f"CODE: {code}")
print(f"OUT:\n{out}")

client.close()
