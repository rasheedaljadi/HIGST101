import json
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from scripts.remote_ssh_helper import get_ssh_client, run_remote_cmd

def main():
    client = get_ssh_client()
    remote_base = "/home/highest-ye/htdocs/highest-ye.store"
    backup_dir = "/home/highest-ye/backups"
    target_sha = "f85f9b956f82f1bc4eb9e7428cd8ead06cd37be4"
    
    # 1. Take safe file backup of normalizer outside webroot
    backup_php = f"""<?php
$file = '{remote_base}/packages/Webkul/Procurement/src/Support/AliExpressMoneyNormalizer.php';
$destDir = '{backup_dir}/normalizer_backup_' . date('Ymd_His');
mkdir($destDir, 0755, true);
$destFile = $destDir . '/AliExpressMoneyNormalizer.php';
if (file_exists($file)) {{
    copy($file, $destFile);
    $sha = hash_file('sha256', $destFile);
    echo json_encode([
        'backup_path' => $destFile,
        'sha256' => $sha,
        'timestamp' => date('Y-m-d H:i:s P'),
    ], JSON_PRETTY_PRINT);
}} else {{
    echo json_encode(['error' => 'file not found']);
}}
"""
    sftp = client.open_sftp()
    with sftp.file('/tmp/backup_normalizer.php', 'w') as f:
        f.write(backup_php)
    sftp.close()
    
    code, backup_out, err = run_remote_cmd(client, "php /tmp/backup_normalizer.php && rm -f /tmp/backup_normalizer.php")
    print("=== FILE BACKUP MANIFEST ===")
    print(backup_out)
    
    # 2. Execute Pure Git Deployment on Staging with Stash Protection
    git_deploy_cmd = f"""
cd {remote_base} && \
git stash --include-untracked -m "staging_pre_git_sync_f85f9b9" && \
git fetch origin feat/delivery-admin-ui-rebuild && \
git checkout {target_sha}
"""
    code, git_out, git_err = run_remote_cmd(client, git_deploy_cmd)
    print("=== GIT DEPLOY OUTPUT ===")
    print(git_out)
    if git_err:
        print("GIT ERR:", git_err)
        
    # 3. Verify Git Invariants
    verify_cmd = f"""
cd {remote_base} && \
echo "HEAD SHA: $(git rev-parse HEAD)" && \
echo "GIT DIFF: $(git diff --stat)" && \
echo "GIT DIFF EXIT CODE: $?" && \
echo "LOCAL FILE SHA256: $(sha256sum packages/Webkul/Procurement/src/Support/AliExpressMoneyNormalizer.php | awk '{{print $1}}')" && \
echo "HEAD BLOB SHA256: $(git show HEAD:packages/Webkul/Procurement/src/Support/AliExpressMoneyNormalizer.php | sha256sum | awk '{{print $1}}')"
"""
    code, verify_out, verify_err = run_remote_cmd(client, verify_cmd)
    print("=== GIT INVARIANTS VERIFICATION ===")
    print(verify_out)
    
    # 4. Clear Caches
    clear_cmd = f"cd {remote_base} && php artisan config:clear && php artisan route:clear && php artisan cache:clear"
    code, clear_out, err = run_remote_cmd(client, clear_cmd)
    print("Cache cleared:\n", clear_out.strip())
    
    # 5. Run Unit Tests on Staging
    test_cmd = f"cd {remote_base} && php artisan test packages/Webkul/Procurement/tests/Unit/AliExpressMoneyNormalizerTest.php"
    code, test_out, test_err = run_remote_cmd(client, test_cmd)
    print("=== UNIT TEST RESULTS ON STAGING ===")
    print(test_out)
    if test_err:
        print("TEST ERR:", test_err)
        
    # 6. Check Database Invariance
    audit_php = f"""<?php
$projDir = '{remote_base}';
require $projDir . '/vendor/autoload.php';
$app = require_once $projDir . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Illuminate\\Support\\Facades\\DB;

$tables = [
    'external_platform_orders',
    'supplier_purchase_orders',
    'procurement_batches',
    'procurement_demands',
    'procurement_demand_allocations',
    'procurement_cost_snapshots',
    'inventory_sources',
    'aliexpress_webhook_inbox_messages',
    'orders',
    'invoices',
    'shipments',
    'refunds',
    'jobs',
    'failed_jobs'
];

$counts = [];
foreach ($tables as $t) {{
    $counts[$t] = DB::table($t)->count();
}}

echo json_encode(['db_counts' => $counts], JSON_PRETTY_PRINT);
"""
    sftp = client.open_sftp()
    with sftp.file('/tmp/audit_db.php', 'w') as f:
        f.write(audit_php)
    sftp.close()
    
    code, db_out, err = run_remote_cmd(client, "php /tmp/audit_db.php && rm -f /tmp/audit_db.php")
    print("=== DATABASE INVARIANCE CHECK ===")
    print(db_out)

    client.close()

if __name__ == '__main__':
    main()
