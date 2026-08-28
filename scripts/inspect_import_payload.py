import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$import = DB::table('aliexpress_product_imports')->where('aliexpress_product_id', '1005011935069063')->first();
print_r(array_keys((array)$import));
if (!empty($import->raw_payload)) {
    $raw = json_decode($import->raw_payload, true);
    echo "Raw payload keys: " . implode(', ', array_keys($raw ?? [])) . "\n";
    $variants = data_get($raw, 'result.ae_item_sku_info_dtos.ae_item_sku_info_d_t_o', []);
    if (isset($variants['sku_id'])) $variants = [$variants];
    echo "Count of variants in raw_payload: " . count($variants) . "\n";
    foreach ($variants as $v) {
        echo "SKU ID: {$v['sku_id']}, sku_attr: {$v['sku_attr']}\n";
    }
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/inspect_import_payload.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 inspect_import_payload.php && rm inspect_import_payload.php")
print(f"OUTPUT:\n{out}")

client.close()
