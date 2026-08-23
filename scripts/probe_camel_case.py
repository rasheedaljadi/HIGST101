import json
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from scripts.remote_ssh_helper import get_ssh_client, run_remote_cmd

def main():
    client = get_ssh_client()
    remote_base = "/home/highest-ye/htdocs/highest-ye.store"
    
    probe_php = """<?php
$projDir = '""" + remote_base + """';
require $projDir . '/vendor/autoload.php';
$app = require_once $projDir . '/bootstrap/app.php';
$kernel = $app->make(\\Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use App\\Services\\AliExpress\\AliExpressOAuthService;
use App\\Services\\AliExpress\\AliExpressApiClient;

$oauth = app(AliExpressOAuthService::class);
$token = $oauth->latestToken();
$apiClient = app(AliExpressApiClient::class);
$accessToken = $token->access_token;

$productId = '1005010378829324';
$skuId = '12000052207602660';
$skuAttr = '14:29;200000124:200000364';
$shippingService = 'CAINIAO_FULFILLMENT_STD';

$code = 'RQNA2641';

$tests = [
    // CC1: camelCase parameter name
    'CC1_paramPlaceOrderRequest4OpenApiDTO' => [
        'paramPlaceOrderRequest4OpenApiDTO' => [
            'outOrderId' => 'CC-01',
            'logisticsAddress' => [
                'contactPerson' => 'Rasheed Aljadi',
                'fullName' => 'Rasheed Aljadi',
                'phoneCountry' => '966',
                'mobileNo' => '0572124578',
                'phoneNum' => '0572124578',
                'address' => '2641 Al Nasai St, West Naseem Dist',
                'city' => 'Riyadh',
                'province' => 'Riyadh',
                'zip' => $code,
                'country' => 'SA',
            ],
            'productItems' => [['productCount' => 1, 'productId' => $productId, 'skuId' => $skuId, 'skuAttr' => $skuAttr, 'skuDefineType' => 'sku_id', 'logisticsServiceName' => $shippingService]],
        ]
    ],
    // CC2: snake_case with location_tree_address_id
    'CC2_location_tree_address_id' => [
        'param_place_order_request4_open_api_d_t_o' => [
            'out_order_id' => 'CC-02',
            'logistics_address' => [
                'contact_person' => 'Rasheed Aljadi',
                'phone_country' => '966',
                'mobile_no' => '0572124578',
                'address' => '2641 Al Nasai St, West Naseem Dist',
                'city' => 'Riyadh',
                'province' => 'Riyadh',
                'zip' => $code,
                'country' => 'SA',
                'location_tree_address_id' => 'SA_11',
            ],
            'product_items' => [['product_count' => 1, 'product_id' => $productId, 'sku_id' => $skuId, 'sku_attr' => $skuAttr, 'sku_define_type' => 'sku_id', 'logistics_service_name' => $shippingService]],
        ]
    ],
];

$results = [];
foreach ($tests as $k => $p) {
    try {
        $res = $apiClient->call('aliexpress.ds.order.create', $accessToken, $p);
        $results[$k] = $res['body'] ?? $res;
    } catch (\\Throwable $e) {
        $results[$k] = ['error' => $e->getMessage()];
    }
}

echo json_encode($results, JSON_PRETTY_PRINT);
"""

    sftp = client.open_sftp()
    remote_script_path = f"{remote_base}/scripts/probe_camel_case.php"
    with sftp.open(remote_script_path, 'w') as f:
        f.write(probe_php)
    sftp.close()
    
    cmd = f"cd {remote_base} && php scripts/probe_camel_case.php"
    code, out, err = run_remote_cmd(client, cmd)
    print(f"\n--- Output ---\n{out}")
    
    run_remote_cmd(client, f"rm -f {remote_script_path}")
    client.close()

if __name__ == '__main__':
    main()
