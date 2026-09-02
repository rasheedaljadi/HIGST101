import remote_ssh_helper as r

client = r.get_ssh_client()

local_gateway = r"e:\HIGESTO NEW1\higest\higest101\packages\Webkul\Procurement\src\Gateways\AliExpressOrderSubmissionGateway.php"
remote_gateway = "/home/highest-ye/htdocs/highest-ye.store/packages/Webkul/Procurement/src/Gateways/AliExpressOrderSubmissionGateway.php"

print(f"Uploading {local_gateway} to {remote_gateway}...")
sftp = client.open_sftp()
sftp.put(local_gateway, remote_gateway)
sftp.close()
print("Upload successful!")

# Run test on remote
php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Webkul\Procurement\Models\SupplierPurchaseOrder;
use Webkul\Procurement\Services\ProcurementSubmitService;
use Webkul\Procurement\Gateways\AliExpressOrderSubmissionGateway;

$spo = SupplierPurchaseOrder::with('items')->findOrFail(98);
$submitService = app(ProcurementSubmitService::class);
$gateway = app(AliExpressOrderSubmissionGateway::class);

echo "=== 1. Testing Preflight on SPO #98 with new localized error mapping ===\n";
$preflight = $submitService->preflightSupplierPurchaseOrder(98);
echo "Preflight isSuccess: " . ($preflight->isSuccess ? 'true' : 'false') . "\n";
echo "Preflight ErrorCode: " . $preflight->errorCode . "\n";
echo "Preflight Localized ErrorMessage (Arabic):\n" . $preflight->errorMessage . "\n";

echo "\n=== 2. Testing submitUnpaid on SPO #98 with new localized error mapping ===\n";
$reflection = new \ReflectionClass($submitService);
$buildDraftMethod = $reflection->getMethod('buildOrderDraft');
$buildDraftMethod->setAccessible(true);
$draft = $buildDraftMethod->invoke($submitService, $spo, 'DIAG-ARABIC-TEST-' . time());

$result = $gateway->submitUnpaid($draft);
echo "Result Class: " . get_class($result) . "\n";
if ($result instanceof \Webkul\Procurement\DTO\ExternalOrderSubmissionFailed) {
    echo "ErrorCode: " . $result->errorCode . "\n";
    echo "Localized Error Message (Arabic):\n" . $result->errorMessageMasked . "\n";
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_arabic_error_output.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php test_arabic_error_output.php && rm test_arabic_error_output.php")
print(f"\nOUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

# Clear opcache / cache on remote
r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php artisan optimize:clear")

client.close()
