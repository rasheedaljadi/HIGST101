import json
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from scripts.remote_ssh_helper import get_ssh_client, run_remote_cmd

def main():
    client = get_ssh_client()
    remote_base = "/home/highest-ye/htdocs/highest-ye.store"
    
    script = f"""<?php
$projDir = '{remote_base}';
require $projDir . '/vendor/autoload.php';
$app = require_once $projDir . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Illuminate\\Support\\Facades\\DB;

// Inspect Supplier POs matching the candidate product / SKU
$productId = '1005010378829324';
$skuId = '12000052207602660';

$epoCols = DB::getSchemaBuilder()->getColumnListing('external_platform_orders');
$spoCols = DB::getSchemaBuilder()->getColumnListing('supplier_purchase_orders');

$spos = DB::table('supplier_purchase_orders')->get();
$epos = DB::table('external_platform_orders')->get();
$spoItems = DB::table('supplier_purchase_order_items')->get();

echo json_encode([
    'spo_cols' => $spoCols,
    'epo_cols' => $epoCols,
    'spos' => $spos,
    'epos' => $epos,
    'spo_items' => $spoItems,
], JSON_PRETTY_PRINT);
"""
    sftp = client.open_sftp()
    with sftp.file('/tmp/check_spo.php', 'w') as f:
        f.write(script)
    sftp.close()
    
    code, out, err = run_remote_cmd(client, "php /tmp/check_spo.php && rm -f /tmp/check_spo.php")
    print(out)
    if err:
        print("ERR:", err)
        
    client.close()

if __name__ == '__main__':
    main()
