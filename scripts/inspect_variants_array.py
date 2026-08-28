import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$import = DB::table('aliexpress_product_imports')->where('aliexpress_product_id', '1005011935069063')->first();
$raw = json_decode($import->payload_snapshot, true);
$variants = $raw['variants'] ?? [];
echo "Count of variants: " . count($variants) . "\n";
if (!empty($variants[0])) {
    echo "First variant:\n";
    print_r($variants[0]);
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/inspect_variants_array.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 inspect_variants_array.php && rm inspect_variants_array.php")
print(f"OUTPUT:\n{out}")

client.close()
