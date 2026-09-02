import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Illuminate\\Support\\Facades\\DB;
use Webkul\\Procurement\\Contracts\\AliExpressOrderGateway;
use Webkul\\Procurement\\Models\\ExternalPlatformOrder;
use Webkul\\Procurement\\Services\\AliExpressPollingService;

$targetOrderId = '1122571315031333';

echo "=========================================================\\n";
echo "1. QUERYING ALIEXPRESS GATEWAY FOR ORDER {$targetOrderId}\\n";
echo "=========================================================\\n";

$gateway = app(AliExpressOrderGateway::class);
$snapshot = $gateway->getOrder($targetOrderId);

echo "Snapshot Result:\\n";
echo "  externalOrderId: {$snapshot->externalOrderId}\\n";
echo "  orderStatus: {$snapshot->orderStatus}\\n";
echo "  rawStatus: {$snapshot->rawStatus}\\n";
echo "  trackingNumber: " . ($snapshot->trackingNumber ?? 'NONE') . "\\n";
echo "  carrierName: " . ($snapshot->carrierName ?? 'NONE') . "\\n";
echo "  overTimeLeft: " . ($snapshot->overTimeLeft ?? 'NONE') . "\\n";
echo "  paymentDeadlineAt: " . ($snapshot->paymentDeadlineAt ?? 'NONE') . "\\n";
echo "  rawResponse: " . json_encode($snapshot->rawResponse, JSON_UNESCAPED_UNICODE) . "\\n";

echo "\\n=========================================================\\n";
echo "2. CHECKING external_platform_orders TABLE FOR {$targetOrderId}\\n";
echo "=========================================================\\n";

$order = ExternalPlatformOrder::where('external_order_id', $targetOrderId)->first();
if ($order) {
    echo "Found ExternalPlatformOrder #{$order->id}:\\n";
    echo "  supplier_purchase_order_id: {$order->supplier_purchase_order_id}\\n";
    echo "  raw_status: {$order->raw_status}\\n";
    echo "  normalized_status: {$order->normalized_status}\\n";
    echo "  last_synced_at: {$order->last_synced_at}\\n";
    echo "  external_order_id: {$order->external_order_id}\\n";
    echo "  payment_deadline_at: {$order->payment_deadline_at}\\n";
} else {
    echo "No record in external_platform_orders with external_order_id = {$targetOrderId}\\n";
    echo "All existing external_platform_orders:\\n";
    $all = ExternalPlatformOrder::all();
    foreach ($all as $item) {
        echo "  ID: {$item->id} | ExternalID: {$item->external_order_id} | Status: {$item->normalized_status} (raw: {$item->raw_status}) | SPO: {$item->supplier_purchase_order_id} | Updated: {$item->updated_at}\\n";
    }
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/inspect_epo_live.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 inspect_epo_live.php && rm inspect_epo_live.php")
print(f"OUT:\n{out}")

client.close()
