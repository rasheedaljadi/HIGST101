import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$import = DB::table('aliexpress_product_imports')->where('aliexpress_product_id', '1005011935069063')->first();
if ($import) {
    echo "Found Import for 1005011935069063:\n";
    echo "ID: {$import->id}, Status: {$import->status}\n";
    if (!empty($import->raw_payload)) {
        echo "Raw Payload (first 500 chars):\n" . substr($import->raw_payload, 0, 500) . "\n";
    }
} else {
    echo "No direct import row, listing latest imports:\n";
    $latest = DB::table('aliexpress_product_imports')->orderBy('id', 'desc')->take(3)->get();
    foreach ($latest as $l) {
        echo "ID: {$l->id}, AE ID: {$l->aliexpress_product_id}, Bagisto ID: {$l->product_id}\n";
    }
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/inspect_ae_imports.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 inspect_ae_imports.php && rm inspect_ae_imports.php")
print(f"OUTPUT:\n{out}")

client.close()
