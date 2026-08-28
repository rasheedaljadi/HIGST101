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

php_code = """<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use App\\Services\\AliExpress\\AliExpressApiClient;
use Webkul\\Procurement\\Contracts\\AliExpressAuthorizationContextResolver;

$authResolver = app(AliExpressAuthorizationContextResolver::class);
$auth = $authResolver->resolveForDropshipperSubmission(null);
$apiClient = app(AliExpressApiClient::class);

$freightReq = [
    'productId' => '1005010804755442',
    'shipToCountry' => 'SA',
    'quantity' => 1,
    'currency' => 'USD',
    'language' => 'en_US',
    'locale' => 'en_US',
    'selectedSkuId' => '12000053556265155',
];

$freightRes = $apiClient->call('aliexpress.ds.freight.query', $auth->accessToken, [
    'queryDeliveryReq' => $freightReq,
]);

$list = $freightRes['body']['aliexpress_ds_freight_query_response']['result']['aeop_freight_calculate_result_for_buyer_d_t_o_list']['aeop_freight_calculate_result_for_buyer_dto'] ?? [];
if (isset($list['service_name'])) $list = [$list];

echo "Freight Options Count: " . count($list) . "\n";
foreach ($list as $f) {
    echo "  Service: " . ($f['service_name'] ?? '') . " | Company: " . ($f['company_name'] ?? '') . " | Amount: $" . ($f['freight']['amount'] ?? '0.00') . " | Est: " . ($f['estimated_delivery_time'] ?? '') . " days\n";
}
"""

sftp = client.open_sftp()
with sftp.file(f'{APP_DIR}/inspect_cpu_freight2.php', 'w') as f:
    f.write(php_code)
sftp.close()

stdin, stdout, stderr = client.exec_command(f'cd {APP_DIR} && php inspect_cpu_freight2.php && rm -f inspect_cpu_freight2.php')
print(stdout.read().decode('utf-8'))
print(stderr.read().decode('utf-8'))
client.close()
