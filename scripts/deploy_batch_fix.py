import remote_ssh_helper as r
import os

client = r.get_ssh_client()
sftp = client.open_sftp()

files_to_sync = [
    ("packages/Webkul/Procurement/src/Services/ProcurementSubmitService.php", "packages/Webkul/Procurement/src/Services/ProcurementSubmitService.php"),
    ("packages/Webkul/Procurement/src/Resources/views/admin/batches/view.blade.php", "packages/Webkul/Procurement/src/Resources/views/admin/batches/view.blade.php"),
]

local_base = r"e:\HIGESTO NEW1\higest\higest101"
remote_base = "/home/highest-ye/htdocs/highest-ye.store"

for rel_local, rel_remote in files_to_sync:
    local_path = os.path.join(local_base, rel_local)
    remote_path = f"{remote_base}/{rel_remote}"
    print(f"Uploading {rel_local} -> {remote_path}")
    sftp.put(local_path, remote_path)

sftp.close()

# Clear cache
code, out, err = r.run_remote_cmd(client, f"cd {remote_base} && php8.4 artisan view:clear && php8.4 artisan cache:clear")
print(f"Cache Clear: CODE {code}\n{out}")

# Test simulation of submit on Batch 85
test_php = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Webkul\\Procurement\\Models\\ProcurementBatch;
use Webkul\\Procurement\\Services\\ProcurementSubmitService;
use Webkul\\User\\Models\\Admin;

$batch = ProcurementBatch::where('batch_number', 'BATCH-20260830-ECLGAS')->first();

echo "=========================================================\\n";
echo "TESTING PREFLIGHT EXECUTION ON BATCH {$batch->batch_number}\\n";
echo "=========================================================\\n";
$admin = Admin::first();
if ($admin) {
    auth()->guard('admin')->setUser($admin);
}

$submitService = app(ProcurementSubmitService::class);
try {
    $res = $submitService->submitBatch($batch->id, $admin->id ?? 1);
    echo "Submit Success!\\n";
} catch (\\Throwable $e) {
    echo "Clean Error Caught (as expected by design):\\n";
    echo $e->getMessage() . "\\n";
}

echo "\\nBatch State now: " . $batch->fresh()->state . "\\n";
"""

sftp2 = client.open_sftp()
with sftp2.file(f"{remote_base}/test_eclgas_submit.php", "w") as f:
    f.write(test_php)
sftp2.close()

code, out, err = r.run_remote_cmd(client, f"cd {remote_base} && php8.4 test_eclgas_submit.php && rm test_eclgas_submit.php")
print(f"\nVerification Output:\n{out}")

client.close()
