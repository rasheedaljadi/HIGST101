import json
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from scripts.remote_ssh_helper import get_ssh_client, run_remote_cmd

def main():
    client = get_ssh_client()
    remote_base = "/home/highest-ye/htdocs/highest-ye.store"
    
    script_php = f"""<?php
$projDir = '{remote_base}';
require $projDir . '/vendor/autoload.php';
$app = require_once $projDir . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Illuminate\\Support\\Facades\\DB;

$epo = DB::table('external_platform_orders')
    ->where('supplier_purchase_order_id', 35)
    ->first();

$audit = DB::table('procurement_audit_logs')
    ->where('auditable_type', 'Webkul\\\\Procurement\\\\Models\\\\SupplierPurchaseOrder')
    ->where('auditable_id', 35)
    ->latest('id')
    ->first();

echo json_encode([
    'epo' => $epo,
    'audit' => $audit,
], JSON_PRETTY_PRINT);
"""
    sftp = client.open_sftp()
    with sftp.file('/tmp/inspect_sub_details.php', 'w') as f:
        f.write(script_php)
    sftp.close()
    
    code, out, err = run_remote_cmd(client, "php /tmp/inspect_sub_details.php && rm -f /tmp/inspect_sub_details.php")
    print(out)
    if err:
        print("ERR:", err)
        
    client.close()

if __name__ == '__main__':
    main()
