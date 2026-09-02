import sys
sys.path.insert(0, 'scripts')
import remote_ssh_helper as r

client = r.get_ssh_client()
sftp = client.open_sftp()

php_code = """<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Webkul\\Procurement\\Models\\ProcurementDemand;
use Webkul\\Procurement\\Models\\ProcurementBatch;
use Webkul\\Procurement\\Models\\SupplierPurchaseOrder;
use Webkul\\Procurement\\Models\\ExternalPlatformOrder;

echo "=== DEMANDS BY STATE ===\\n";
$demands = ProcurementDemand::selectRaw('state, count(*) as cnt')->groupBy('state')->get();
foreach ($demands as $d) {
    echo "  - {$d->state}: {$d->cnt}\\n";
}

echo "\\n=== BATCHES BY STATE ===\\n";
$batches = ProcurementBatch::selectRaw('state, count(*) as cnt')->groupBy('state')->get();
foreach ($batches as $b) {
    echo "  - {$b->state}: {$b->cnt}\\n";
}

echo "\\n=== SUPPLIER POs BY STATE ===\\n";
$spos = SupplierPurchaseOrder::selectRaw('state, count(*) as cnt')->groupBy('state')->get();
foreach ($spos as $s) {
    echo "  - {$s->state}: {$s->cnt}\\n";
}

echo "\\n=== PLATFORM ORDERS BY STATUS ===\\n";
$pos = ExternalPlatformOrder::selectRaw('normalized_status, count(*) as cnt')->groupBy('normalized_status')->get();
foreach ($pos as $p) {
    echo "  - {$p->normalized_status}: {$p->cnt}\\n";
}

echo "\\n=== OPEN DEMANDS SAMPLE ===\\n";
$openDemands = ProcurementDemand::where('state', 'open_for_batching')->limit(5)->get();
foreach ($openDemands as $od) {
    echo "  - Demand #{$od->id} | Order #{$od->order_id} | Store: {$od->supplier_store_id} ({$od->supplier_store_name}) | SKU: {$od->supplier_sku_id} | Qty: {$od->qty_required_external}\\n";
}
"""

with sftp.file("/home/highest-ye/htdocs/highest-ye.store/inspect_proc_summary.php", "w") as f:
    f.write(php_code)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 inspect_proc_summary.php && rm inspect_proc_summary.php")
print(out)
client.close()
