import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Webkul\\Procurement\\Services\\ProcurementSubmitService;
use Webkul\\Procurement\\Models\\ProcurementBatch;
use Webkul\\User\\Models\\Admin;

$admin = Admin::first();
if ($admin) {
    auth()->guard('admin')->setUser($admin);
}

$submitService = app(ProcurementSubmitService::class);
$batch = ProcurementBatch::find(84);

echo "=========================================================\\n";
echo "TESTING BATCH #84 SUBMISSION SERVICE EXECUTION\\n";
echo "=========================================================\\n";
echo "Current Batch State: " . $batch->state . "\\n";

try {
    $res = $submitService->submitBatch(84, $admin->id ?? 1);
    echo "Submit Executed Successfully! New Batch State: " . $res->state . "\\n";
} catch (\\Throwable $e) {
    echo "Caught Exception (Localized): " . $e->getMessage() . "\\n";
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_batch_submit_run.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_batch_submit_run.php && rm test_batch_submit_run.php")
print(f"CODE: {code}")
print(f"OUT:\n{out}")

client.close()
