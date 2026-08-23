import json
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from scripts.remote_ssh_helper import get_ssh_client, run_remote_cmd

def main():
    client = get_ssh_client()
    remote_base = "/home/highest-ye/htdocs/highest-ye.store"
    
    script = """<?php
$projDir = '/home/highest-ye/htdocs/highest-ye.store';
require $projDir . '/vendor/autoload.php';
$app = require_once $projDir . '/bootstrap/app.php';
$kernel = $app->make(\\Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

$appKey = env('ALIEXPRESS_APP_KEY');
$envSecret = env('ALIEXPRESS_APP_SECRET');

$client = new \\App\\Services\\AliExpress\\AliExpressApiClient($appKey, $envSecret);
$token = app(\\App\\Services\\AliExpress\\AliExpressOAuthService::class)->latestToken();

DB::table('aliexpress_settings')->where('id', 1)->update(['app_key' => null, 'app_secret' => null]);
$appKey = config('aliexpress.app_key');
$appSecret = config('aliexpress.app_secret');
echo "config app_key len: " . strlen((string)$appKey) . ", prefix: " . substr((string)$appKey, 0, 3) . "\n";
echo "config app_secret len: " . strlen((string)$appSecret) . "\n";

$body = $res['body']['aliexpress_ds_product_get_response']['result'] ?? $res['body'];
echo json_encode([
    'title' => $body['ae_item_base_info_dto']['subject'] ?? $body['item_title'] ?? $body['subject'] ?? null,
    'store' => $body['ae_store_info']['store_name'] ?? $body['store_info']['store_name'] ?? null,
    'skus' => $body['ae_item_sku_info_dtos']['ae_item_sku_info_d_t_o'] ?? null,
], JSON_PRETTY_PRINT);
"""
    sftp = client.open_sftp()
    with sftp.file('/tmp/inspect_prod.php', 'w') as f:
        f.write(script)
    sftp.close()
    
    code, out, err = run_remote_cmd(client, "php /tmp/inspect_prod.php && rm -f /tmp/inspect_prod.php")
    print(out)
    client.close()

if __name__ == '__main__':
    main()
