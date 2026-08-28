import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$items = DB::table('supplier_purchase_order_items')->where('supplier_purchase_order_id', 78)->get();
echo "ITEMS FOR SPO 78:\n";
print_r($items);

if (!empty($items[0])) {
    $pId = $items[0]->product_id ?? null;
    if ($pId) {
        $prod = DB::table('products')->where('id', $pId)->first();
        echo "Product:\n";
        print_r($prod);
    }
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/inspect_spo_items.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 inspect_spo_items.php && rm inspect_spo_items.php")
print(f"OUTPUT:\n{out}")

client.close()
