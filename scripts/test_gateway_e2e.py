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
$skuAttr = '14:201447015#NO PAD';
$shippingService = 'CAINIAO_FULFILLMENT_STD';

$draft = new ExternalOrderDraft(
    supplierPurchaseOrderId: 9999,
    correlationKey: 'E2E-GATEWAY-SA-' . time(),
    items: [
        [
            'supplier_product_id' => $productId,
            'supplier_sku_id' => $skuId,
            'qty' => 1,
            'expected_unit_cost' => 45.13,
            'sku_attr' => $skuAttr,
            'logistics_service_name' => $shippingService,
        ],
    ],
    currencyCode: 'USD'
);

echo "=== EXECUTING PROCUREMENT GATEWAY submitUnpaid() FOR SAUDI ARABIA ===\n";
$result = $gateway->submitUnpaid($draft);

if ($result instanceof VerifiedExternalOrderCreated) {
    echo "🎉🎉🎉 FULL GATEWAY SUBMISSION TO SAUDI ARABIA SUCCEEDED! 🎉🎉🎉\n";
    echo "External Order ID: " . $result->externalOrderId . "\n";
    echo "Status: " . $result->status . "\n";
    echo "Placed At: " . $result->placedAt . "\n";
    echo "Summary Masked:\n" . json_encode($result->payloadMasked, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
} elseif ($result instanceof ExternalOrderSubmissionFailed) {
    echo "❌ Submission Failed:\n";
    echo "Error Code: " . $result->errorCode . "\n";
    echo "Error Msg: " . $result->errorMessageMasked . "\n";
    echo "Request ID: " . $result->providerRequestId . "\n";
} else {
    echo "Unknown Result Type:\n";
    print_r($result);
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_gateway_e2e.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_gateway_e2e.php && rm test_gateway_e2e.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
