import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Webkul\\Procurement\\Models\\ExternalPlatformOrder;
use Webkul\\Procurement\\Services\\AliExpressPollingService;

$targetOrderId = '1122571315031333';
$order = ExternalPlatformOrder::where('external_order_id', $targetOrderId)->firstOrFail();

echo "Before Sync: Order #{$order->id} | Raw: {$order->raw_status} | Normalized: {$order->normalized_status}\\n";

$polling = app(AliExpressPollingService::class);
$updated = $polling->syncOrder($order);

echo "After Sync:  Order #{$updated->id} | Raw: {$updated->raw_status} | Normalized: {$updated->normalized_status} | LastSynced: {$updated->last_synced_at}\\n";
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/sync_order_114.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 sync_order_114.php && rm sync_order_114.php")
print(f"OUT:\n{out}")

client.close()
