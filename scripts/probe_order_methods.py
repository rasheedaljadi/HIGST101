import json
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from scripts.remote_ssh_helper import get_ssh_client, run_remote_cmd

def main():
    client = get_ssh_client()
    remote_base = "/home/highest-ye/htdocs/highest-ye.store"
    order_id = "1122360339411333"
    
    verify_php = """<?php
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

$orderId = '""" + order_id + """';

$methods = [
    'aliexpress.trade.ds.order.get' => ['single_order_query' => json_encode(['order_id' => $orderId])],
    'aliexpress.trade.order.get' => ['order_id' => $orderId],
    'aliexpress.ds.trade.order.get' => ['order_id' => $orderId],
    'aliexpress.solution.order.get' => ['single_order_query' => json_encode(['order_id' => $orderId])],
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
    remote_script_path = f"{remote_base}/scripts/probe_order_methods.php"
    with sftp.open(remote_script_path, 'w') as f:
        f.write(verify_php)
    sftp.close()
    
    try:
        cmd = f"cd {remote_base} && php scripts/probe_order_methods.php"
        code, out, err = run_remote_cmd(client, cmd)
        print(f"\n--- Order Query Result ---\n{out}")
    finally:
        try:
            run_remote_cmd(client, f"rm -f {remote_script_path}")
        except Exception:
            pass
        client.close()

if __name__ == '__main__':
    main()
