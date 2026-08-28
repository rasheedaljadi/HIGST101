import sys
import paramiko

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')

HOST = '76.13.79.242'
USER = 'highest-ye'
PASS = 'YoK2PBV1fo82yujX2tDq'
APP_DIR = '/home/highest-ye/htdocs/highest-ye.store'

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(HOST, username=USER, password=PASS, timeout=20)

php_code = """<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use App\\Models\\AliExpressProductImport;

$import = AliExpressProductImport::where('product_id', 3625)->first();
if ($import) {
    echo "Payload Variants:\n";
    echo json_encode($import->payload_snapshot['variants'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
}
"""

sftp = client.open_sftp()
with sftp.file(f'{APP_DIR}/inspect_payload_var_tmp.php', 'w') as f:
    f.write(php_code)
sftp.close()

stdin, stdout, stderr = client.exec_command(f'cd {APP_DIR} && php inspect_payload_var_tmp.php && rm -f inspect_payload_var_tmp.php')
print(stdout.read().decode('utf-8'))
print(stderr.read().decode('utf-8'))
client.close()
