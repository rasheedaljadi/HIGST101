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

$splTests = [
    // S01: Exact SPL English fields
    'S01_exact_spl_en' => [
        'contact_person' => 'Rasheed Aljadi',
        'phone_country' => '966',
        'mobile_no' => '0572124578',
        'address' => 'Al Nasai',
        'city' => 'RIYADH',
        'province' => 'RIYADH',
        'zip' => $code,
        'country' => 'SA',
    ],
    // S02: Exact SPL Arabic fields
    'S02_exact_spl_ar' => [
        'contact_person' => 'رشيد الجعدي',
        'phone_country' => '966',
        'mobile_no' => '0572124578',
        'address' => 'النسائي',
        'city' => 'الرياض',
        'province' => 'الرياض',
        'zip' => $code,
        'country' => 'SA',
    ],
    // S03: SPL English with building and secondary: '2641 Al Nasai, 8125'
    'S03_spl_en_building_sec' => [
        'contact_person' => 'Rasheed Aljadi',
        'phone_country' => '966',
        'mobile_no' => '0572124578',
        'address' => '2641 Al Nasai',
        'address2' => '8125 West Naseem Dist',
        'city' => 'RIYADH',
        'province' => 'RIYADH',
        'zip' => $code,
        'country' => 'SA',
    ],
    // S04: SPL Arabic with building: '2641 شارع النسائي'
    'S04_spl_ar_building' => [
        'contact_person' => 'رشيد الجعدي',
        'phone_country' => '966',
        'mobile_no' => '0572124578',
        'address' => '2641 شارع النسائي',
        'address2' => '8125 النسيم الغربي',
        'city' => 'الرياض',
        'province' => 'الرياض',
        'zip' => $code,
        'country' => 'SA',
    ],
    // S05: Postal code 14233 in zip, RQNA2641 in address
    'S05_postal_14233_address_rqna' => [
        'contact_person' => 'Rasheed Aljadi',
        'phone_country' => '966',
        'mobile_no' => '0572124578',
        'address' => 'RQNA2641 Al Nasai',
        'city' => 'RIYADH',
        'province' => 'RIYADH',
        'zip' => '14233',
        'country' => 'SA',
    ],
    // S06: Zip = '14233-2641', Address = 'Al Nasai'
    'S06_zip_extended_address_spl' => [
        'contact_person' => 'Rasheed Aljadi',
        'phone_country' => '966',
        'mobile_no' => '0572124578',
        'address' => 'Al Nasai',
        'city' => 'RIYADH',
        'province' => 'RIYADH',
        'zip' => '14233-2641',
        'country' => 'SA',
    ],
    // S07: All caps address '2641 AL NASAI ST, WEST NASEEM DIST'
    'S07_all_caps_address' => [
        'contact_person' => 'RASHEED ALJADI',
        'phone_country' => '966',
        'mobile_no' => '0572124578',
        'address' => '2641 AL NASAI ST, WEST NASEEM DIST',
        'city' => 'RIYADH',
        'province' => 'RIYADH',
        'zip' => 'RQNA2641',
        'country' => 'SA',
    ],
];

$results = [];

foreach ($splTests as $name => $addr) {
    $outOrderId = 'SPL-' . date('YmdHis') . '-' . substr(md5($name), 0, 6);
    
    $params = [
        'param_place_order_request4_open_api_d_t_o' => [
            'out_order_id' => $outOrderId,
            'logistics_address' => $addr,
            'product_items' => [
                [
                    'product_count' => 1,
                    'product_id' => $productId,
                    'sku_id' => $skuId,
                    'sku_attr' => $skuAttr,
                    'sku_define_type' => 'sku_id',
                    'logistics_service_name' => $shippingService,
                ]
            ],
        ],
    ];

    try {
        $res = $apiClient->call('aliexpress.ds.order.create', $accessToken, $params);
        $body = $res['body'] ?? [];
        $resp = $body['aliexpress_ds_order_create_response'] ?? $body;
        $result = $resp['result'] ?? [];
        $isSuccess = $result['is_success'] ?? false;
        
        $results[$name] = [
            'is_success' => $isSuccess,
            'error_code' => $result['error_code'] ?? ($body['error_response']['code'] ?? null),
            'error_msg' => $result['error_msg'] ?? ($body['error_response']['msg'] ?? null),
            'order_list' => $result['order_list'] ?? null,
            'raw' => $body,
        ];
        
        if ($isSuccess === true) {
            $results['WINNING_PAYLOAD'] = [
                'name' => $name,
                'address' => $addr,
                'order_list' => $result['order_list'] ?? null,
            ];
            break;
        }
    } catch (\\Throwable $e) {
        $results[$name] = [
            'exception' => $e->getMessage(),
        ];
    }
    
    usleep(500000);
}

echo json_encode($results, JSON_PRETTY_PRINT);
"""

    sftp = client.open_sftp()
    remote_script_path = f"{remote_base}/scripts/probe_spl_exact_fields.php"
    with sftp.open(remote_script_path, 'w') as f:
        f.write(probe_php)
    sftp.close()
    
    try:
        cmd = f"cd {remote_base} && php scripts/probe_spl_exact_fields.php"
        code, out, err = run_remote_cmd(client, cmd)
        print(f"\n--- Output ---\n{out}")
        if err:
            print(f"\n--- STDERR ---\n{err}")
    finally:
        try:
            run_remote_cmd(client, f"rm -f {remote_script_path}")
        except Exception:
            pass
        client.close()

if __name__ == '__main__':
    main()
