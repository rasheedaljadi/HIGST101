import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Webkul\Procurement\Services\ProcurementSubmitService;

$service = app(ProcurementSubmitService::class);

echo "Calling submitBatch(72)...\n";
try {
    $batch = $service->submitBatch(72, 1);
    echo "Batch submitted. State: " . $batch->state . "\n";
} catch (\Throwable $e) {
    echo "Caught: " . $e->getMessage() . "\n";
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/run_submit_72.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 run_submit_72.php && rm run_submit_72.php")
print(f"OUTPUT:\n{out}")

client.close()
