import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Webkul\\Procurement\\Models\\ProcurementDemand;
use Webkul\\Sales\\Models\\OrderItem;
use Illuminate\\Support\\Facades\\DB;

$demands = ProcurementDemand::with(['product', 'orderItem'])->latest('id')->limit(5)->get();

echo "=========================================================\\n";
echo "DEMANDS PRODUCT & ORDER ITEM DATA:\\n";
echo "=========================================================\\n";

foreach ($demands as $d) {
    echo "\\nDemand ID: {$d->id} | Product ID: {$d->product_id} | Variant ID: {$d->variant_product_id}\\n";
    echo "  Product Relation Name: " . ($d->product?->name ?? 'NULL') . "\\n";
    
    if ($d->orderItem) {
        $oi = $d->orderItem;
        echo "  OrderItem ID: {$oi->id} | Name: {$oi->name}\\n";
        echo "  OrderItem Additional: " . json_encode($oi->additional, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\\n";
    } else {
        echo "  No OrderItem relation.\\n";
    }
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_demand_product_inspect.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_demand_product_inspect.php && rm test_demand_product_inspect.php")
print(f"CODE: {code}")
print(f"OUT:\n{out}")

client.close()
