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
use App\\Models\\AliExpressProductImport;
use App\\Models\\HigestSourceOffer;

$imports = DB::table('aliexpress_product_imports')->get();
$offers = DB::table('higest_source_offers')->get();

$extProductId = '1005010378829324';
$extSkuId = '12000052207602660';

$matchingImports = DB::table('aliexpress_product_imports')
    ->where('aliexpress_product_id', $extProductId)
    ->get();

$matchingOffers = DB::table('higest_source_offers')
    ->where('source_sku_id', $extSkuId)
    ->orWhere('source_sku_id', $extProductId)
    ->get();

// Also check inventory of product 1 and other products across all inventory sources
$inventoryBySource = DB::table('product_inventories')
    ->join('inventory_sources', 'inventory_sources.id', '=', 'product_inventories.inventory_source_id')
    ->select('product_inventories.product_id', 'inventory_sources.code', 'inventory_sources.name', 'product_inventories.qty')
    ->get();

echo json_encode([
    'total_imports' => $imports->count(),
    'all_imports' => $imports,
    'total_offers' => $offers->count(),
    'all_offers' => $offers,
    'matching_imports' => $matchingImports,
    'matching_offers' => $matchingOffers,
    'inventory_by_source' => $inventoryBySource,
], JSON_PRETTY_PRINT);
"""
    sftp = client.open_sftp()
    with sftp.file('/tmp/inspect_imports.php', 'w') as f:
        f.write(script)
    sftp.close()
    
    code, out, err = run_remote_cmd(client, "php /tmp/inspect_imports.php && rm -f /tmp/inspect_imports.php")
    print(out)
    if err:
        print("ERR:", err)
        
    client.close()

if __name__ == '__main__':
    main()
