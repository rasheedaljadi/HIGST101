import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Webkul\Procurement\Gateways\AliExpressOrderSubmissionGateway;
use Webkul\Procurement\DTO\ExternalOrderDraft;
use Webkul\Procurement\DTO\VerifiedExternalOrderCreated;
use Webkul\Procurement\DTO\ExternalOrderSubmissionFailed;

$gateway = app(AliExpressOrderSubmissionGateway::class);

$productId = '1005010737996063';
$skuId = '12000053357140815';

$draft = new ExternalOrderDraft(
    supplierPurchaseOrderId: 9999,
    correlationKey: 'TEST-GATEWAY-ACTIVE-' . time(),
    items: [
        [
            'supplier_product_id' => $productId,
            'supplier_sku_id' => $skuId,
            'qty' => 1,
            'expected_unit_cost' => 45.13,
            'sku_attr' => '14:201447015#NO PAD',
            'logistics_service_name' => 'CAINIAO_FULFILLMENT_STD',
        ],
    ],
    currencyCode: 'USD'
);

echo "Calling gateway->submitUnpaid() for active product {$productId}...\n";
$result = $gateway->submitUnpaid($draft);
echo "Gateway Result Class: " . get_class($result) . "\n";
print_r($result);
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_active_po.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_active_po.php && rm test_active_po.php")
print(f"OUTPUT:\n{out}")

client.close()
