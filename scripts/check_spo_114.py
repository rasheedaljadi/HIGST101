import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Webkul\\Procurement\\Models\\ExternalPlatformOrder;
use Webkul\\Procurement\\Models\\SupplierPurchaseOrder;

$targetOrderId = '1122571315031333';
$epo = ExternalPlatformOrder::where('external_order_id', $targetOrderId)->first();
$spo = SupplierPurchaseOrder::find($epo->supplier_purchase_order_id);

echo "EPO #{$epo->id}:\\n";
echo "  raw_status: {$epo->raw_status}\\n";
echo "  normalized_status: {$epo->normalized_status}\\n";

echo "SPO #{$spo->id}:\\n";
echo "  state: {$spo->state}\\n";
echo "  payment_state: {$spo->payment_state}\\n";
echo "  tracking_number: " . ($spo->tracking_number ?? 'NONE') . "\\n";
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/check_spo_114.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 check_spo_114.php && rm check_spo_114.php")
print(f"OUT:\n{out}")

client.close()
