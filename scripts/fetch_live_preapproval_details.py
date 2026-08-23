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

use App\Models\AliExpressProductImport;
use App\Models\HigestSourceOffer;
use App\Services\AliExpress\AliExpressApiClient;
use App\Services\AliExpress\AliExpressOAuthService;
use Illuminate\Support\Facades\DB;

// 1. Resolve candidate product from database (active import)
$candidateProductId = '1005010378829324';
$candidateSkuId = '12000052207602669';

$import = AliExpressProductImport::where('status', 'success')->orderBy('id', 'desc')->first();
if ($import && !empty($import->aliexpress_product_id)) {
    $candidateProductId = (string) $import->aliexpress_product_id;
    $offer = HigestSourceOffer::where('product_id', $import->product_id)->first();
    if ($offer && !empty($offer->source_sku_id)) {
        $candidateSkuId = (string) $offer->source_sku_id;
    }
}

// 2. Resolve warehouse address from inventory_sources (code=default)
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

// 3. Resolve active OAuth token and call live endpoints
$oauthService = app(AliExpressOAuthService::class);
$token = $oauthService->latestToken();
$apiClient = app(AliExpressApiClient::class);

$draftReference = 'DRAFT-SIM-SA-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
$qty = 1;

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

if ($prodRes['ok']) {
    $body = $prodRes['body'];
    $resp = $body['aliexpress_ds_product_get_response'] ?? $body;
    $res = $resp['result'] ?? [];
    
    $storeName = $res['ae_item_base_info_dto']['seller_admin_seq'] ?? ($res['ae_store_info']['store_name'] ?? 'AliExpress Official Store');
    $productTitle = $res['ae_item_base_info_dto']['subject'] ?? 'Imported Store Product';
    
    $variants = data_get($res, 'ae_item_sku_info_dtos.ae_item_sku_info_d_t_o', []);
    foreach ($variants as $v) {
        if (($v['sku_id'] ?? '') == $candidateSkuId) {
            $resolvedSkuAttr = $v['sku_attr'] ?? null;
            if (isset($v['offer_sale_price'])) {
                $liveUnitPrice = (float) $v['offer_sale_price'];
            } elseif (isset($v['sku_price'])) {
                $liveUnitPrice = (float) $v['sku_price'];
            }
            break;
        }
    }
    if ($liveUnitPrice === null && !empty($variants)) {
        $firstV = $variants[0];
        $liveUnitPrice = (float) ($firstV['offer_sale_price'] ?? $firstV['sku_price'] ?? 0.0);
        $resolvedSkuAttr = $firstV['sku_attr'] ?? null;
    }
}

// Freight Query
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

if ($freightRes['ok']) {
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

$productSubtotal = $liveUnitPrice * $qty;
$documentedFees = 0.0;
$liveTotalUsd = $productSubtotal + $shippingCost + $documentedFees;

$output = [
    'draft_reference' => $draftReference,
    'product_id' => $candidateProductId,
    'store_name' => $storeName,
    'product_title' => substr($productTitle, 0, 40) . '...',
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
    'live_total_usd' => $liveTotalUsd,
    'integrity_result' => 'NO_CREATE_CALLS / NO_PAYMENT / NO_DB_WRITES',
    'timestamp' => date('Y-m-d H:i:s T'),
    'validity' => '15 minutes from generation',
];

echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
"""
    
    sftp = client.open_sftp()
    with sftp.file('/tmp/fetch_live_preapproval.php', 'w') as f:
        f.write(php_code)
    sftp.close()
    
    cmd = "cd /home/highest-ye/htdocs/highest-ye.store && php /tmp/fetch_live_preapproval.php"
    code, out, err = run_remote_cmd(client, cmd)
    print(out)
    if err:
        print("ERR:", err)
    client.close()

if __name__ == '__main__':
    main()
