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
$skuId = '12000053357140815'; // Variant: Black on White

$correlationKey = 'HIGEST_TEST_' . time();
$draft = new ExternalOrderDraft(
    supplierPurchaseOrderId: 999901,
    correlationKey: $correlationKey,
    items: [
        [
            'supplier_product_id' => $productId,
            'supplier_sku_id' => $skuId,
            'qty' => 1,
            'expected_unit_cost' => 44.79,
        ]
    ],
    currencyCode: 'USD'
);

echo "Submitting Test Order to AliExpress via submitUnpaid()...\n";
echo "Correlation Key (out_order_id): {$correlationKey}\n";

$result = $gateway->submitUnpaid($draft);

if ($result instanceof VerifiedExternalOrderCreated) {
    echo "\n=== ORDER CREATION SUCCESSFUL ===\n";
    echo "Official External Order ID: {$result->externalOrderId}\n";
    echo "Provider Status: {$result->providerStatus}\n";
    echo "Provider Request ID: {$result->providerRequestId}\n";
    echo "Metadata:\n";
    print_r($result->responseMetadata);
} elseif ($result instanceof ExternalOrderSubmissionFailed) {
    echo "\n=== ORDER CREATION FAILED ===\n";
    echo "Error Code: {$result->errorCode}\n";
    echo "Error Message: {$result->errorMessageMasked}\n";
    echo "Retry Classification: {$result->retryClassification}\n";
    echo "Raw Response:\n";
    print_r($result->rawResponse);
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_order_create.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_order_create.php && rm test_order_create.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
