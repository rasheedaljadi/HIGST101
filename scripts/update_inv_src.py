import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Update inventory_sources.default with the proven winning address
DB::table('inventory_sources')->where('code', 'default')->update([
    'name' => 'مستودع الرياض (العنوان الافتراضي)',
    'contact_name' => 'Mostafa Bamashmous',
    'contact_number' => '572124578',
    'country' => 'SA',
    'state' => 'Riyadh',
    'city' => 'Riyadh',
    'street' => '3455 Ahmad Bin Rushd St, Al Aziziyah, 7664',
    'postcode' => '14512',
    'updated_at' => now(),
]);

echo "Updated inventory_sources.default successfully!\n";

$updated = DB::table('inventory_sources')->where('code', 'default')->first();
print_r($updated);
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/update_inv_src.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 update_inv_src.php && rm update_inv_src.php")
print(f"OUTPUT:\n{out}")

client.close()
