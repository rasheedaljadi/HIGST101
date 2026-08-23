import json
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from scripts.remote_ssh_helper import get_ssh_client, run_remote_cmd

def main():
    client = get_ssh_client()
    base_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    remote_base = "/home/highest-ye/htdocs/highest-ye.store"
    
    # Upload test suite
    sftp = client.open_sftp()
    with open(os.path.join(base_dir, 'scripts/run_strict_gateway_correctness_tests.php'), 'r', encoding='utf-8') as f:
        sftp.file('/tmp/run_strict_gateway_tests.php', 'w').write(f.read())
    sftp.close()
    
    # 1. Run Strict Gateway Tests
    code, test_out, test_err = run_remote_cmd(client, "php /tmp/run_strict_gateway_tests.php && rm -f /tmp/run_strict_gateway_tests.php")
    print("=== STRICT GATEWAY TEST SUITE RESULTS ===")
    print(test_out)
    
    # 2. Check Database Table Counts & Migration Status
    audit_php = """<?php
$projDir = '/home/highest-ye/htdocs/highest-ye.store';
require $projDir . '/vendor/autoload.php';
$app = require_once $projDir . '/bootstrap/app.php';
$kernel = $app->make(\\Illuminate\\Contracts\\Console\\Kernel::class);
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
foreach ($tables as $t) {
    try {
        $counts[$t] = DB::table($t)->count();
    } catch (\\Throwable $e) {
        $counts[$t] = 'error: ' . $e->getMessage();
    }
}

$lastMigration = DB::table('migrations')->orderBy('id', 'desc')->first();
$aeLiveCount = DB::table('external_platform_orders')->where('external_order_id', 'like', 'AE-LIVE-%')->count();

echo json_encode([
    'table_counts' => $counts,
    'last_migration' => $lastMigration,
    'synthetic_ae_live_count' => $aeLiveCount,
    'default_inventory_source' => DB::table('inventory_sources')->where('code', 'default')->first(['code', 'name', 'country', 'city', 'state', 'postcode', 'contact_name', 'contact_number']),
], JSON_PRETTY_PRINT);
"""
    sftp = client.open_sftp()
    with sftp.file('/tmp/audit_staging_state.php', 'w') as f:
        f.write(audit_php)
    sftp.close()
    
    code, audit_out, audit_err = run_remote_cmd(client, "php /tmp/audit_staging_state.php && rm -f /tmp/audit_staging_state.php")
    print("=== STAGING STATE AUDIT ===")
    print(audit_out)
    
    client.close()

if __name__ == '__main__':
    main()
