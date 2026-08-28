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

use App\\Models\\ExternalVariantProjection;

echo "=== PRODUCTION SYSTEM STATUS ===\n";
echo "Total External Variant Projections Mapped: " . ExternalVariantProjection::count() . "\n";
echo "Config Procurement Max Cost Variance: " . config('procurement.max_cost_variance_percent', 'Not set') . "%\n";
echo "Config Procurement V2 Live Order Creation: " . (config('procurement.v2_live_order_creation_enabled') ? 'ENABLED' : 'DISABLED') . "\n";
echo "Config Procurement Polling: " . (config('procurement.polling.enabled') ? 'ENABLED' : 'DISABLED') . "\n";
"""

sftp = client.open_sftp()
with sftp.file(f'{APP_DIR}/verify_prod_status.php', 'w') as f:
    f.write(php_code)
sftp.close()

stdin, stdout, stderr = client.exec_command(f'cd {APP_DIR} && php verify_prod_status.php && rm -f verify_prod_status.php')
print(stdout.read().decode('utf-8'))
print(stderr.read().decode('utf-8'))
client.close()
