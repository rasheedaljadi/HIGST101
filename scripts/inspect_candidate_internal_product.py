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

$parentId = 3153;
$variantId = 3163;

$parent = DB::table('products')->where('id', $parentId)->first();
$variant = DB::table('products')->where('id', $variantId)->first();

$parentFlat = DB::table('product_flat')->where('product_id', $parentId)->get();
$variantFlat = DB::table('product_flat')->where('product_id', $variantId)->get();

$parentInv = DB::table('product_inventories')
    ->join('inventory_sources', 'inventory_sources.id', '=', 'product_inventories.inventory_source_id')
    ->where('product_inventories.product_id', $parentId)
    ->select('inventory_sources.code', 'inventory_sources.name', 'product_inventories.qty')
    ->get();

$variantInv = DB::table('product_inventories')
    ->join('inventory_sources', 'inventory_sources.id', '=', 'product_inventories.inventory_source_id')
    ->where('product_inventories.product_id', $variantId)
    ->select('inventory_sources.code', 'inventory_sources.name', 'product_inventories.qty')
    ->get();

$import = DB::table('aliexpress_product_imports')->where('id', 457)->first();
$payload = json_decode($import->payload_snapshot, true);

echo json_encode([
    'import_id' => $import->id,
    'store_info' => $payload['store_info'] ?? null,
    'store_id' => $payload['store_id'] ?? null,
    'store_name' => $payload['store_name'] ?? null,
    'all_payload_keys' => array_keys($payload),
], JSON_PRETTY_PRINT);
"""
    sftp = client.open_sftp()
    with sftp.file('/tmp/inspect_product_3153.php', 'w') as f:
        f.write(script)
    sftp.close()
    
    code, out, err = run_remote_cmd(client, "php /tmp/inspect_product_3153.php && rm -f /tmp/inspect_product_3153.php")
    print(out)
    if err:
        print("ERR:", err)
        
    client.close()

if __name__ == '__main__':
    main()
