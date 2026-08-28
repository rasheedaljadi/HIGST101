import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$skuId = '12000059778048925';

$tables = ['aliexpress_product_imports', 'products', 'product_attribute_values', 'product_flat', 'supplier_purchase_order_items', 'procurement_demands', 'procurement_demand_allocations'];

foreach ($tables as $t) {
    if (Illuminate\Support\Facades\Schema::hasTable($t)) {
        $cols = Illuminate\Support\Facades\Schema::getColumnListing($t);
        foreach ($cols as $c) {
            $cnt = DB::table($t)->where($c, 'like', "%{$skuId}%")->count();
            if ($cnt > 0) {
                echo "Table: {$t}, Column: {$c}, Count: {$cnt}\n";
            }
        }
    }
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/search_sku_all_tables.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 search_sku_all_tables.php && rm search_sku_all_tables.php")
print(f"OUTPUT:\n{out}")

client.close()
