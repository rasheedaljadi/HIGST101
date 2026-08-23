import json
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from scripts.remote_ssh_helper import get_ssh_client, run_remote_cmd

def main():
    client = get_ssh_client()
    remote_base = "/home/highest-ye/htdocs/highest-ye.store"
    
    php = """<?php
$projDir = '/home/highest-ye/htdocs/highest-ye.store';
require $projDir . '/vendor/autoload.php';
$app = require_once $projDir . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

echo json_encode($counts, JSON_PRETTY_PRINT);
"""
    sftp = client.open_sftp()
    with sftp.file('/tmp/check_counts_final.php', 'w') as f:
        f.write(php)
    sftp.close()
    
    code, out, err = run_remote_cmd(client, "php /tmp/check_counts_final.php && rm -f /tmp/check_counts_final.php")
    print(out)
    client.close()

if __name__ == '__main__':
    main()
