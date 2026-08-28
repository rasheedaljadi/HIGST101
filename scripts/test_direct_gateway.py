import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Webkul\Procurement\Gateways\AliExpressOrderSubmissionGateway;
use Webkul\Procurement\Services\ProcurementSubmitService;
use Webkul\Procurement\Models\SupplierPurchaseOrder;

$service = app(ProcurementSubmitService::class);
$gateway = app(AliExpressOrderSubmissionGateway::class);

$spo = SupplierPurchaseOrder::with('items')->find(80);
$draft = (new \ReflectionClass($service))->getMethod('buildOrderDraft')->invoke($service, $spo, $spo->purchase_order_number);

echo "Draft Items:\n";
print_r($draft->items);

echo "\nCalling gateway->submitUnpaid()...\n";
$result = $gateway->submitUnpaid($draft);
echo "Gateway Result Class: " . get_class($result) . "\n";
print_r($result);
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_direct_gateway.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_direct_gateway.php && rm test_direct_gateway.php")
print(f"OUTPUT:\n{out}")

client.close()
