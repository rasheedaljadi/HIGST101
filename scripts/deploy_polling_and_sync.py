import remote_ssh_helper as r

client = r.get_ssh_client()
sftp = client.open_sftp()

local_base = r"e:\HIGESTO NEW1\higest\higest101"
remote_base = "/home/highest-ye/htdocs/highest-ye.store"

f = "packages/Webkul/Procurement/src/Services/AliExpressPollingService.php"
sftp.put(f"{local_base}/{f}", f"{remote_base}/{f}")
sftp.close()

# Sync all external orders
sync_php = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Webkul\\Procurement\\Models\\ExternalPlatformOrder;
use Webkul\\Procurement\\Services\\AliExpressPollingService;

echo "=========================================================\\n";
echo "SYNCING ALL ACTIVE EXTERNAL PLATFORM ORDERS\\n";
echo "=========================================================\\n";

$polling = app(AliExpressPollingService::class);
$orders = ExternalPlatformOrder::whereNotNull('external_order_id')
    ->whereNotIn('normalized_status', ['completed', 'cancelled'])
    ->get();

echo "Found {$orders->count()} active external orders to sync.\\n";

foreach ($orders as $ord) {
    try {
        $updated = $polling->syncOrder($ord);
        echo "  Synced Order #{$updated->id} (Ext: {$updated->external_order_id}): Raw={$updated->raw_status} => Status={$updated->normalized_status}\\n";
    } catch (\\Throwable $e) {
        echo "  Error syncing Order #{$ord->id}: " . $e->getMessage() . "\\n";
    }
}
"""

sftp2 = client.open_sftp()
with sftp2.file(f"{remote_base}/sync_all_active_orders.php", "w") as f:
    f.write(sync_php)
sftp2.close()

code, out, err = r.run_remote_cmd(client, f"cd {remote_base} && php8.4 sync_all_active_orders.php && rm sync_all_active_orders.php")
print(f"OUT:\n{out}")

client.close()
