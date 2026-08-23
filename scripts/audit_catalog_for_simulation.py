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

$extProductId = '1005010378829324';
$extSkuId = '12000052207602660';

// 1. Find product in product_flat or product_attribute_values
$products = DB::table('products')->get();
$flatProducts = DB::table('product_flat')->get();
$aliexpressProducts = DB::table('product_attribute_values')
    ->where('text_value', 'like', "%$extProductId%")
    ->orWhere('text_value', 'like', "%$extSkuId%")
    ->get();

// Also search in aliexpress_products / product mapping tables if any
$allTables = DB::select('SHOW TABLES');
$tableNames = array_map(function($t) {{
    return array_values((array)$t)[0];
}}, $allTables);

$aeTables = array_filter($tableNames, function($n) {{
    return str_contains($n, 'aliexpress') || str_contains($n, 'procurement') || str_contains($n, 'inventory') || str_contains($n, 'supplier') || str_contains($n, 'product');
}});

// 2. Check product inventories
$inventorySources = DB::table('inventory_sources')->get();
$productInventories = DB::table('product_inventories')->get();

// 3. Check existing products matching ID 1, 2, etc.
$sampleProducts = DB::table('products')->limit(10)->get();
$sampleFlat = DB::table('product_flat')->limit(10)->get();

echo json_encode([
    'matching_attr_values' => $aliexpressProducts,
    'ae_procurement_tables' => array_values($aeTables),
    'inventory_sources' => $inventorySources,
    'product_inventories_sample' => $productInventories->take(20),
    'sample_products' => $sampleProducts,
    'sample_flat' => $sampleFlat,
], JSON_PRETTY_PRINT);
"""
    sftp = client.open_sftp()
    with sftp.file('/tmp/audit_catalog.php', 'w') as f:
        f.write(script)
    sftp.close()
    
    code, out, err = run_remote_cmd(client, "php /tmp/audit_catalog.php && rm -f /tmp/audit_catalog.php")
    print(out)
    if err:
        print("ERR:", err)
        
    client.close()

if __name__ == '__main__':
    main()
