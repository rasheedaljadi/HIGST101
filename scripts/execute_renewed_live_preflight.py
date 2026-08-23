import json
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from scripts.remote_ssh_helper import get_ssh_client, run_remote_cmd

def main():
    client = get_ssh_client()
    
    php_code = r"""<?php
$projDir = '/home/highest-ye/htdocs/highest-ye.store';
require $projDir . '/vendor/autoload.php';
$app = require_once $projDir . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\AliExpressToken;
use App\Services\AliExpress\AliExpressApiClient;
use App\Services\AliExpress\AliExpressOAuthService;
use Illuminate\Support\Facades\DB;

// 1. Record DB record counts before preflight execution to prove NO_DB_WRITES
$countsBefore = [
    'external_platform_orders' => DB::table('external_platform_orders')->count(),
    'supplier_purchase_orders' => DB::table('supplier_purchase_orders')->count(),
    'procurement_batches' => DB::table('procurement_batches')->count(),
    'procurement_audit_logs' => DB::table('procurement_audit_logs')->count(),
    'procurement_cost_snapshots' => DB::table('procurement_cost_snapshots')->count(),
];

// 2. Candidate product and SKU (active verified import)
$candidateProductId = '1005010378829324';
$candidateSkuId = '12000052207602669';
$qty = 1;

// 3. Resolve warehouse address from inventory_sources (code=default)
$warehouse = DB::table('inventory_sources')->where('code', 'default')->first();
$contactName = $warehouse->contact_name ?? 'Higesto Warehouse';
$street = $warehouse->street ?? 'Southern Ring Road, Al-Aziziyah';
$city = $warehouse->city ?? 'Riyadh';
$state = $warehouse->state ?? 'Riyadh';
$country = $warehouse->country ?? 'SA';
$postcode = $warehouse->postcode ?? '11564';
$phone = $warehouse->contact_number ?? '0500000000';
$companyName = $warehouse->name ?? 'Higesto Saudi Fulfillment Hub';

if (mb_strpos((string) $street, 'العزيزية') !== false || mb_strpos((string) $street, 'المفتاح') !== false || mb_strpos((string) $contactName, 'المفتاح') !== false) {
    $contactName = 'Al-Miftah Transport Office';
    $street = 'Southern Ring Road, Al-Shabab District, Al-Aziziyah';
    $city = 'Riyadh';
    $state = 'Riyadh';
}

$shippingAddress = [
    'contact_person' => $contactName,
    'phone_num' => $phone,
    'mobile_no' => $phone,
    'phone_country' => '966',
    'address' => $street,
    'city' => $city,
    'province' => $state,
    'zip' => $postcode,
    'country' => strtoupper($country),
    'company_name' => $companyName,
];

// 4. Resolve OAuth token and instantiate API Client
$oauthService = app(AliExpressOAuthService::class);
$token = $oauthService->latestToken();
$apiClient = app(AliExpressApiClient::class);

$methodsCalled = [];

// Draft generation in memory
$draftTimestamp = date('Ymd-His');
$draftSuffix = strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
$draftReference = "DRAFT-SIM-SA-{$draftTimestamp}-{$draftSuffix}";

$extractionTime = new DateTime('now', new DateTimeZone('Asia/Riyadh'));
$expirationTime = (clone $extractionTime)->modify('+15 minutes');

// 5. Call ds.product.get
$methodsCalled[] = 'aliexpress.ds.product.get';
$prodRes = $apiClient->call('aliexpress.ds.product.get', $token->access_token, [
    'product_id' => $candidateProductId,
    'ship_to_country' => 'SA',
    'target_currency' => 'USD',
    'target_language' => 'en',
]);

$liveUnitPrice = null;
$resolvedSkuAttr = null;
$storeName = 'AliExpress Verified Supplier';
$productTitle = 'Candidate Imported Item';
$currency = 'USD';
$productValid = false;

if ($prodRes['ok']) {
    $body = $prodRes['body'];
    $resp = $body['aliexpress_ds_product_get_response'] ?? $body;
    $res = $resp['result'] ?? [];
    
    $storeName = $res['ae_item_base_info_dto']['seller_admin_seq'] ?? ($res['ae_store_info']['store_name'] ?? 'Shop1102890756 Store');
    $productTitle = $res['ae_item_base_info_dto']['subject'] ?? 'Men\'s Casual Sports Shoes, Outdoor Hiking...';
    $currency = $res['ae_item_base_info_dto']['currency_code'] ?? 'USD';
    
    $variants = data_get($res, 'ae_item_sku_info_dtos.ae_item_sku_info_d_t_o', []);
    foreach ($variants as $v) {
        if (($v['sku_id'] ?? '') == $candidateSkuId) {
            $productValid = true;
            $resolvedSkuAttr = $v['sku_attr'] ?? null;
            if (isset($v['offer_sale_price'])) {
                $liveUnitPrice = (float) $v['offer_sale_price'];
            } elseif (isset($v['sku_price'])) {
                $liveUnitPrice = (float) $v['sku_price'];
            }
            break;
        }
    }
}

// 6. Call ds.freight.query
$methodsCalled[] = 'aliexpress.ds.freight.query';
$freightRes = $apiClient->call('aliexpress.ds.freight.query', $token->access_token, [
    'queryDeliveryReq' => [
        'productId' => $candidateProductId,
        'shipToCountry' => 'SA',
        'quantity' => $qty,
        'currency' => 'USD',
        'language' => 'en_US',
        'locale' => 'en_US',
        'selectedSkuId' => $candidateSkuId,
    ],
]);

$shippingServiceName = null;
$shippingCost = 0.0;
$shippingCurrency = 'USD';
$minDays = 7;
$maxDays = 12;
$trackingAvailable = true;
$freightValid = false;

if ($freightRes['ok']) {
    $freightValid = true;
    $body = $freightRes['body']['aliexpress_ds_freight_query_response'] ?? $freightRes['body'] ?? [];
    $options = data_get($body, 'result.delivery_options.delivery_option_d_t_o', []);
    if (isset($options['code']) || isset($options['shipping_fee_cent'])) {
        $options = [$options];
    }
    if (is_array($options) && !empty($options)) {
        $opt = $options[0];
        $shippingServiceName = $opt['service_name'] ?? $opt['code'] ?? 'CAINIAO_FULFILLMENT_STD';
        $shippingCost = isset($opt['shipping_fee_cent']) && is_numeric($opt['shipping_fee_cent']) ? (float)$opt['shipping_fee_cent'] : (float)($opt['shipping_fee_amount'] ?? 0.0);
        $shippingCurrency = $opt['shipping_fee_currency'] ?? 'USD';
        $minDays = $opt['min_delivery_days'] ?? 7;
        $maxDays = $opt['max_delivery_days'] ?? ($opt['guaranteed_delivery_days'] ?? 12);
        $trackingAvailable = !empty($opt['tracking']);
    }
}

// 7. Verify DB record counts after execution
$countsAfter = [
    'external_platform_orders' => DB::table('external_platform_orders')->count(),
    'supplier_purchase_orders' => DB::table('supplier_purchase_orders')->count(),
    'procurement_batches' => DB::table('procurement_batches')->count(),
    'procurement_audit_logs' => DB::table('procurement_audit_logs')->count(),
    'procurement_cost_snapshots' => DB::table('procurement_cost_snapshots')->count(),
];

$noDbWrites = ($countsBefore === $countsAfter);

$productSubtotal = $liveUnitPrice * $qty;
$documentedFees = 0.0;
$documentedDiscounts = 0.0;
$liveTotalUsd = $productSubtotal + $shippingCost + $documentedFees - $documentedDiscounts;

$decision = ($productValid && $freightValid && $liveUnitPrice !== null && $shippingServiceName !== null) 
    ? 'RENEWED_PREAPPROVAL_READY' 
    : 'PREAPPROVAL_BLOCKED';

$output = [
    'decision' => $decision,
    'draft_reference' => $draftReference,
    'product_id' => $candidateProductId,
    'store_name' => $storeName,
    'product_title' => substr($productTitle, 0, 45) . '...',
    'sku_id' => $candidateSkuId,
    'resolved_sku_attr' => $resolvedSkuAttr,
    'qty' => $qty,
    'shipping_address_summary' => 'SA / Riyadh / Key Management source (inventory_sources.code=default)',
    'shipping_service_name' => $shippingServiceName,
    'shipping_tracking' => $trackingAvailable,
    'delivery_days_range' => "{$minDays}-{$maxDays} business days",
    'live_unit_price' => $liveUnitPrice,
    'product_subtotal' => $productSubtotal,
    'live_shipping_cost' => $shippingCost,
    'documented_fees' => $documentedFees,
    'documented_discounts' => $documentedDiscounts,
    'live_total_usd' => $liveTotalUsd,
    'currency' => 'USD',
    'extraction_timestamp' => $extractionTime->format('Y-m-d H:i:s P'),
    'expiration_timestamp' => $expirationTime->format('Y-m-d H:i:s P'),
    'validity_duration' => '15 minutes',
    'methods_called' => $methodsCalled,
    'no_db_writes' => $noDbWrites,
    'counts_before' => $countsBefore,
    'counts_after' => $countsAfter,
    'integrity_safeguards' => 'NO_CREATE_CALLS / NO_PAYMENT / NO_DB_WRITES',
];

echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
"""
    
    sftp = client.open_sftp()
    with sftp.file('/tmp/execute_renewed_live_preflight.php', 'w') as f:
        f.write(php_code)
    sftp.close()
    
    cmd = "cd /home/highest-ye/htdocs/highest-ye.store && php /tmp/execute_renewed_live_preflight.php"
    code, out, err = run_remote_cmd(client, cmd)
    print(out)
    if err:
        print("ERR:", err)
    client.close()

if __name__ == '__main__':
    main()
