import json
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from scripts.remote_ssh_helper import get_ssh_client, run_remote_cmd

def main():
    client = get_ssh_client()
    remote_base = "/home/highest-ye/htdocs/highest-ye.store"
    
    cmd = f"""
cd {remote_base} && \
echo "HEAD_SHA: $(git rev-parse HEAD)" && \
echo "STATUS_OUTPUT:" && \
git status --short && \
echo "DIFF_EXIT_CODE: $?" && \
echo "FILE_SHA256: $(sha256sum packages/Webkul/Procurement/src/Support/AliExpressMoneyNormalizer.php | awk '{{print $1}}')" && \
echo "BLOB_SHA256: $(git show HEAD:packages/Webkul/Procurement/src/Support/AliExpressMoneyNormalizer.php | sha256sum | awk '{{print $1}}')"
"""
    code, out, err = run_remote_cmd(client, cmd)
    print(out)
    
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
    'failed_jobs'
];

$counts = [];
foreach ($tables as $t) {{
    $counts[$t] = DB::table($t)->count();
}}

echo json_encode(['db_counts' => $counts], JSON_PRETTY_PRINT);
"""
    sftp = client.open_sftp()
    with sftp.file('/tmp/audit_final_db.php', 'w') as f:
        f.write(audit_php)
    sftp.close()
    
    code, db_out, err = run_remote_cmd(client, "php /tmp/audit_final_db.php && rm -f /tmp/audit_final_db.php")
    print("=== FINAL DATABASE COUNTS ===")
    print(db_out)
        
    client.close()

if __name__ == '__main__':
    main()
