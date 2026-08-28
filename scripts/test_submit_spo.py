import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Webkul\Procurement\Services\ProcurementSubmitService;
use Webkul\Procurement\Models\SupplierPurchaseOrder;

$service = app(ProcurementSubmitService::class);

// Reset SPO 80 state to draft so we can submit it
$spoId = 80;
SupplierPurchaseOrder::where('id', $spoId)->update([
    'state' => SupplierPurchaseOrder::STATE_DRAFT,
]);

echo "=== SUBMITTING SPO {$spoId} VIA PROCUREMENT SUBMIT SERVICE ===\n";
try {
    $res = $service->submitSupplierPurchaseOrder($spoId, 1);
    echo "🎉 SPO {$spoId} SUBMITTED SUCCESSFULLY!\n";
    $platformOrders = $res->platformOrders;
    foreach ($platformOrders as $po) {
        echo "PO ID: {$po->id}, Ext Order ID: {$po->external_order_id}, Status: {$po->normalized_status}\n";
    }
} catch (\Throwable $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_submit_spo.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_submit_spo.php && rm test_submit_spo.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
