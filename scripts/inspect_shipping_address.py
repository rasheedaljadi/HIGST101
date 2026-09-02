import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Webkul\\Procurement\\Gateways\\AliExpressOrderSubmissionGateway;
use Illuminate\\Support\\Facades\\DB;

$gateway = app(AliExpressOrderSubmissionGateway::class);
$address = $gateway->resolveWarehouseShippingAddress();

echo "=========================================================\\n";
echo "RESOLVED SHIPPING ADDRESS PAYLOAD FOR ALIEXPRESS API:\\n";
echo "=========================================================\\n";
print_r($address);

$warehouse = DB::table('inventory_sources')->where('code', 'default')->first();
echo "\\n=========================================================\\n";
echo "INVENTORY SOURCE (DEFAULT WAREHOUSE) RECORD:\\n";
echo "=========================================================\\n";
echo "Name: " . $warehouse->name . "\\n";
echo "Contact Name: " . $warehouse->contact_name . "\\n";
echo "Description (Meta): " . $warehouse->description . "\\n";
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_address_inspection.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_address_inspection.php && rm test_address_inspection.php")
print(f"CODE: {code}")
print(f"OUT:\n{out}")

client.close()
