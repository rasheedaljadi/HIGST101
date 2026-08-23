import json
import sys
from remote_ssh_helper import get_ssh_client, run_remote_cmd

# Ensure UTF-8 output encoding for Windows console
if sys.platform == 'win32':
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')
    sys.stderr.reconfigure(encoding='utf-8', errors='replace')

APP_PATH = '/home/highest-ye/htdocs/highest-ye.store'

def main():
    client = get_ssh_client()
    print("\n=== Executing Remote Integrity Remediation & Deployment on 76.13.79.242 ===")
    
    # 1. Pull latest commit from origin
    print("\n[1] Pulling latest commit...")
    _, pull_out, pull_err = run_remote_cmd(client, f"cd {APP_PATH} && git pull origin feat/delivery-admin-ui-rebuild")
    print(pull_out)
    if pull_err:
        print(f"STDERR: {pull_err}")
        
    # 2. Run migrations
    print("\n[2] Running database migrations...")
    _, mig_out, mig_err = run_remote_cmd(client, f"cd {APP_PATH} && php artisan migrate --force")
    print(mig_out)
    if mig_err:
        print(f"STDERR: {mig_err}")
        
    # 3. Execute remediation command
    print("\n[3] Executing procurement:remediate-failed-submission command on SPO #1...")
    cmd = f'cd {APP_PATH} && php artisan procurement:remediate-failed-submission 1 --actor_id=1 --error_code=IllegalAccessToken --error_msg="The specified API Path or access token is invalid or ungranted on AliExpress IOP gateway" --request_id=212a73a517874213795736385 --synthetic_id=AE-LIVE-20260822-4586371333'
    _, rem_out, rem_err = run_remote_cmd(client, cmd)
    print(rem_out)
    if rem_err:
        print(f"STDERR: {rem_err}")
        
    # 4. Verify post-remediation database state in read-only mode
    print("\n[4] Inspecting post-remediation state on server...")
    REMOTE_VERIFY_PHP = r"""<?php
require '/home/highest-ye/htdocs/highest-ye.store/vendor/autoload.php';
$app = require_once '/home/highest-ye/htdocs/highest-ye.store/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$spo = Webkul\Procurement\Models\SupplierPurchaseOrder::find(1);
$platformOrder = Webkul\Procurement\Models\ExternalPlatformOrder::find(1);
$auditLogs = Webkul\Procurement\Models\ProcurementAuditLog::where('auditable_id', 1)->get();

echo json_encode([
    'spo' => [
        'id' => $spo?->id,
        'purchase_order_number' => $spo?->purchase_order_number,
        'state' => $spo?->state,
        'payment_state' => $spo?->payment_state,
        'external_sync_state' => $spo?->external_sync_state,
        'expected_total' => (float)$spo?->expected_total
    ],
    'platform_order' => [
        'id' => $platformOrder?->id,
        'external_order_id' => $platformOrder?->external_order_id,
        'correlation_key' => $platformOrder?->correlation_key,
        'provider_request_id' => $platformOrder?->provider_request_id,
        'failure_code' => $platformOrder?->failure_code,
        'raw_status' => $platformOrder?->raw_status,
        'normalized_status' => $platformOrder?->normalized_status,
        'snapshots' => $platformOrder?->snapshots
    ],
    'audit_logs_count' => $auditLogs->count(),
    'latest_audit_log' => $auditLogs->last()
], JSON_PRETTY_PRINT);
"""
    sftp = client.open_sftp()
    with sftp.file('/tmp/verify_remediation.php', 'w') as f:
        f.write(REMOTE_VERIFY_PHP)
    sftp.close()
    
    code_php, php_out, php_err = run_remote_cmd(client, f"cd {APP_PATH} && php /tmp/verify_remediation.php")
    run_remote_cmd(client, "rm -f /tmp/verify_remediation.php")
    
    print("\n--- Verified Post-Remediation State ---")
    print(php_out)
    
    with open('scripts/remote_remediation_verification_result.json', 'w', encoding='utf-8') as f:
        f.write(php_out)
        
    client.close()

if __name__ == '__main__':
    main()
