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
client.connect(HOST, username=USER, password=PASS, timeout=15)

script = """<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

$cols = Illuminate\\Support\\Facades\\Schema::getColumnListing('product_flat');
echo "Columns in product_flat (" . count($cols) . "):\\n";
echo implode(', ', $cols) . "\\n";

echo "\\nChecking if 'cost' exists in product_flat: " . (in_array('cost', $cols) ? 'YES' : 'NO') . "\\n";
"""

sftp = client.open_sftp()
with sftp.file(f"{APP_DIR}/check_cols.php", 'w') as f:
    f.write(script)
sftp.close()

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php check_cols.php && rm check_cols.php")
print(stdout.read().decode('utf-8', errors='replace'))

client.close()
