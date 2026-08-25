import sys
import paramiko
import json

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')

HOST = '76.13.79.242'
USER = 'highest-ye'
PASS = 'YoK2PBV1fo82yujX2tDq'
APP_DIR = '/home/highest-ye/htdocs/highest-ye.store'

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(HOST, username=USER, password=PASS, timeout=20)
sftp = client.open_sftp()

php_script = """<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use App\\Models\\AliExpressToken;
use App\\Services\\AliExpress\\AliExpressApiClient;

$token = AliExpressToken::latest()->first();
$apiClient = app(AliExpressApiClient::class);

$aeOrderId = '1122476304621333';

$res = $apiClient->call('aliexpress.trade.ds.order.get', $token->access_token, [
    'single_order_query' => json_encode(['order_id' => $aeOrderId]),
]);

echo "=== ALIEXPRESS ORDER GET RESULT ===\\n";
echo json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\\n";
"""

with sftp.open(f"{APP_DIR}/inspect_ae_order_live.php", 'w') as f:
    f.write(php_script)
sftp.close()

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php inspect_ae_order_live.php && rm inspect_ae_order_live.php")
print(stdout.read().decode('utf-8', errors='replace'))
client.close()
