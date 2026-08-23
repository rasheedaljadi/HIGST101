import json
import sys
from remote_ssh_helper import get_ssh_client, run_remote_cmd

# Ensure UTF-8 output encoding for Windows console
if sys.platform == 'win32':
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')
    sys.stderr.reconfigure(encoding='utf-8', errors='replace')

APP_PATH = '/home/highest-ye/htdocs/highest-ye.store'

REMOTE_CHECK_TOKEN_PHP = r"""<?php
require '/home/highest-ye/htdocs/highest-ye.store/vendor/autoload.php';
$app = require_once '/home/highest-ye/htdocs/highest-ye.store/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$hasTable = Illuminate\Support\Facades\Schema::hasTable('aliexpress_tokens');
$tokens = [];
if ($hasTable) {
    $rows = Illuminate\Support\Facades\DB::table('aliexpress_tokens')->get();
    foreach ($rows as $r) {
        $tokens[] = [
            'id' => $r->id,
            'user_nick' => $r->user_nick ?? 'N/A',
            'seller_id' => $r->seller_id ?? 'N/A',
            'expires_at' => $r->expires_at ?? 'N/A',
            'refresh_expires_at' => $r->refresh_expires_at ?? 'N/A',
            'has_access_token' => !empty($r->access_token),
            'has_refresh_token' => !empty($r->refresh_token),
            'created_at' => $r->created_at ?? 'N/A',
            'updated_at' => $r->updated_at ?? 'N/A',
        ];
    }
}

$oauthService = app(\App\Services\AliExpress\AliExpressOAuthService::class);
$authUrl = $oauthService->buildAuthorizationUrl('pilot_auth_check');

echo json_encode([
    'has_aliexpress_tokens_table' => $hasTable,
    'stored_tokens_count' => count($tokens),
    'stored_tokens_metadata' => $tokens,
    'oauth_configured' => $oauthService->isConfigured(),
    'redirect_uri' => $oauthService->resolveRedirectUri(),
    'auth_url_structure' => preg_replace('/app_key=[^&]+/', 'app_key=MASKED', $authUrl)
], JSON_PRETTY_PRINT);
"""

def main():
    client = get_ssh_client()
    print("\n=== Inspecting AliExpress OAuth & Stored Tokens on Remote Server ===")
    
    sftp = client.open_sftp()
    with sftp.file('/tmp/check_token.php', 'w') as f:
        f.write(REMOTE_CHECK_TOKEN_PHP)
    sftp.close()
    
    code_php, php_out, php_err = run_remote_cmd(client, f"cd {APP_PATH} && php /tmp/check_token.php")
    run_remote_cmd(client, "rm -f /tmp/check_token.php")
    
    if php_err:
        print(f"PHP STDERR:\n{php_err}")
        
    print("\n--- OAuth & Token Status ---")
    print(php_out)
    
    with open('scripts/live_oauth_token_check.json', 'w', encoding='utf-8') as f:
        f.write(php_out)
        
    client.close()

if __name__ == '__main__':
    main()
