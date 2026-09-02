import remote_ssh_helper as r

client = r.get_ssh_client()

test_php = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Webkul\\Procurement\\DataGrids\\ProcurementDemandDataGrid;
use Webkul\\User\\Models\\Admin;

$admin = Admin::first();
if ($admin) {
    auth()->guard('admin')->setUser($admin);
}

$grid = app(ProcurementDemandDataGrid::class);
$grid->prepareColumns();

echo "=========================================================\\n";
echo "ALL COLUMNS IN DATAGRID:\\n";
echo "=========================================================\\n";
foreach ($grid->getColumns() as $c) {
    $idx = method_exists($c, 'getIndex') ? $c->getIndex() : ($c->index ?? 'unknown');
    $lbl = method_exists($c, 'getLabel') ? $c->getLabel() : ($c->label ?? 'unknown');
    echo "Col: {$idx} => {$lbl}\\n";
}

echo "\\n=========================================================\\n";
echo "ROW RENDERING FOR LATEST DEMANDS:\\n";
echo "=========================================================\\n";
$targetCol = null;
foreach ($grid->getColumns() as $c) {
    $idx = method_exists($c, 'getIndex') ? $c->getIndex() : ($c->index ?? 'unknown');
    if ($idx === 'product_name') {
        $targetCol = $c;
        break;
    }
}

if ($targetCol) {
    echo "product_name Column Found! Testing closures on live rows:\\n";
    $query = $grid->prepareQueryBuilder();
    $rows = $query->orderBy('demand_id', 'desc')->limit(3)->get();
    foreach ($rows as $row) {
        echo "\\n--- Demand #{$row->demand_id} (Order #{$row->order_increment_id}) ---\\n";
        $closure = $targetCol->closure ?? ($targetCol->getClosure() ?? null);
        if ($closure) {
            echo "Rendered HTML:\\n" . $closure($row) . "\\n";
        } else {
            echo "No closure found on column object.\\n";
        }
    }
} else {
    echo "product_name column NOT found!\\n";
}
"""

sftp2 = client.open_sftp()
with sftp2.file("/home/highest-ye/htdocs/highest-ye.store/test_product_name_grid.php", "w") as f:
    f.write(test_php)
sftp2.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_product_name_grid.php && rm test_product_name_grid.php")
print(f"\nVerification Output:\n{out}")

client.close()
