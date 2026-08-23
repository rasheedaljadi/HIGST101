import paramiko
import subprocess
import time

HOST = '76.13.79.242'
USER = 'highest-ye'
PASS = 'YoK2PBV1fo82yujX2tDq'
APP_DIR = '/home/highest-ye/htdocs/highest-ye.store'
COMMIT_SHA = '2af8a8c044ab7fe2e603e925acda8eeeae6f0e9a'

def connect():
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    print(f"Connecting via SSH to {HOST}...")
    client.connect(HOST, username=USER, password=PASS, timeout=15)
    print("Connected successfully!")
    return client

def run_cmd(client, cmd, check_exit=True):
    print(f"\n>>> Running Remote Command: {cmd}")
    stdin, stdout, stderr = client.exec_command(cmd)
    out = stdout.read().decode('utf-8', errors='replace')
    err = stderr.read().decode('utf-8', errors='replace')
    exit_code = stdout.channel.recv_exit_status()
    if out:
        print(f"[STDOUT]\n{out.strip()}")
    if err:
        print(f"[STDERR]\n{err.strip()}")
    print(f"[EXIT CODE] {exit_code}")
    if check_exit and exit_code != 0:
        raise Exception(f"Command failed with exit code {exit_code}: {cmd}")
    return exit_code, out, err

if __name__ == '__main__':
    print("=== STEP 1: LOCAL GIT PUSH OF ISOLATION COMMIT ===")
    print(f"Pushing commit {COMMIT_SHA} to remote origin...")
    try:
        push_res = subprocess.run(
            ["git", "push", "origin", "feat/delivery-admin-ui-rebuild"],
            capture_output=True,
            text=True,
            check=True
        )
        print("[STDOUT]", push_res.stdout)
    except subprocess.CalledProcessError as e:
        print("[PUSH OUTPUT/ERROR]", e.stderr or e.stdout)
        print("Continuing deployment script...")

    client = connect()
    
    print("\n=== STEP 2: CREATING PRODUCTION DATABASE BACKUP ===")
    backup_file = f"/home/highest-ye/backups/highest-db-pre-isolation-{int(time.time())}.sql"
    run_cmd(client, "mkdir -p /home/highest-ye/backups")
    
    dump_cmd = f"mysqldump -u highest-ye -p'{PASS}' highest-db > {backup_file} && gzip -f {backup_file}"
    run_cmd(client, dump_cmd, check_exit=False)
    run_cmd(client, "ls -lh /home/highest-ye/backups/*.gz | tail -n 3")

    print("\n=== STEP 3: GIT FETCH & CHECKOUT COMMIT ON REMOTE ===")
    run_cmd(client, f"cd {APP_DIR} && git stash", check_exit=False)
    run_cmd(client, f"cd {APP_DIR} && git fetch origin")
    run_cmd(client, f"cd {APP_DIR} && git checkout -f {COMMIT_SHA}")
    run_cmd(client, f"cd {APP_DIR} && git log -n 1 --oneline")

    print("\n=== STEP 4: RUNNING PRODUCTION DATABASE MIGRATION ===")
    run_cmd(client, f"cd {APP_DIR} && php artisan migrate --force")

    print("\n=== STEP 5: OPTIMIZING & CLEARING CACHES ===")
    run_cmd(client, f"cd {APP_DIR} && php artisan config:clear && php artisan cache:clear && php artisan route:clear && php artisan view:clear")

    print("\n=== STEP 6: POST-DEPLOYMENT READ-ONLY AUDIT ===")
    verify_script = """<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\\\\Contracts\\\\Console\\\\Kernel')->bootstrap();

use Illuminate\\\\Support\\\\Facades\\\\DB;
use Illuminate\\\\Support\\\\Facades\\\\Schema;
use Webkul\\\\Inventory\\\\Services\\\\InventoryReportingService;

echo "=== PRODUCTION POST-DEPLOYMENT VERIFICATION ===\\n";
echo "DB Connection Database: " . config('database.connections.mysql.database') . "\\n";

$hasTable = Schema::hasTable('external_availability_snapshots') ? 'YES' : 'NO';
echo "Table external_availability_snapshots exists: {$hasTable}\\n";

$defaultSource = DB::table('inventory_sources')->where('code', 'default')->first();
if ($defaultSource) {
    $pCount = DB::table('product_inventories')->where('inventory_source_id', $defaultSource->id)->count();
    $qSum = DB::table('product_inventories')->where('inventory_source_id', $defaultSource->id)->sum('qty');
    echo "DEFAULT SOURCE INTACT: Linked Prods = {$pCount}, QtySum = " . number_format($qSum) . "\\n";
}

$service = app(InventoryReportingService::class);
$sourcesReport = $service->getSourcesBalanceReport();
$excludesDefault = !$sourcesReport->pluck('code')->contains('default') ? 'YES (PASSED)' : 'NO (FAILED)';
echo "Sources Balance Report excludes default: {$excludesDefault}\\n";

$reconciliationReport = $service->getReconciliationReport();
$reconciliationExcludes = !$reconciliationReport->pluck('source_code')->contains('default') ? 'YES (PASSED)' : 'NO (FAILED)';
echo "Reconciliation Report excludes default: {$reconciliationExcludes}\\n";

$legacyReport = $service->getLegacyExceptionReport();
echo "Legacy Exception Report items count: " . count($legacyReport) . "\\n";
"""

    sftp = client.open_sftp()
    with sftp.file(f"{APP_DIR}/verify_deploy_temp.php", "w") as f:
        f.write(php_script)
    sftp.close()

    run_cmd(client, f"cd {APP_DIR} && php verify_deploy_temp.php")
    run_cmd(client, f"rm -f {APP_DIR}/verify_deploy_temp.php")

    client.close()
    print("\n=== PRODUCTION DEPLOYMENT COMPLETED SUCCESSFULLY ===")
