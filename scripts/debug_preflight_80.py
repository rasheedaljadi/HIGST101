import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Webkul\Procurement\Services\ProcurementSubmitService;

$service = app(ProcurementSubmitService::class);
$preflight = $service->preflightSupplierPurchaseOrder(80);
print_r($preflight);
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/debug_preflight_80.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 debug_preflight_80.php && rm debug_preflight_80.php")
print(f"OUTPUT:\n{out}")

client.close()
