import remote_ssh_helper as r

client = r.get_ssh_client()
sftp = client.open_sftp()

local_base = r"e:\HIGESTO NEW1\higest\higest101"
remote_base = "/home/highest-ye/htdocs/highest-ye.store"

files = [
    "packages/Webkul/Procurement/src/DataGrids/ExternalPlatformOrderDataGrid.php",
    "packages/Webkul/Procurement/src/Resources/lang/ar/app.php",
]

for f in files:
    sftp.put(f"{local_base}/{f}", f"{remote_base}/{f}")
sftp.close()

# Verify and clear cache
verify_php = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Webkul\\Procurement\\DataGrids\\ExternalPlatformOrderDataGrid;
use Illuminate\\Support\\Facades\\DB;

echo "=========================================================\\n";
echo "TESTING ExternalPlatformOrderDataGrid COLUMNS\\n";
echo "=========================================================\\n";

$grid = app(ExternalPlatformOrderDataGrid::class);
$cols = $grid->getColumns();

echo "DataGrid Columns:\\n";
foreach ($cols as $c) {
    echo "  - Column [{$c->getIndex()}]: '{$c->getLabel()}' (Type: {$c->getType()})\\n";
}

echo "\\nTesting DataGrid JSON Row Processing for Order #114 (1122571315031333):\\n";
$req = \\Illuminate\\Http\\Request::create('/admin/procurement/platform-orders', 'GET', [], [], [], [
    'HTTP_X-REQUESTED-WITH' => 'XMLHttpRequest',
    'HTTP_ACCEPT' => 'application/json',
]);
app()->instance('request', $req);

$admin = DB::table('admins')->first();
auth()->guard('admin')->loginUsingId($admin->id);

$json = $grid->toJson();
$data = json_decode($json->getContent(), true);

$records = $data['records'] ?? [];
echo "Total Records: " . count($records) . "\\n";

foreach ($records as $r) {
    if ($r['external_order_id'] == '1122571315031333') {
        echo "Found Order 1122571315031333 in Grid:\\n";
        echo "  Status: " . strip_tags($r['normalized_status']) . "\\n";
        echo "  Expected Total: " . strip_tags($r['spo_expected_total']) . "\\n";
        echo "  Actual Paid (AliExpress): " . strip_tags($r['spo_actual_total']) . "\\n";
    }
}
"""

sftp2 = client.open_sftp()
with sftp2.file(f"{remote_base}/verify_grid_cols.php", "w") as f:
    f.write(verify_php)
sftp2.close()

code, out, err = r.run_remote_cmd(client, f"cd {remote_base} && php8.4 verify_grid_cols.php && rm verify_grid_cols.php && php8.4 artisan view:clear && php8.4 artisan cache:clear")
print(f"OUT:\n{out}")

client.close()
