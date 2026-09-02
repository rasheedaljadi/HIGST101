import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Webkul\Product\Models\Product;

echo "=== 1. Checking product_flat for product 8740 and variant 8756 ===\n";
$pfParent = DB::table('product_flat')->where('product_id', 8740)->first();
$pfVariant = DB::table('product_flat')->where('product_id', 8756)->first();
print_r(['parent_flat' => $pfParent, 'variant_flat' => $pfVariant]);

echo "\n=== 2. Checking cost attribute in product_attribute_values ===\n";
$costAttr = DB::table('attributes')->where('code', 'cost')->first();
if ($costAttr) {
    $costValues = DB::table('product_attribute_values')
        ->where('attribute_id', $costAttr->id)
        ->whereIn('product_id', [8740, 8756])
        ->get();
    print_r($costValues);
}

echo "\n=== 3. Checking aliexpress_product_imports ===\n";
$import = DB::table('aliexpress_product_imports')->where('product_id', 8740)->first();
if ($import) {
    echo "Import ID: {$import->id}, Price: {$import->price}\n";
    // Check variants in payload
    $payload = json_decode($import->payload_snapshot, true);
    echo "Payload keys: " . implode(', ', array_keys($payload ?? [])) . "\n";
}

echo "\n=== 4. Checking Demand 58 source_snapshot ===\n";
$demand = DB::table('procurement_demands')->where('id', 58)->first();
$snap = json_decode($demand->source_snapshot, true);
print_r($snap);
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/inspect_pricing_sources.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php inspect_pricing_sources.php && rm inspect_pricing_sources.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
