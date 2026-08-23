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

$variations = [
    // V01: lowercase 'rqna2641'
    'V01_lowercase_short' => [
        'contact_person' => 'Rasheed Aljadi',
        'full_name' => 'Rasheed Aljadi',
        'phone_country' => '966',
        'mobile_no' => '0572124578',
        'address' => '2641 Al Nasai St, West Naseem Dist',
        'city' => 'Riyadh',
        'province' => 'Riyadh',
        'zip' => 'rqna2641',
        'country' => 'SA',
    ],
    // V02: 14233-2641
    'V02_postal_dash_building' => [
        'contact_person' => 'Rasheed Aljadi',
        'full_name' => 'Rasheed Aljadi',
        'phone_country' => '966',
        'mobile_no' => '0572124578',
        'address' => '2641 Al Nasai St, West Naseem Dist',
        'city' => 'Riyadh',
        'province' => 'Riyadh',
        'zip' => '14233-2641',
        'country' => 'SA',
    ],
    // V03: 2641-14233
    'V03_building_dash_postal' => [
        'contact_person' => 'Rasheed Aljadi',
        'full_name' => 'Rasheed Aljadi',
        'phone_country' => '966',
        'mobile_no' => '0572124578',
        'address' => '2641 Al Nasai St, West Naseem Dist',
        'city' => 'Riyadh',
        'province' => 'Riyadh',
        'zip' => '2641-14233',
        'country' => 'SA',
    ],
    // V04: 142338125 (Postal + Secondary)
    'V04_postal_secondary_8digits' => [
        'contact_person' => 'Rasheed Aljadi',
        'full_name' => 'Rasheed Aljadi',
        'phone_country' => '966',
        'mobile_no' => '0572124578',
        'address' => '2641 Al Nasai St, West Naseem Dist',
        'city' => 'Riyadh',
        'province' => 'Riyadh',
        'zip' => '142338125',
        'country' => 'SA',
    ],
    // V05: 26418125 (Building + Secondary = 8 digits)
    'V05_building_secondary_8digits' => [
        'contact_person' => 'Rasheed Aljadi',
        'full_name' => 'Rasheed Aljadi',
        'phone_country' => '966',
        'mobile_no' => '0572124578',
        'address' => 'Al Nasai St, West Naseem Dist',
        'city' => 'Riyadh',
        'province' => 'Riyadh',
        'zip' => '26418125',
        'country' => 'SA',
    ],
    // V06: 2641-8125
    'V06_building_dash_secondary' => [
        'contact_person' => 'Rasheed Aljadi',
        'full_name' => 'Rasheed Aljadi',
        'phone_country' => '966',
        'mobile_no' => '0572124578',
        'address' => 'Al Nasai St, West Naseem Dist',
        'city' => 'Riyadh',
        'province' => 'Riyadh',
        'zip' => '2641-8125',
        'country' => 'SA',
    ],
    // V07: District An Nasim Al Gharbi + Ar Riyad
    'V07_standard_district_ar_riyad' => [
        'contact_person' => 'Rasheed Aljadi',
        'full_name' => 'Rasheed Aljadi',
        'phone_country' => '966',
        'mobile_no' => '0572124578',
        'address' => '2641 Al Nasai St',
        'district' => 'An Nasim Al Gharbi',
        'city' => 'Ar Riyad',
        'province' => 'Ar Riyad',
        'zip' => 'RQNA2641',
        'country' => 'SA',
    ],
    // V08: District West Naseem + Riyadh
    'V08_district_west_naseem' => [
        'contact_person' => 'Rasheed Aljadi',
        'full_name' => 'Rasheed Aljadi',
        'phone_country' => '966',
        'mobile_no' => '0572124578',
        'address' => '2641 Al Nasai St',
        'district' => 'West Naseem',
        'city' => 'Riyadh',
        'province' => 'Riyadh',
        'zip' => 'RQNA2641',
        'country' => 'SA',
    ],
];

$results = [];

foreach ($variations as $name => $addr) {
    $outOrderId = 'VAR-' . date('YmdHis') . '-' . substr(md5($name), 0, 6);
    
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
    remote_script_path = f"{remote_base}/scripts/probe_saudi_address_variations.php"
    with sftp.open(remote_script_path, 'w') as f:
        f.write(probe_php)
    sftp.close()
    
    cmd = f"cd {remote_base} && php scripts/probe_saudi_address_variations.php"
    code, out, err = run_remote_cmd(client, cmd)
    print(f"\n--- Output ---\n{out}")
    if err:
        print(f"\n--- STDERR ---\n{err}")
        
    run_remote_cmd(client, f"rm -f {remote_script_path}")
    
    try:
        probe_data = json.loads(out)
        out_json_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'probe_saudi_address_var_results.json')
        with open(out_json_path, 'w', encoding='utf-8') as f:
            json.dump(probe_data, f, indent=2)
        print(f"[Variations Probe] Saved result to {out_json_path}")
    except Exception as e:
        print(f"[ERROR] Could not parse var probe JSON: {e}")
        
    client.close()

if __name__ == '__main__':
    main()
