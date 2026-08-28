import remote_ssh_helper as r

client = r.get_ssh_client()
sftp = client.open_sftp()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "Columns in offline_payment_destinations:\n";
print_r(Schema::getColumnListing('offline_payment_destinations'));

echo "\nRows in offline_payment_destinations:\n";
print_r(DB::table('offline_payment_destinations')->get()->toArray());

echo "\nColumns in offline_payment_accounts:\n";
print_r(Schema::getColumnListing('offline_payment_accounts'));

echo "\nRows in offline_payment_accounts:\n";
print_r(DB::table('offline_payment_accounts')->get()->toArray());
"""

with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_tables.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_tables.php && rm test_tables.php")
print(f"CODE: {code}")
print(f"OUT:\n{out}")

client.close()
