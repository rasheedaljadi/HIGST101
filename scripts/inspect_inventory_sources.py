import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$sources = DB::table('inventory_sources')->get();
echo "Inventory Sources Count: " . $sources->count() . "\n";
foreach ($sources as $source) {
    echo "ID: {$source->id}, Code: {$source->code}, Name: {$source->name}\n";
    echo "  Contact Name: {$source->contact_name}, Number: {$source->contact_number}\n";
    echo "  Street: {$source->street}, City: {$source->city}, State: {$source->state}, Country: {$source->country}, Postcode: {$source->postcode}\n";
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/inspect_inventory_sources.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 inspect_inventory_sources.php && rm inspect_inventory_sources.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
