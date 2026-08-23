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

$params = [
    'order_id' => $orderId,
];

try {
    $res = $apiClient->call('aliexpress.ds.order.get', $accessToken, $params);
    echo json_encode($res['body'] ?? $res, JSON_PRETTY_PRINT);
} catch (\\Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
"""

    sftp = client.open_sftp()
    remote_script_path = f"{remote_base}/scripts/verify_order_get.php"
    with sftp.open(remote_script_path, 'w') as f:
        f.write(verify_php)
    sftp.close()
    
    try:
        cmd = f"cd {remote_base} && php scripts/verify_order_get.php"
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
