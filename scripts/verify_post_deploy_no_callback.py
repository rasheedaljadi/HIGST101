import json
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from scripts.remote_ssh_helper import get_ssh_client, run_remote_cmd

def main():
    client = get_ssh_client()
    remote_base = "/home/highest-ye/htdocs/highest-ye.store"
    
    post_verify_php = """<?php
$projDir = '/home/highest-ye/htdocs/highest-ye.store';
require $projDir . '/vendor/autoload.php';
$app = require_once $projDir . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\AliExpressSetting;
use App\Services\AliExpress\AliExpressWebhookSignatureVerifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$setting = AliExpressSetting::current();
$appKey = (string) ($setting->app_key ?? 'testkey');
$appSecret = (string) ($setting->app_secret ?? 'testsecret');

$verifier = new AliExpressWebhookSignatureVerifier();

// 1. Test Valid Signature Fixture (No external call)
$testBody = '{"message_type":53,"seller_id":"200042360","data":{"trade_order_id":"8201948572910482"}}';
$validSig = hash_hmac('sha256', $appKey . $testBody, $appSecret);
$validReq = Request::create('aliexpress/webhook', 'POST', [], [], [], [
    'HTTP_AUTHORIZATION' => $validSig,
    'CONTENT_TYPE' => 'application/json',
], $testBody);
$validResult = $verifier->verify($validReq);

// 2. Test Invalid Signature Fixture
$invalidReq = Request::create('aliexpress/webhook', 'POST', [], [], [], [
    'HTTP_AUTHORIZATION' => 'invalid_signature_hex_123',
    'CONTENT_TYPE' => 'application/json',
], $testBody);
$invalidResult = $verifier->verify($invalidReq);

// 3. Test Missing Signature Fixture
$missingReq = Request::create('aliexpress/webhook', 'POST', [], [], [], [
    'CONTENT_TYPE' => 'application/json',
], $testBody);
$missingResult = $verifier->verify($missingReq);

// 4. Baseline Counts Check After Migration
$tables = [
    'external_platform_orders',
    'supplier_purchase_orders',
    'procurement_batches',
    'procurement_demands',
    'procurement_demand_allocations',
    'procurement_cost_snapshots',
    'procurement_audit_logs',
    'aliexpress_webhook_inbox_messages'
];
$counts = [];
foreach ($tables as $t) {
    $counts[$t] = Schema::hasTable($t) ? DB::table($t)->count() : null;
}

$output = [
    'signature_verifier_valid_test' => $validResult,
    'signature_verifier_invalid_test' => $invalidResult,
    'signature_verifier_missing_test' => $missingResult,
    'signature_logic_secure' => ($validResult === true && $invalidResult === false && $missingResult === false),
    'inbox_table_empty' => ($counts['aliexpress_webhook_inbox_messages'] === 0),
    'post_counts' => $counts,
];

echo json_encode($output, JSON_PRETTY_PRINT);
"""
    sftp = client.open_sftp()
    with sftp.file('/tmp/post_deploy_verify.php', 'w') as f:
        f.write(post_verify_php)
    sftp.close()
    
    code, verify_out, err = run_remote_cmd(client, "php /tmp/post_deploy_verify.php")
    print("=== POST DEPLOY LOGIC VERIFICATION ===")
    print(verify_out)
    
    code, route_out, err = run_remote_cmd(client, f"cd {remote_base} && php artisan route:list --path=aliexpress")
    print("=== ROUTE LIST ALIEXPRESS ===")
    print(route_out)
    
    client.close()

if __name__ == '__main__':
    main()
