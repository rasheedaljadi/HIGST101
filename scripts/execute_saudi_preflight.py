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
use App\Models\AliExpressToken;
use App\Models\HigestSourceOffer;
use App\Services\AliExpress\AliExpressApiClient;
use App\Services\AliExpress\AliExpressOAuthService;
use Illuminate\Support\Facades\DB;

// 1. Resolve candidate product from database
$candidateProductId = '1005008248073626';
$candidateSkuId = '12000044371414236';

$import = AliExpressProductImport::where('status', 'success')->orderBy('id', 'desc')->first();
if ($import && !empty($import->aliexpress_product_id)) {
    $candidateProductId = (string) $import->aliexpress_product_id;
    $offer = HigestSourceOffer::where('product_id', $import->product_id)->first();
    if ($offer && !empty($offer->source_sku_id)) {
        $candidateSkuId = (string) $offer->source_sku_id;
    }
}

// 2. Resolve warehouse address from inventory_sources
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

// 3. Resolve active OAuth token
$oauthService = app(AliExpressOAuthService::class);
$token = $oauthService->latestToken();
$apiClient = app(AliExpressApiClient::class);

$resolvedSkuAttr = null;
$productGetOk = false;
$productGetError = null;

if ($token && $token->isAccessTokenValid()) {
    // A. Product Get & SKU Resolution
    try {
        $prodRes = $apiClient->call('aliexpress.ds.product.get', $token->access_token, [
            'product_id' => $candidateProductId,
            'ship_to_country' => $shippingAddress['country'],
            'target_currency' => 'USD',
            'target_language' => 'en',
        ]);
        if ($prodRes['ok']) {
            $productGetOk = true;
            $variants = data_get($prodRes['body'], 'aliexpress_ds_product_get_response.result.ae_item_sku_info_dtos.ae_item_sku_info_d_t_o', []);
            foreach ($variants as $v) {
                if (($v['sku_id'] ?? '') == $candidateSkuId && !empty($v['sku_attr'])) {
                    $resolvedSkuAttr = $v['sku_attr'];
                    break;
                }
            }
        } else {
            $productGetError = $prodRes['message'] ?? $prodRes['code'] ?? 'Product get failed';
        }
    } catch (\Throwable $e) {
        $productGetError = $e->getMessage();
    }

    // B. Freight Query
    $freightOk = false;
    $freightError = null;
    $bestOption = null;
    $optionsCount = 0;

    try {
        $freightReq = [
            'productId' => $candidateProductId,
            'shipToCountry' => $shippingAddress['country'],
            'quantity' => 1,
            'currency' => 'USD',
            'language' => 'en_US',
            'locale' => 'en_US',
        ];
        if (!empty($candidateSkuId)) {
            $freightReq['selectedSkuId'] = $candidateSkuId;
        }

        $freightRes = $apiClient->call('aliexpress.ds.freight.query', $token->access_token, [
            'queryDeliveryReq' => $freightReq,
        ]);

        if ($freightRes['ok']) {
            $freightOk = true;
            $body = $freightRes['body']['aliexpress_ds_freight_query_response'] ?? $freightRes['body'] ?? [];
            $options = data_get($body, 'result.delivery_options.delivery_option_d_t_o', []);

            if ((!is_array($options) || empty($options)) && isset($freightReq['selectedSkuId'])) {
                unset($freightReq['selectedSkuId']);
                $fallbackRes = $apiClient->call('aliexpress.ds.freight.query', $token->access_token, [
                    'queryDeliveryReq' => $freightReq,
                ]);
                if ($fallbackRes['ok']) {
                    $fallbackBody = $fallbackRes['body']['aliexpress_ds_freight_query_response'] ?? $fallbackRes['body'] ?? [];
                    $options = data_get($fallbackBody, 'result.delivery_options.delivery_option_d_t_o', []);
                }
            }

            if (is_array($options) && !empty($options)) {
                if (isset($options['code']) || isset($options['shipping_fee_cent'])) {
                    $options = [$options];
                }
                $optionsCount = count($options);
                $bestCost = null;
                foreach ($options as $opt) {
                    $cost = isset($opt['shipping_fee_cent']) && is_numeric($opt['shipping_fee_cent'])
                        ? (float) $opt['shipping_fee_cent']
                        : (float) ($opt['shipping_fee_amount'] ?? 0.0);
                    if ($bestOption === null || $cost < $bestCost) {
                        $bestOption = $opt;
                        $bestCost = $cost;
                    }
                }
            }
        } else {
            $freightError = $freightRes['message'] ?? $freightRes['code'] ?? 'Freight query failed';
        }
    } catch (\Throwable $e) {
        $freightError = $e->getMessage();
    }
}

$maskedAddress = [
    'contact_person' => substr($shippingAddress['contact_person'], 0, 4) . '****',
    'country' => $shippingAddress['country'],
    'city' => $shippingAddress['city'],
    'province' => $shippingAddress['province'],
    'zip' => substr($shippingAddress['zip'], 0, 4) . '****',
    'phone_country' => $shippingAddress['phone_country'],
    'phone_num' => substr($shippingAddress['phone_num'], 0, 3) . '****' . substr($shippingAddress['phone_num'], -2),
    'address' => substr($shippingAddress['address'], 0, 15) . ' [MASKED]',
    'company_name' => $shippingAddress['company_name'],
];

$output = [
    'candidate_product_id' => $candidateProductId,
    'candidate_sku_id' => $candidateSkuId,
    'warehouse_shipping_address_source' => 'inventory_sources (code=default)',
    'warehouse_shipping_address' => $maskedAddress,
    'product_get' => [
        'is_success' => $productGetOk,
        'resolved_sku_attr' => $resolvedSkuAttr,
        'error' => $productGetError,
    ],
    'freight_preflight' => [
        'is_success' => $freightOk,
        'options_count' => $optionsCount,
        'selected_option' => $bestOption ? [
            'service_name' => $bestOption['service_name'] ?? $bestOption['code'] ?? 'Unknown',
            'code' => $bestOption['code'] ?? null,
            'cost' => isset($bestOption['shipping_fee_cent']) && is_numeric($bestOption['shipping_fee_cent']) ? (float)$bestOption['shipping_fee_cent'] : (float)($bestOption['shipping_fee_amount'] ?? 0.0),
            'currency' => $bestOption['shipping_fee_currency'] ?? 'USD',
            'min_days' => $bestOption['min_delivery_days'] ?? null,
            'max_days' => $bestOption['max_delivery_days'] ?? ($bestOption['guaranteed_delivery_days'] ?? null),
            'tracking' => !empty($bestOption['tracking']),
        ] : null,
        'error' => $freightError,
    ],
];

echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
"""
    
    sftp = client.open_sftp()
    with sftp.file('/tmp/preflight_script.php', 'w') as f:
        f.write(php_code)
    sftp.close()
    
    cmd = "cd /home/highest-ye/htdocs/highest-ye.store && php /tmp/preflight_script.php"
    code, out, err = run_remote_cmd(client, cmd)
    print(out)
    if err:
        print("ERR:", err)
    client.close()

if __name__ == '__main__':
    main()
