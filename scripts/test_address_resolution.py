import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Webkul\Procurement\Gateways\AliExpressOrderSubmissionGateway;

// Update inventory source with the exact AliExpress address provided by user
DB::table('inventory_sources')
    ->where('code', 'default')
    ->update([
        'name' => 'عنوان علي إكسبرس الافتراضي',
        'contact_name' => 'Mostafa Mo Bamashmous',
        'contact_number' => '572124578',
        'contact_email' => 'mostafabama2006@gmail.com',
        'street' => 'حي العزيزية, الرياض, المملكة العربية السعودية',
        'city' => 'الرياض',
        'state' => 'منطقة الرياض',
        'country' => 'SA',
        'postcode' => 'RMAD3455',
        'status' => 1,
    ]);

$gateway = app(AliExpressOrderSubmissionGateway::class);
$address = $gateway->resolveWarehouseShippingAddress();

echo "Resolved Logistics Address for AliExpress API:\n";
print_r($address);
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_address_resolution.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_address_resolution.php && rm test_address_resolution.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
