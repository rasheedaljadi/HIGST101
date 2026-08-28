import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$imports = DB::table('aliexpress_product_imports')
    ->whereNotNull('payload_snapshot')
    ->orderByDesc('id')
    ->take(20)
    ->get();

foreach ($imports as $imp) {
    $snap = json_decode($imp->payload_snapshot, true);
    $variants = $snap['variants'] ?? [];
    if (!empty($variants)) {
        foreach ($variants as $v) {
            if (!empty($v['sku_attr'])) {
                echo "AE Product: {$imp->aliexpress_product_id}, SKU: {$v['sku_id']}, sku_attr: {$v['sku_attr']}\n";
                break;
            }
        }
    }
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/inspect_imported_sku_attrs.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 inspect_imported_sku_attrs.php && rm inspect_imported_sku_attrs.php")
print(f"OUTPUT:\n{out}")

client.close()
