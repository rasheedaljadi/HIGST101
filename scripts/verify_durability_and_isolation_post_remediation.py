import json
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from scripts.remote_ssh_helper import get_ssh_client, run_remote_cmd

def main():
    client = get_ssh_client()
    remote_base = "/home/highest-ye/htdocs/highest-ye.store"
    
    # 1. Graceful restart test
    code, restart_out, err = run_remote_cmd(client, f"cd {remote_base} && php artisan queue:restart")
    
    # Wait 4 seconds for restart
    run_remote_cmd(client, "sleep 4")
    
    # 2. Comprehensive verification script
    verify_php = """<?php
$projDir = '/home/highest-ye/htdocs/highest-ye.store';
require $projDir . '/vendor/autoload.php';
$app = require_once $projDir . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkul\Procurement\Models\AliExpressWebhookInboxMessage;
use Webkul\Procurement\Models\ProcurementAuditLog;

$tables = [
    'external_platform_orders',
    'supplier_purchase_orders',
    'procurement_batches',
    'procurement_demands',
    'procurement_demand_allocations',
    'procurement_cost_snapshots',
    'procurement_audit_logs',
    'aliexpress_webhook_inbox_messages',
    'jobs',
    'failed_jobs'
];
$counts = [];
foreach ($tables as $t) {
    $counts[$t] = Schema::hasTable($t) ? DB::table($t)->count() : null;
}

$auditRecord = ProcurementAuditLog::where('action', 'aliexpress_oauth_expiration_warning')->first();

echo json_encode([
    'queue_connection_config' => config('queue.default'),
    'app_debug' => config('app.debug') ? 'true' : 'false',
    'app_env' => app()->environment(),
    'counts' => $counts,
    'smoke_audit_record_metadata' => $auditRecord ? [
        'id' => $auditRecord->id,
        'action' => $auditRecord->action,
        'actor_type' => $auditRecord->actor_type,
        'correlation_id' => $auditRecord->correlation_id,
        'created_at' => $auditRecord->created_at?->toIso8601String(),
    ] : null,
], JSON_PRETTY_PRINT);
"""
    sftp = client.open_sftp()
    with sftp.file('/tmp/verify_durability_final.php', 'w') as f:
        f.write(verify_php)
    sftp.close()
    
    code, out, err = run_remote_cmd(client, "php /tmp/verify_durability_final.php && rm -f /tmp/verify_durability_final.php")
    print("=== FINAL INTEGRITY & COUNTS ===")
    print(out)
    
    # 3. Process inspection
    code, ps_out, err = run_remote_cmd(client, "ps aux | grep 'queue:work database' | grep -v grep")
    print("=== RUNNING WORKER PROCESS ===")
    print(ps_out.strip())
    
    # 4. Service status & Linger inspection
    code, svc_out, err = run_remote_cmd(client, "systemctl --user status highest-queue-aliexpress-webhooks.service | head -n 12")
    print("=== SYSTEMD SERVICE STATUS ===")
    print(svc_out.strip())
    
    code, linger_out, err = run_remote_cmd(client, "loginctl show-user highest-ye -p Linger")
    print("=== LINGER STATUS ===")
    print(linger_out.strip())
    
    client.close()

if __name__ == '__main__':
    main()
