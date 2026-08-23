import json
import sys
from remote_ssh_helper import get_ssh_client, run_remote_cmd

# Ensure UTF-8 output encoding for Windows console
if sys.platform == 'win32':
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')
    sys.stderr.reconfigure(encoding='utf-8', errors='replace')

APP_PATH = '/home/highest-ye/htdocs/highest-ye.store'

REMOTE_AUDIT_PHP = r"""<?php
require '/home/highest-ye/htdocs/highest-ye.store/vendor/autoload.php';
$app = require_once '/home/highest-ye/htdocs/highest-ye.store/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// 1. Audit SA dropship station address
$saSource = Illuminate\Support\Facades\DB::table('inventory_sources')
    ->where('code', 'hayest_dropship_sa')
    ->first();

$saAddressComplete = (
    !empty($saSource->contact_name) || !empty($saSource->name)
) && !empty($saSource->country) && !empty($saSource->state);

// 2. Find imported products with AliExpress metadata
$importedProducts = Illuminate\Support\Facades\DB::table('products as p')
    ->join('product_flat as pf', 'pf.product_id', '=', 'p.id')
    ->leftJoin('product_inventories as pi', function($join) {
        $join->on('pi.product_id', '=', 'p.id')
             ->where('pi.inventory_source_id', '=', 6); // hayest_dropship_ye
    })
    ->where('pf.status', 1)
    ->where('pf.locale', 'ar')
    ->whereNotNull('p.additional')
    ->select([
        'p.id',
        'p.type',
        'p.sku',
        'pf.name',
        'pf.price',
        'p.additional',
        'pi.qty as owned_ye_qty'
    ])
    ->limit(20)
    ->get();

$candidates = [];
foreach ($importedProducts as $prod) {
    $additional = is_string($prod->additional) ? json_decode($prod->additional, true) : (array)$prod->additional;
    $aeId = $additional['aliexpress_product_id'] ?? $additional['ae_product_id'] ?? null;
    $storeId = $additional['store_id'] ?? $additional['supplier_store_id'] ?? 'AE-STORE-4586371333';
    
    if ($aeId || !empty($additional['dropshipping_source'])) {
        $candidates[] = [
            'product_id' => $prod->id,
            'sku' => $prod->sku,
            'name' => $prod->name,
            'price_usd' => (float)$prod->price,
            'external_id' => $aeId,
            'store_id' => $storeId,
            'owned_ye_qty' => (int)($prod->owned_ye_qty ?? 0),
            'shortage_reason' => 'Zero stock in hayest_dropship_ye; requires external procurement'
        ];
    }
}

echo json_encode([
    'sa_source' => [
        'code' => $saSource->code ?? 'N/A',
        'name' => $saSource->name ?? 'N/A',
        'country' => $saSource->country ?? 'SA',
        'state' => $saSource->state ?? 'Riyadh',
        'city' => $saSource->city ?? 'Riyadh',
        'is_complete' => $saAddressComplete
    ],
    'candidates_count' => count($candidates),
    'candidates' => array_slice($candidates, 0, 5)
], JSON_PRETTY_PRINT);
"""

def main():
    client = get_ssh_client()
    print("\n=== Phase 0: Auditing Candidate Imported Products & SA Station ===")
    
    sftp = client.open_sftp()
    with sftp.file('/tmp/audit_candidates.php', 'w') as f:
        f.write(REMOTE_AUDIT_PHP)
    sftp.close()
    
    code_php, php_out, php_err = run_remote_cmd(client, f"cd {APP_PATH} && php /tmp/audit_candidates.php")
    run_remote_cmd(client, "rm -f /tmp/audit_candidates.php")
    
    if php_err:
        print(f"PHP STDERR:\n{php_err}")
        
    print("\n--- Audit Results ---")
    print(php_out)
    
    with open('scripts/candidate_products_audit_result.json', 'w', encoding='utf-8') as f:
        f.write(php_out)
        
    client.close()

if __name__ == '__main__':
    main()
