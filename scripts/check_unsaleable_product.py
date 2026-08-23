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

use Illuminate\Support\Facades\DB;

$import = DB::table('aliexpress_product_imports')
    ->where('aliexpress_product_id', '1005010388364235')
    ->first();

echo "IMPORT RECORD:\n";
echo json_encode($import, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

if ($import && $import->product_id) {
    $product = DB::table('products')->where('id', $import->product_id)->first();
    echo "\nLOCAL PRODUCT:\n";
    echo json_encode($product, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    
    $productFlat = DB::table('product_flat')->where('product_id', $import->product_id)->first();
    if ($productFlat) {
        echo "\nPRODUCT FLAT (NAME/STATUS):\n";
        echo "Name: " . ($productFlat->name ?? 'N/A') . " | Status: " . ($productFlat->status ?? 'N/A') . " | Visible: " . ($productFlat->visible_individually ?? 'N/A') . "\n";
    }
}

// Check other imports with errors
echo "\n=== ALL IMPORTS WITH ERRORS ===\n";
$errImports = DB::table('aliexpress_product_imports')
    ->whereNotNull('error')
    ->where('error', '!=', '')
    ->get(['id', 'aliexpress_product_id', 'product_id', 'error', 'updated_at']);

foreach ($errImports as $ei) {
    echo "ID: {$ei->id} | AE_ID: {$ei->aliexpress_product_id} | Product_ID: {$ei->product_id} | Error: {$ei->error}\n";
}
'''

with sftp.file(f"{APP_DIR}/tmp_check_product_ae.php", "w") as f:
    f.write(php_script)
sftp.close()

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php tmp_check_product_ae.php && rm tmp_check_product_ae.php")
out = stdout.read().decode('utf-8', errors='replace').strip()
err = stderr.read().decode('utf-8', errors='replace').strip()

print("--- PRODUCT & IMPORT DETAILS ---")
print(out)
if err:
    print("--- ERROR ---")
    print(err)

client.close()
