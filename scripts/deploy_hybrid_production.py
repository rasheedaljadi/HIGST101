import sys
import os
import time

sys.path.insert(0, 'scripts')
import remote_ssh_helper as r

HOST = '76.13.79.242'
USER = 'highest-ye'
PASS = 'YoK2PBV1fo82yujX2tDq'
APP_DIR = '/home/highest-ye/htdocs/highest-ye.store'

print("=" * 65)
print("STARTING HYBRID CHECKOUT PRODUCTION DEPLOYMENT")
print("Target Server: 76.13.79.242 (/home/highest-ye/htdocs/highest-ye.store)")
print("=" * 65)

client = r.get_ssh_client()
sftp = client.open_sftp()

def exec_remote(cmd, label=None):
    if label:
        print(f"\n--- {label} ---")
    print(f">> [CMD] {cmd}")
    code, out, err = r.run_remote_cmd(client, cmd)
    if out.strip():
        print(f"[OUT]\n{out.strip()}")
    if err.strip():
        print(f"[ERR]\n{err.strip()}")
    print(f"[EXIT CODE] {code}")
    if code != 0:
        raise RuntimeError(f"Step failed with code {code}: {cmd}")
    return out

try:
    # STEP 1: Backup Database
    backup_ts = time.strftime("%Y%m%d_%H%M%S")
    backup_php = """<?php
$env = file_get_contents('.env');
preg_match('/DB_USERNAME=(.*)/', $env, $u);
preg_match('/DB_PASSWORD=(.*)/', $env, $pw);
preg_match('/DB_DATABASE=(.*)/', $env, $db);
$user = trim($u[1] ?? 'highest-db');
$pass = trim($pw[1] ?? '');
$dbName = trim($db[1] ?? 'highest-db');
$passArg = $pass ? '-p' . escapeshellarg($pass) : '';
$backupFile = '/home/highest-ye/backups/production_pre_hybrid_deploy_""" + backup_ts + """.sql';
exec('mkdir -p /home/highest-ye/backups');
$cmd = 'mysqldump -h 127.0.0.1 -u ' . escapeshellarg($user) . ' ' . $passArg . ' ' . escapeshellarg($dbName) . ' > ' . $backupFile;
exec($cmd, $output, $returnVar);
if ($returnVar !== 0) {
    echo "BACKUP_FAILED: code " . $returnVar . "\\n";
    exit(1);
}
echo "BACKUP_SUCCESS: " . $backupFile . "\\n";
"""
    with sftp.file(f"{APP_DIR}/run_deploy_backup.php", "w") as f:
        f.write(backup_php)

    exec_remote(f"cd {APP_DIR} && php8.4 run_deploy_backup.php && rm run_deploy_backup.php", "1. Taking Pre-Deployment Database Backup")

    # STEP 2: Fetch and Hard Reset to Origin Main
    deploy_git_cmd = f"cd {APP_DIR} && git remote set-url origin git@github.com:rasheedaljadi/HIGST101.git && git fetch origin main && git reset --hard origin/main && git log -n 1 --oneline"
    exec_remote(deploy_git_cmd, "2. Pulling Code from GitHub (origin/main)")

    # STEP 3: Run Database Migrations
    exec_remote(f"cd {APP_DIR} && php8.4 artisan migrate --force", "3. Running Migrations")

    # STEP 4: Ensure Storage Permissions and Symlinks
    storage_cmd = f"cd {APP_DIR} && mkdir -p storage/framework/views storage/framework/cache/data storage/framework/sessions storage/logs && chmod -R 775 storage bootstrap/cache && php8.4 artisan storage:link --force || true"
    exec_remote(storage_cmd, "4. Checking Storage Permissions and Links")

    # STEP 5: Clear and Rebuild Caches
    cache_cmd = f"cd {APP_DIR} && php8.4 artisan config:clear && php8.4 artisan route:clear && php8.4 artisan view:clear && php8.4 artisan cache:clear"
    exec_remote(cache_cmd, "5. Clearing and Rebuilding Framework Caches")

    # STEP 6: Verification
    verify_php = """<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

echo "=== PRODUCTION VERIFICATION ===\\n";
echo "Git Commit: " . trim(shell_exec('git log -n 1 --oneline')) . "\\n";
echo "AliExpress Keys View: " . (view()->exists('aliexpress.keys') ? 'EXISTS' : 'MISSING') . "\\n";
echo "Procurement Demands View: " . (view()->exists('procurement::admin.demands.index') ? 'EXISTS' : 'MISSING') . "\\n";
echo "Procurement Batches Create View: " . (view()->exists('procurement::admin.batches.create') ? 'EXISTS' : 'MISSING') . "\\n";
echo "Procurement Supplier Orders View: " . (view()->exists('procurement::admin.supplier_orders.view') ? 'EXISTS' : 'MISSING') . "\\n";
echo "Procurement Platform Orders View: " . (view()->exists('procurement::admin.platform_orders.view') ? 'EXISTS' : 'MISSING') . "\\n";
echo "Live Stock Validator Class: " . (class_exists('App\\\\Services\\\\AliExpress\\\\AliExpressLiveStockValidator') ? 'LOADED' : 'NOT FOUND') . "\\n";
echo "Procurement Batch Service Class: " . (class_exists('Webkul\\\\Procurement\\\\Services\\\\ProcurementBatchService') ? 'LOADED' : 'NOT FOUND') . "\\n";
"""
    with sftp.file(f"{APP_DIR}/run_deploy_verify.php", "w") as f:
        f.write(verify_php)

    exec_remote(f"cd {APP_DIR} && php8.4 run_deploy_verify.php && rm run_deploy_verify.php", "6. Verifying Production State and Loaded Views")

    print("\n" + "=" * 65)
    print("DEPLOYMENT TO PRODUCTION COMPLETED SUCCESSFULLY 100%!")
    print("=" * 65)

except Exception as e:
    print(f"\n[FATAL ERROR DURING DEPLOYMENT]: {e}")
    sys.exit(1)
finally:
    sftp.close()
    client.close()
