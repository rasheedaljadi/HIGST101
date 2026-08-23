import paramiko

HOST = '76.13.79.242'
USER = 'highest-ye'
PASS = 'YoK2PBV1fo82yujX2tDq'
APP_DIR = '/home/highest-ye/htdocs/highest-ye.store'

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(HOST, username=USER, password=PASS, timeout=15)
sftp = client.open_sftp()

php_script = r'''<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\AliExpressProductImport;
use App\Services\AliExpress\AliExpressProductSyncer;

$import = AliExpressProductImport::where('aliexpress_product_id', '1005010388364235')->first();
if (!$import) {
    echo "Import not found.\n";
    exit(0);
}

echo "Testing sync for import ID: {$import->id} (Local Product: {$import->product_id})...\n";
$syncer = app(AliExpressProductSyncer::class);

try {
    $syncer->sync($import);
    echo "SUCCESS: Syncer executed without throwing unhandled exception.\n";
    $import->refresh();
    echo "New Import Status: {$import->status}\n";
    echo "New Import Error: {$import->error}\n";
} catch (\Throwable $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
}
'''

with sftp.file(f"{APP_DIR}/tmp_test_sync_single.php", "w") as f:
    f.write(php_script)
sftp.close()

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php tmp_test_sync_single.php && rm tmp_test_sync_single.php")
out = stdout.read().decode('utf-8', errors='replace').strip()
err = stderr.read().decode('utf-8', errors='replace').strip()

print("--- SYNC TEST OUTPUT ---")
print(out)
if err:
    print("--- ERROR ---")
    print(err)

client.close()
