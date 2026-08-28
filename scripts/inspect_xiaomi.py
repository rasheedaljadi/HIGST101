import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$import = DB::table('aliexpress_product_imports')->where('aliexpress_product_id', '1005011942764190')->first();
if ($import) {
    echo "Found Import for 1005011942764190\n";
    $snap = json_decode($import->payload_snapshot, true);
    echo "Axes:\n";
    print_r($snap['axes'] ?? []);
    echo "Variants count: " . count($snap['variants'] ?? []) . "\n";
    foreach ($snap['variants'] ?? [] as $v) {
        echo "SKU ID: {$v['sku_id']}, Price: {$v['price']}, sku_attr: " . ($v['sku_attr'] ?? 'NONE') . "\n";
        print_r($v['options_by_axis'] ?? []);
    }
} else {
    echo "No import record found for 1005011942764190\n";
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/inspect_xiaomi.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 inspect_xiaomi.php && rm inspect_xiaomi.php")
print(f"OUTPUT:\n{out}")

client.close()
