import paramiko

HOST = '76.13.79.242'
USER = 'highest-ye'
PASS = 'YoK2PBV1fo82yujX2tDq'
APP_DIR = '/home/highest-ye/htdocs/highest-ye.store'

def connect():
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    print(f"Connecting via SSH to {HOST}...")
    client.connect(HOST, username=USER, password=PASS, timeout=15)
    return client

def run_cmd(client, cmd):
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
    return exit_code, out, err

if __name__ == '__main__':
    client = connect()
    
    verify_script = """<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Schema;
use Webkul\\Inventory\\Services\\InventoryReportingService;

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
        f.write(verify_script)
    sftp.close()

    run_cmd(client, f"cd {APP_DIR} && php verify_deploy_temp.php")
    run_cmd(client, f"rm -f {APP_DIR}/verify_deploy_temp.php")

    client.close()
