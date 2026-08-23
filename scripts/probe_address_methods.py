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

// Probe methods related to address and logistics
$methods = [
    'aliexpress.logistics.redefine.getlogisticsselleraddresses' => ['seller_address_query' => 'sender,pickup,refund'],
    'aliexpress.ds.product.get' => ['product_id' => '1005010378829324', 'ship_to_country' => 'SA', 'target_currency' => 'USD', 'target_language' => 'en'],
];

$results = [];
foreach ($methods as $m => $p) {
    try {
        $res = $apiClient->call($m, $accessToken, $p);
        $results[$m] = $res['body'] ?? $res;
    } catch (\\Throwable $e) {
        $results[$m] = ['error' => $e->getMessage()];
    }
}

echo json_encode($results, JSON_PRETTY_PRINT);
"""

    sftp = client.open_sftp()
    remote_script_path = f"{remote_base}/scripts/probe_address_methods.php"
    with sftp.open(remote_script_path, 'w') as f:
        f.write(probe_php)
    sftp.close()
    
    cmd = f"cd {remote_base} && php scripts/probe_address_methods.php"
    code, out, err = run_remote_cmd(client, cmd)
    print(f"\n--- Output ---\n{out}")
    
    run_remote_cmd(client, f"rm -f {remote_script_path}")
    client.close()

if __name__ == '__main__':
    main()
