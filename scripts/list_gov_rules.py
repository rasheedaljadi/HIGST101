import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$rules = DB::table('delivery_governorate_rules')->orderBy('state_code')->get();
echo "=== DELIVERY GOVERNORATE RULES (" . $rules->count() . " Total) ===\n";
foreach ($rules as $r) {
    echo sprintf(
        "State: %-4s | Type: %-15s | Enabled: %d | Fee: %6.2f | Min: %6.2f | Methods: %s\n",
        $r->state_code,
        $r->delivery_type,
        $r->is_enabled,
        $r->delivery_fee,
        $r->min_order_amount,
        $r->allowed_payment_methods
    );
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_list_rules.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_list_rules.php && rm test_list_rules.php")
print(f"OUTPUT:\n{out}")
client.close()
