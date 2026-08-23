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

$gccTests = [
    // AE: United Arab Emirates (Dubai)
    'AE_Dubai' => [
        'contact_person' => 'Rasheed Aljadi',
        'full_name' => 'Rasheed Aljadi',
        'phone_country' => '971',
        'mobile_no' => '501234567',
        'address' => 'Sheikh Zayed Road, Trade Centre 1',
        'city' => 'Dubai',
        'province' => 'Dubai',
        'zip' => '00000',
        'country' => 'AE',
    ],
    // KW: Kuwait
    'KW_KuwaitCity' => [
        'contact_person' => 'Rasheed Aljadi',
        'full_name' => 'Rasheed Aljadi',
        'phone_country' => '965',
        'mobile_no' => '91234567',
        'address' => 'Arabian Gulf St, Sharq',
        'city' => 'Kuwait City',
        'province' => 'Al Asimah',
        'zip' => '13001',
        'country' => 'KW',
    ],
    // BH: Bahrain
    'BH_Manama' => [
        'contact_person' => 'Rasheed Aljadi',
        'full_name' => 'Rasheed Aljadi',
        'phone_country' => '973',
        'mobile_no' => '36123456',
        'address' => 'Government Ave, Manama Center',
        'city' => 'Manama',
        'province' => 'Capital',
        'zip' => '304',
        'country' => 'BH',
    ],
];

$results = [];

foreach ($gccTests as $name => $addr) {
    $outOrderId = 'GCC-' . date('YmdHis') . '-' . substr(md5($name), 0, 6);
    
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
    remote_script_path = f"{remote_base}/scripts/probe_gcc_countries.php"
    with sftp.open(remote_script_path, 'w') as f:
        f.write(probe_php)
    sftp.close()
    
    try:
        cmd = f"cd {remote_base} && php scripts/probe_gcc_countries.php"
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
