import remote_ssh_helper as r
import os

client = r.get_ssh_client()
sftp = client.open_sftp()

files_to_sync = [
    ("packages/Webkul/Procurement/src/DataGrids/ProcurementDemandDataGrid.php", "packages/Webkul/Procurement/src/DataGrids/ProcurementDemandDataGrid.php"),
    ("packages/Webkul/Procurement/src/Services/ProcurementBatchService.php", "packages/Webkul/Procurement/src/Services/ProcurementBatchService.php"),
    ("packages/Webkul/Procurement/src/Resources/views/admin/batches/create.blade.php", "packages/Webkul/Procurement/src/Resources/views/admin/batches/create.blade.php"),
]

# Sync all 21 locale files
lang_dir = r"e:\HIGESTO NEW1\higest\higest101\packages\Webkul\Procurement\src\Resources\lang"
for loc in os.listdir(lang_dir):
    loc_file = os.path.join(lang_dir, loc, "app.php")
    if os.path.isfile(loc_file):
        rel = f"packages/Webkul/Procurement/src/Resources/lang/{loc}/app.php"
        files_to_sync.append((rel, rel))

local_base = r"e:\HIGESTO NEW1\higest\higest101"
remote_base = "/home/highest-ye/htdocs/highest-ye.store"

for rel_local, rel_remote in files_to_sync:
    local_path = os.path.join(local_base, rel_local)
    remote_path = f"{remote_base}/{rel_remote}"
    print(f"Uploading {rel_local} -> {remote_path}")
    sftp.put(local_path, remote_path)

sftp.close()

# Clear cache
code, out, err = r.run_remote_cmd(client, f"cd {remote_base} && php8.4 artisan view:clear && php8.4 artisan cache:clear")
print(f"Cache Clear: CODE {code}\n{out}")

# Verification on server: Render Demands DataGrid and Test Batch Creation blocking with 0 stock
test_php = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Webkul\\Procurement\\DataGrids\\ProcurementDemandDataGrid;
use Webkul\\Procurement\\Services\\ProcurementBatchService;
use Webkul\\Procurement\\Models\\ProcurementDemand;
use Webkul\\User\\Models\\Admin;
use Illuminate\\Http\\Request;

$admin = Admin::first();
if ($admin) {
    auth()->guard('admin')->setUser($admin);
}

echo "=========================================================\\n";
echo "1. VERIFYING DEMANDS DATAGRID COLUMNS & STOCK CLOSURE\\n";
echo "=========================================================\\n";
$grid = app(ProcurementDemandDataGrid::class);
$grid->prepareColumns();
$hasStockCol = false;
foreach ($grid->getColumns() as $col) {
    if ($col->getIndex() === 'supplier_stock') {
        $hasStockCol = true;
        echo "Found Column: '{$col->getIndex()}' (Label: '{$col->getLabel()}') ✅\\n";
    }
}

if (!$hasStockCol) {
    echo "supplier_stock Column NOT found ❌\\n";
}

echo "\\n=========================================================\\n";
echo "2. TESTING ZERO-STOCK BATCHING PREVENTION\\n";
echo "=========================================================\\n";
// Find demand 63 or 65 (stock = 0)
$outOfStockDemand = ProcurementDemand::where('supplier_sku_id', '12000052766564369')->first();
if ($outOfStockDemand) {
    echo "Testing Demand #{$outOfStockDemand->id} (SKU: {$outOfStockDemand->supplier_sku_id})\\n";
    $batchService = app(ProcurementBatchService::class);
    $stock = $batchService->resolveDemandSupplierStock($outOfStockDemand);
    echo "Resolved Stock: " . var_export($stock, true) . "\\n";
    
    // Temporarily test createBatch logic
    try {
        $outOfStockDemand->update(['state' => ProcurementDemand::STATE_OPEN_FOR_BATCHING, 'qty_batched' => 0]);
        $batchService->createBatch([$outOfStockDemand->id], $admin->id ?? 1);
        echo "Batch created unexpectedly ❌\\n";
    } catch (\\DomainException $e) {
        echo "Successfully Blocked Batching with Exception ✅:\\n";
        echo $e->getMessage() . "\\n";
    }
}
"""

sftp2 = client.open_sftp()
with sftp2.file(f"{remote_base}/test_stock_feature.php", "w") as f:
    f.write(test_php)
sftp2.close()

code, out, err = r.run_remote_cmd(client, f"cd {remote_base} && php8.4 test_stock_feature.php && rm test_stock_feature.php")
print(f"\nVerification Output:\n{out}")

client.close()
