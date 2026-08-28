import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$rows = DB::table('external_platform_orders')
    ->whereIn('id', [62, 63, 64, 65, 66])
    ->get();

foreach ($rows as $r) {
    echo "ID: {$r->id}\n";
    echo "PO Number: " . ($r->purchase_order_number ?? 'N/A') . "\n";
    echo "Status: " . ($r->normalized_status ?? 'N/A') . "\n";
    echo "Ext Order ID: " . ($r->external_order_id ?? 'N/A') . "\n";
    echo "Created At: {$r->created_at}\n";
    echo "Error / Raw Response:\n" . ($r->raw_response ?? $r->error_message ?? $r->last_error ?? 'N/A') . "\n";
    echo "------------------------------------------------------------\n";
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/inspect_platform_orders.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 inspect_platform_orders.php && rm inspect_platform_orders.php")
print(f"OUTPUT:\n{out}")

client.close()
