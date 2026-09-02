import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Webkul\Procurement\Models\SupplierPurchaseOrder;
use Webkul\Procurement\Services\ProcurementSubmitService;
use Webkul\Procurement\Gateways\AliExpressOrderSubmissionGateway;
use Webkul\Procurement\Contracts\AliExpressAuthorizationContextResolver;
use App\Services\AliExpress\AliExpressApiClient;

$spo = SupplierPurchaseOrder::with('items')->findOrFail(98);
$submitService = app(ProcurementSubmitService::class);
$gateway = app(AliExpressOrderSubmissionGateway::class);
$authResolver = app(AliExpressAuthorizationContextResolver::class);
$apiClient = app(AliExpressApiClient::class);

$reflection = new \ReflectionClass($submitService);
$buildDraftMethod = $reflection->getMethod('buildOrderDraft');
$buildDraftMethod->setAccessible(true);
$draft = $buildDraftMethod->invoke($submitService, $spo, 'DIAG-' . $spo->purchase_order_number);

echo "=== Calling submitUnpaid ===\n";
$result = $gateway->submitUnpaid($draft);

echo "Result Class: " . get_class($result) . "\n";
if ($result instanceof \Webkul\Procurement\DTO\ExternalOrderSubmissionFailed) {
    echo "ErrorCode: " . ($result->errorCode ?? 'N/A') . "\n";
    echo "ErrorMessage: " . ($result->errorMessageMasked ?? 'N/A') . "\n";
    echo "ProviderRequestId: " . ($result->providerRequestId ?? 'N/A') . "\n";
    echo "RetryClassification: " . ($result->retryClassification ?? 'N/A') . "\n";
    echo "RawResponse:\n";
    echo json_encode($result->rawResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
} elseif ($result instanceof \Webkul\Procurement\DTO\VerifiedExternalOrderCreated) {
    echo "SUCCESS! ExternalOrderId: {$result->externalOrderId}\n";
    echo "Status: {$result->providerStatus}\n";
    echo "Metadata:\n";
    echo json_encode($result->responseMetadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/diagnose_spo_live_call2.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php diagnose_spo_live_call2.php && rm diagnose_spo_live_call2.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
