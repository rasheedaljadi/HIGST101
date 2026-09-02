import remote_ssh_helper as r

client = r.get_ssh_client()
sftp = client.open_sftp()

php_script = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

$columns = Schema::getColumnListing('aliexpress_settings');
echo "Columns in aliexpress_settings:\\n";
print_r($columns);

$settings = DB::table('aliexpress_settings')->first();
echo "\\nCurrent Row in aliexpress_settings:\\n";
print_r($settings);
"""

with sftp.file("/home/highest-ye/htdocs/highest-ye.store/inspect_ae_settings.php", "w") as f:
    f.write(php_script)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 inspect_ae_settings.php && rm inspect_ae_settings.php")
print(f"CODE: {code}")
print(f"OUT:\n{out}")

client.close()
