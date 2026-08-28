import remote_ssh_helper as r

client = r.get_ssh_client()
php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$points = DB::table('delivery_points')->get();
echo "Total Delivery Points in DB: " . $points->count() . "\n";
foreach ($points as $p) {
    echo "ID: {$p->id} | Code: {$p->code} | Name: {$p->name} | Name AR: {$p->name_ar} | State: {$p->state_code} | City: {$p->city} | Active: {$p->is_active}\n";
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_dp.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_dp.php && rm test_dp.php")
print(f"OUT:\n{out}")
client.close()
