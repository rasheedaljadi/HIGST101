import remote_ssh_helper as r

client = r.get_ssh_client()

verify_php = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Webkul\\Procurement\\DataGrids\\ExternalPlatformOrderDataGrid;
use Illuminate\\Support\\Facades\\DB;

$req = \\Illuminate\\Http\\Request::create('/admin/procurement/platform-orders', 'GET', [], [], [], [
    'HTTP_X-REQUESTED-WITH' => 'XMLHttpRequest',
    'HTTP_ACCEPT' => 'application/json',
]);
app()->instance('request', $req);

$admin = DB::table('admins')->first();
auth()->guard('admin')->loginUsingId($admin->id);

$grid = app(ExternalPlatformOrderDataGrid::class);
$json = $grid->toJson();
$data = json_decode($json->getContent(), true);

echo "COLUMNS LISTING IN JSON:\\n";
foreach ($data['columns'] as $c) {
    echo "  Index: [{$c['index']}] | Label: '{$c['label']}'\\n";
}

echo "\\nSAMPLE RECORD (Order #114):\\n";
foreach ($data['records'] as $r) {
    if (str_contains($r['external_order_id'], '1122571315031333')) {
        echo "  external_order_id: {$r['external_order_id']}\\n";
        echo "  normalized_status: {$r['normalized_status']}\\n";
        echo "  spo_expected_total: {$r['spo_expected_total']}\\n";
        echo "  spo_actual_total: {$r['spo_actual_total']}\\n";
    }
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_grid_json.php", "w") as f:
    f.write(verify_php)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_grid_json.php && rm test_grid_json.php")
print(f"OUT:\n{out}")

client.close()
