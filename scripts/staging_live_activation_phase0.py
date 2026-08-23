import json
import sys
from remote_ssh_helper import get_ssh_client, run_remote_cmd

# Ensure UTF-8 output encoding for Windows console
if sys.platform == 'win32':
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')
    sys.stderr.reconfigure(encoding='utf-8', errors='replace')

APP_PATH = '/home/highest-ye/htdocs/highest-ye.store'
EXPECTED_SHA = '11eeeeb088f2cd5ef2ce3ac2cd9d5bcb4a5bec92'

REMOTE_PHASE0_PHP = r"""<?php
require '/home/highest-ye/htdocs/highest-ye.store/vendor/autoload.php';
$app = require_once '/home/highest-ye/htdocs/highest-ye.store/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$aeConfig = config('aliexpress', []);
$procConfig = config('procurement', []);

$clientClass = 'App\Services\AliExpress\AliExpressApiClient';
$hasClient = class_exists($clientClass);
$methods = $hasClient ? get_class_methods($clientClass) : [];

$sources = Illuminate\Support\Facades\DB::table('inventory_sources')->get(['code', 'name', 'status']);

$hasPlat = Illuminate\Support\Facades\Schema::hasTable('external_platform_orders');
$platCount = $hasPlat ? Illuminate\Support\Facades\DB::table('external_platform_orders')->count() : 0;
$hasSpo = Illuminate\Support\Facades\Schema::hasTable('supplier_purchase_orders');
$spoCount = $hasSpo ? Illuminate\Support\Facades\DB::table('supplier_purchase_orders')->count() : 0;
$hasDemands = Illuminate\Support\Facades\Schema::hasTable('procurement_demands');
$demandsCount = $hasDemands ? Illuminate\Support\Facades\DB::table('procurement_demands')->count() : 0;

echo json_encode([
    'procurement_v2_enabled' => config('procurement.v2_enabled', false),
    'procurement_polling_enabled' => config('procurement.polling.enabled', false),
    'procurement_keys' => array_keys($procConfig),
    'aliexpress_keys' => array_keys($aeConfig),
    'aliexpress_has_app_key' => !empty(config('aliexpress.app_key')),
    'aliexpress_has_app_secret' => !empty(config('aliexpress.app_secret')),
    'aliexpress_has_access_token' => !empty(config('aliexpress.access_token')),
    'aliexpress_environment' => config('aliexpress.environment', 'sandbox'),
    'aliexpress_callback_url' => config('aliexpress.callback_url', ''),
    'queue_connection' => config('queue.default'),
    'app_debug' => config('app.debug'),
    'app_env' => config('app.env'),
    'ae_client_methods' => $methods,
    'inventory_sources' => $sources,
    'counts' => [
        'external_platform_orders' => $platCount,
        'supplier_purchase_orders' => $spoCount,
        'procurement_demands' => $demandsCount
    ]
], JSON_PRETTY_PRINT);
"""

def main():
    client = get_ssh_client()
    print("\n=== PHASE 0: Pre-Activation Audit & Health Introspection (Read-Only) ===")
    
    # 1. Verify Git HEAD and clean status
    _, current_sha, _ = run_remote_cmd(client, f"cd {APP_PATH} && git rev-parse HEAD")
    _, branch_name, _ = run_remote_cmd(client, f"cd {APP_PATH} && git branch --show-current")
    
    print(f"Server Git HEAD: {current_sha} (Expected: {EXPECTED_SHA})")
    print(f"Server Git Branch: {branch_name}")
    
    # Upload remote runner script to /tmp/phase0_introspection.php
    sftp = client.open_sftp()
    with sftp.file('/tmp/phase0_introspection.php', 'w') as f:
        f.write(REMOTE_PHASE0_PHP)
    sftp.close()
    
    code_php, php_out, php_err = run_remote_cmd(client, f"cd {APP_PATH} && php /tmp/phase0_introspection.php")
    run_remote_cmd(client, "rm -f /tmp/phase0_introspection.php")
    
    if php_err:
        print(f"PHP STDERR:\n{php_err}")
        
    print("\n--- Introspection Results ---")
    print(php_out)
    
    with open('scripts/live_activation_phase0_result.json', 'w', encoding='utf-8') as f:
        f.write(php_out)
        
    client.close()

if __name__ == '__main__':
    main()
