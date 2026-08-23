import json
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from scripts.remote_ssh_helper import get_ssh_client, run_remote_cmd

def main():
    client = get_ssh_client()
    remote_base = "/home/highest-ye/htdocs/highest-ye.store"
    
    probe_php = """<?php
$projDir = '""" + remote_base + """';
require $projDir . '/vendor/autoload.php';
$app = require_once $projDir . '/bootstrap/app.php';
$kernel = $app->make(\\Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use App\\Services\\AliExpress\\AliExpressOAuthService;
use App\\Services\\AliExpress\\AliExpressApiClient;

$oauth = app(AliExpressOAuthService::class);
$token = $oauth->latestToken();
$apiClient = app(AliExpressApiClient::class);
$accessToken = $token->access_token;

$productId = '1005010378829324';
$skuId = '12000052207602660';

// Test freight query for SA with various parameters
$tests = [
    'F01_standard_sa' => [
        'query_delivery_req' => [
            'ship_to_country' => 'SA',
            'product_id' => $productId,
            'product_num' => 1,
            'selected_sku_id' => $skuId,
        ]
    ],
    'F02_sa_with_city' => [
        'query_delivery_req' => [
            'ship_to_country' => 'SA',
            'product_id' => $productId,
            'product_num' => 1,
            'selected_sku_id' => $skuId,
            'city' => 'Riyadh',
            'province' => 'Riyadh',
        ]
    ],
];

$results = [];
foreach ($tests as $k => $p) {
    try {
        $res = $apiClient->call('aliexpress.ds.freight.query', $accessToken, $p);
        $results[$k] = $res['body'] ?? $res;
    } catch (\\Throwable $e) {
        $results[$k] = ['error' => $e->getMessage()];
    }
}

echo json_encode($results, JSON_PRETTY_PRINT);
"""

    sftp = client.open_sftp()
    remote_script_path = f"{remote_base}/scripts/probe_freight_details.php"
    with sftp.open(remote_script_path, 'w') as f:
        f.write(probe_php)
    sftp.close()
    
    cmd = f"cd {remote_base} && php scripts/probe_freight_details.php"
    code, out, err = run_remote_cmd(client, cmd)
    print(f"\n--- Output ---\n{out}")
    
    run_remote_cmd(client, f"rm -f {remote_script_path}")
    client.close()

if __name__ == '__main__':
    main()
