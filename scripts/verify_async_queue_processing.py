import json
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from scripts.remote_ssh_helper import get_ssh_client, run_remote_cmd

def main():
    client = get_ssh_client()
    remote_base = "/home/highest-ye/htdocs/highest-ye.store"
    
    test_php = """<?php
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
use Webkul\Procurement\Jobs\ProcessAliExpressWebhookJob;
use Webkul\Procurement\Models\AliExpressWebhookInboxMessage;

$setting = AliExpressSetting::current();
$appKey = (string) ($setting->app_key ?? 'testkey');
$appSecret = (string) ($setting->app_secret ?? 'testsecret');

$verifier = new AliExpressWebhookSignatureVerifier();
$controller = new \App\Http\Controllers\AliExpress\AliExpressWebhookController($verifier);

// 1. Send an internal isolated payload (Type 65 - System notification, no external API call needed)
$testOrderId = '8201948572' . rand(100000, 999999);
$testBody = json_encode([
    'event_id' => 'EVT-TEST-ASYNC-' . uniqid(),
    'message_type' => 65,
    'timestamp' => time(),
    'payload' => ['notice' => 'System token expiry reminder test'],
]);
$sig = hash_hmac('sha256', $appKey . $testBody, $appSecret);

$req = Request::create('aliexpress/webhook', 'POST', [], [], [], [
    'HTTP_AUTHORIZATION' => $sig,
    'CONTENT_TYPE' => 'application/json',
], $testBody);

$startHttp = microtime(true);
$response = $controller->handle($req);
$httpDurationMs = round((microtime(true) - $startHttp) * 1000, 2);

$httpStatus = $response->getStatusCode();

echo json_encode([
    'http_status' => $httpStatus,
    'http_duration_ms' => $httpDurationMs,
    'jobs_in_queue_before_sleep' => DB::table('jobs')->where('queue', 'aliexpress-webhooks')->count(),
], JSON_PRETTY_PRINT);
"""
    sftp = client.open_sftp()
    with sftp.file('/tmp/test_async_queue.php', 'w') as f:
        f.write(test_php)
    sftp.close()
    
    code, out, err = run_remote_cmd(client, "php /tmp/test_async_queue.php && rm -f /tmp/test_async_queue.php")
    print("=== HTTP ENQUEUE TEST ===")
    print(out)
    
    # Wait for background queue worker to process the job
    code, sleep_out, err = run_remote_cmd(client, "sleep 3")
    
    # Check worker processing result and database baseline counts
    verify_post_php = """<?php
$projDir = '/home/highest-ye/htdocs/highest-ye.store';
require $projDir . '/vendor/autoload.php';
$app = require_once $projDir . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkul\Procurement\Models\AliExpressWebhookInboxMessage;

$latestProcessedInbox = AliExpressWebhookInboxMessage::where('event_type', 65)->latest('id')->first();

$tables = [
    'external_platform_orders',
    'supplier_purchase_orders',
    'procurement_batches',
    'procurement_demands',
    'procurement_demand_allocations',
    'procurement_cost_snapshots',
    'procurement_audit_logs',
    'jobs',
    'failed_jobs'
];
$counts = [];
foreach ($tables as $t) {
    $counts[$t] = Schema::hasTable($t) ? DB::table($t)->count() : null;
}

echo json_encode([
    'worker_processed_inbox_status' => $latestProcessedInbox?->status,
    'worker_processed_at' => $latestProcessedInbox?->processed_at?->toIso8601String(),
    'jobs_pending_count' => $counts['jobs'],
    'failed_jobs_count' => $counts['failed_jobs'],
    'post_counts' => $counts,
], JSON_PRETTY_PRINT);
"""
    sftp = client.open_sftp()
    with sftp.file('/tmp/verify_async_result.php', 'w') as f:
        f.write(verify_post_php)
    sftp.close()
    
    code, out2, err = run_remote_cmd(client, "php /tmp/verify_async_result.php && rm -f /tmp/verify_async_result.php")
    print("=== ASYNC WORKER EXECUTION RESULT ===")
    print(out2)
    
    # Test graceful restart
    code, restart_out, err = run_remote_cmd(client, f"cd {remote_base} && php artisan queue:restart")
    print("=== GRACEFUL QUEUE RESTART ===")
    print(restart_out.strip())
    
    code, service_status, err = run_remote_cmd(client, "systemctl --user status highest-queue-aliexpress-webhooks.service 2>&1 | head -n 12")
    print("=== SERVICE STATUS AFTER RESTART ===")
    print(service_status)
    
    client.close()

if __name__ == '__main__':
    main()
