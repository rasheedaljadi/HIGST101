import json
import sys
from remote_ssh_helper import get_ssh_client, run_remote_cmd

# Ensure UTF-8 output encoding for Windows console
if sys.platform == 'win32':
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')
    sys.stderr.reconfigure(encoding='utf-8', errors='replace')

APP_PATH = '/home/highest-ye/htdocs/highest-ye.store'

REMOTE_INTROSPECTION_PHP = r"""<?php
require '/home/highest-ye/htdocs/highest-ye.store/vendor/autoload.php';
$app = require_once '/home/highest-ye/htdocs/highest-ye.store/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tokenRow = App\Models\AliExpressToken::orderBy('id', 'desc')->first();

if (!$tokenRow) {
    echo json_encode(['error' => 'No token found in database']);
    exit;
}

$token = $tokenRow->access_token;
$client = app(App\Services\AliExpress\AliExpressApiClient::class);

// Read-only introspection: try to query a known product or check method availability
// We do NOT create any order or financial transaction.
// Let's test a harmless read-only method, e.g. aliexpress.ds.product.get for a test product ID if available
$readResult = $client->call('aliexpress.ds.product.get', $token, [
    'product_id' => '1005006248443977',
    'ship_to_country' => 'SA'
]);

echo json_encode([
    'token_id' => $tokenRow->id,
    'seller_id' => $tokenRow->seller_id,
    'token_updated_at' => (string)$tokenRow->updated_at,
    'api_call_ok' => $readResult['ok'],
    'api_status_code' => $readResult['status'],
    'api_error_code' => $readResult['code'] ?? null,
    'api_error_message' => $readResult['message'] ?? null,
    'response_summary' => [
        'has_body' => !empty($readResult['body']),
        'body_keys' => array_keys($readResult['body'] ?? [])
    ]
], JSON_PRETTY_PRINT);
"""

def main():
    client = get_ssh_client()
    print("\n=== Testing Live AliExpress API Introspection (Read-Only) ===")
    
    sftp = client.open_sftp()
    with sftp.file('/tmp/test_ae_introspection.php', 'w') as f:
        f.write(REMOTE_INTROSPECTION_PHP)
    sftp.close()
    
    code_php, php_out, php_err = run_remote_cmd(client, f"cd {APP_PATH} && php /tmp/test_ae_introspection.php")
    run_remote_cmd(client, "rm -f /tmp/test_ae_introspection.php")
    
    if php_err:
        print(f"PHP STDERR:\n{php_err}")
        
    print("\n--- API Introspection Result ---")
    print(php_out)
    
    with open('scripts/live_ae_introspection_result.json', 'w', encoding='utf-8') as f:
        f.write(php_out)
        
    client.close()

if __name__ == '__main__':
    main()
