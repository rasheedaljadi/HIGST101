import json
import sys
from remote_ssh_helper import get_ssh_client, run_remote_cmd

# Ensure UTF-8 output encoding for Windows console
if sys.platform == 'win32':
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')
    sys.stderr.reconfigure(encoding='utf-8', errors='replace')

APP_PATH = '/home/highest-ye/htdocs/highest-ye.store'

REMOTE_CATALOG_PHP = r"""<?php
require '/home/highest-ye/htdocs/highest-ye.store/vendor/autoload.php';
$app = require_once '/home/highest-ye/htdocs/highest-ye.store/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$productsCount = Illuminate\Support\Facades\DB::table('products')->count();
$sampleProducts = Illuminate\Support\Facades\DB::table('products as p')
    ->join('product_flat as pf', 'pf.product_id', '=', 'p.id')
    ->where('pf.status', 1)
    ->where('pf.locale', 'ar')
    ->select(['p.id', 'p.sku', 'p.type', 'pf.name', 'pf.price', 'p.additional'])
    ->limit(10)
    ->get();

$attributeValues = Illuminate\Support\Facades\DB::table('product_attribute_values as pav')
    ->join('attributes as a', 'a.id', '=', 'pav.attribute_id')
    ->whereIn('a.code', ['origin_type', 'supplier', 'aliexpress_product_id', 'dropship_source', 'external_id'])
    ->select(['pav.product_id', 'a.code', 'pav.text_value', 'pav.integer_value'])
    ->limit(20)
    ->get();

echo json_encode([
    'total_products' => $productsCount,
    'sample_products' => $sampleProducts,
    'custom_attributes' => $attributeValues
], JSON_PRETTY_PRINT);
"""

def main():
    client = get_ssh_client()
    sftp = client.open_sftp()
    with sftp.file('/tmp/inspect_catalog.php', 'w') as f:
        f.write(REMOTE_CATALOG_PHP)
    sftp.close()
    
    code_php, php_out, php_err = run_remote_cmd(client, f"cd {APP_PATH} && php /tmp/inspect_catalog.php")
    run_remote_cmd(client, "rm -f /tmp/inspect_catalog.php")
    
    print(php_out)
    client.close()

if __name__ == '__main__':
    main()
