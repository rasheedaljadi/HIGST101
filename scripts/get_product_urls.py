import json
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from scripts.remote_ssh_helper import get_ssh_client, run_remote_cmd

def main():
    client = get_ssh_client()
    remote_base = "/home/highest-ye/htdocs/highest-ye.store"
    
    query_php = """<?php
$projDir = '""" + remote_base + """';
require $projDir . '/vendor/autoload.php';
$app = require_once $projDir . '/bootstrap/app.php';
$kernel = $app->make(\\Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Illuminate\\Support\\Facades\\DB;

$variantId = 3163;
$variant = DB::table('products')->where('id', $variantId)->first();
$parentId = $variant->parent_id ?? 3153;
$parent = DB::table('products')->where('id', $parentId)->first();

$flat = DB::table('product_flat')->where('product_id', $parentId)->first() 
    ?? DB::table('product_flat')->where('product_id', $variantId)->first();

$offer = DB::table('higest_source_offers')->where('variant_id', $variantId)->first();

$appUrl = config('app.url', 'https://highest-ye.store');
$adminUrl = config('app.admin_url', 'admin');

$urlKey = $flat->url_key ?? null;
$productName = $flat->name ?? ($parent->sku ?? 'Product ' . $parentId);

$info = [
    'variant_id' => $variantId,
    'parent_id' => $parentId,
    'product_name' => $productName,
    'sku' => $flat->sku ?? $variant->sku,
    'storefront_url' => $urlKey ? "{$appUrl}/{$urlKey}" : "{$appUrl}/products/{$parentId}",
    'admin_catalog_url' => "{$appUrl}/{$adminUrl}/catalog/products/edit/{$parentId}",
    'aliexpress_source_url' => "https://www.aliexpress.com/item/1005010378829324.html",
    'supplier_product_id' => '1005010378829324',
    'supplier_sku_id' => '12000052207602660',
];

echo json_encode($info, JSON_PRETTY_PRINT);
"""

    sftp = client.open_sftp()
    remote_script_path = f"{remote_base}/scripts/get_product_urls.php"
    with sftp.open(remote_script_path, 'w') as f:
        f.write(query_php)
    sftp.close()
    
    try:
        cmd = f"cd {remote_base} && php scripts/get_product_urls.php"
        code, out, err = run_remote_cmd(client, cmd)
        print(f"\n--- Product Links Output ---\n{out}")
    finally:
        try:
            run_remote_cmd(client, f"rm -f {remote_script_path}")
        except Exception:
            pass
        client.close()

if __name__ == '__main__':
    main()
