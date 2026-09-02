import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== Columns in procurement_demands ===\n";
print_r(Schema::getColumnListing('procurement_demands'));

echo "\n=== Sample row from procurement_demands ===\n";
$demand = DB::table('procurement_demands')->orderByDesc('id')->first();
print_r($demand);

if ($demand && $demand->order_item_id) {
    echo "\n=== Order Item for this demand ===\n";
    $orderItem = DB::table('order_items')->where('id', $demand->order_item_id)->first();
    print_r($orderItem);
    
    echo "\n=== Product / Variant Product ===\n";
    if ($demand->product_id) {
        $prod = DB::table('products')->where('id', $demand->product_id)->first();
        print_r($prod);
    }
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/inspect_demands_schema.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php inspect_demands_schema.php && rm inspect_demands_schema.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
