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

use App\\Services\\AliExpress\\AliExpressApiClient;
use Webkul\\Procurement\\Contracts\\AliExpressAuthorizationContextResolver;

$externalOrderId = '1122474765781333';

$authResolver = app(AliExpressAuthorizationContextResolver::class);
$auth = $authResolver->resolveForDropshipperSubmission(null);

$apiClient = app(AliExpressApiClient::class);
$response = $apiClient->call('aliexpress.trade.ds.order.get', $auth->accessToken, [
    'single_order_query' => json_encode(['order_id' => $externalOrderId]),
]);

echo "=== ALIEXPRESS LIVE ORDER DETAILS ===\n";
echo "OK: " . ($response['ok'] ? 'true' : 'false') . "\n";
echo json_encode($response['body'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
"""

sftp = client.open_sftp()
with sftp.file(f'{APP_DIR}/query_ae_live_order.php', 'w') as f:
    f.write(php_code)
sftp.close()

stdin, stdout, stderr = client.exec_command(f'cd {APP_DIR} && php query_ae_live_order.php && rm -f query_ae_live_order.php')
print(stdout.read().decode('utf-8'))
print(stderr.read().decode('utf-8'))
client.close()
