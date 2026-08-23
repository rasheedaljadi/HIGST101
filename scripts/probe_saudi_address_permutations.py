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
if (!$token || empty($token->access_token)) {
    echo json_encode(['error' => 'No valid OAuth token']);
    exit(1);
}

$apiClient = app(AliExpressApiClient::class);
$accessToken = $token->access_token;

$productId = '1005010378829324';
$skuId = '12000052207602660';
$skuAttr = '14:29;200000124:200000364';
$shippingService = 'CAINIAO_FULFILLMENT_STD';

$code = 'RQNA2641';

$testPayloads = [
    // P01: Top-level national_address in param_place_order_request4_open_api_d_t_o
    'P01_top_level_national_address' => [
        'param_place_order_request4_open_api_d_t_o' => [
            'out_order_id' => 'PERM-01',
            'national_address' => $code,
            'logistics_address' => [
                'contact_person' => 'Mostafa Bama',
                'phone_country' => '966',
                'mobile_no' => '0572124578',
                'address' => '2641 Al Nasai St, West Naseem Dist',
                'city' => 'Riyadh',
                'province' => 'Riyadh',
                'zip' => $code,
                'country' => 'SA',
            ],
            'product_items' => [['product_count' => 1, 'product_id' => $productId, 'sku_id' => $skuId, 'sku_attr' => $skuAttr, 'sku_define_type' => 'sku_id', 'logistics_service_name' => $shippingService]],
        ]
    ],
    // P02: Top-level passport_no in param_place_order_request4_open_api_d_t_o
    'P02_top_level_passport_no' => [
        'param_place_order_request4_open_api_d_t_o' => [
            'out_order_id' => 'PERM-02',
            'passport_no' => $code,
            'logistics_address' => [
                'contact_person' => 'Mostafa Bama',
                'phone_country' => '966',
                'mobile_no' => '0572124578',
                'address' => '2641 Al Nasai St, West Naseem Dist',
                'city' => 'Riyadh',
                'province' => 'Riyadh',
                'zip' => $code,
                'country' => 'SA',
            ],
            'product_items' => [['product_count' => 1, 'product_id' => $productId, 'sku_id' => $skuId, 'sku_attr' => $skuAttr, 'sku_define_type' => 'sku_id', 'logistics_service_name' => $shippingService]],
        ]
    ],
    // P03: Dedicated building_number + district + short address in address fields
    'P03_building_district_fields' => [
        'param_place_order_request4_open_api_d_t_o' => [
            'out_order_id' => 'PERM-03',
            'logistics_address' => [
                'contact_person' => 'Mostafa Bama',
                'phone_country' => '966',
                'mobile_no' => '0572124578',
                'address' => 'Al Nasai St',
                'address2' => 'West Naseem Dist',
                'building_number' => '2641',
                'district' => 'West Naseem Dist',
                'city' => 'Riyadh',
                'province' => 'Riyadh',
                'zip' => $code,
                'country' => 'SA',
            ],
            'product_items' => [['product_count' => 1, 'product_id' => $productId, 'sku_id' => $skuId, 'sku_attr' => $skuAttr, 'sku_define_type' => 'sku_id', 'logistics_service_name' => $shippingService]],
        ]
    ],
    // P04: Both 5-digit zip in 'zip' and 8-digit in 'national_address_code'
    'P04_both_zip5_and_code' => [
        'param_place_order_request4_open_api_d_t_o' => [
            'out_order_id' => 'PERM-04',
            'logistics_address' => [
                'contact_person' => 'Mostafa Bama',
                'phone_country' => '966',
                'mobile_no' => '0572124578',
                'address' => '2641 Al Nasai St, West Naseem Dist',
                'city' => 'Riyadh',
                'province' => 'Riyadh',
                'zip' => '14233',
                'national_address_code' => $code,
                'short_address' => $code,
                'country' => 'SA',
            ],
            'product_items' => [['product_count' => 1, 'product_id' => $productId, 'sku_id' => $skuId, 'sku_attr' => $skuAttr, 'sku_define_type' => 'sku_id', 'logistics_service_name' => $shippingService]],
        ]
    ],
    // P05: Address string formatted exactly as: 'RQNA2641, 2641 Al Nasai St, West Naseem Dist'
    'P05_code_prefix_in_address' => [
        'param_place_order_request4_open_api_d_t_o' => [
            'out_order_id' => 'PERM-05',
            'logistics_address' => [
                'contact_person' => 'Mostafa Bama',
                'phone_country' => '966',
                'mobile_no' => '0572124578',
                'address' => 'RQNA2641, 2641 Al Nasai St, West Naseem Dist',
                'city' => 'Riyadh',
                'province' => 'Riyadh',
                'zip' => $code,
                'country' => 'SA',
            ],
            'product_items' => [['product_count' => 1, 'product_id' => $productId, 'sku_id' => $skuId, 'sku_attr' => $skuAttr, 'sku_define_type' => 'sku_id', 'logistics_service_name' => $shippingService]],
        ]
    ],
    // P06: Address string formatted as: 'Short Address: RQNA2641'
    'P06_labeled_short_address_in_line' => [
        'param_place_order_request4_open_api_d_t_o' => [
            'out_order_id' => 'PERM-06',
            'logistics_address' => [
                'contact_person' => 'Mostafa Bama',
                'phone_country' => '966',
                'mobile_no' => '0572124578',
                'address' => '2641 Al Nasai St, West Naseem Dist',
                'address2' => 'Short Address: RQNA2641',
                'city' => 'Riyadh',
                'province' => 'Riyadh',
                'zip' => '14233',
                'country' => 'SA',
            ],
            'product_items' => [['product_count' => 1, 'product_id' => $productId, 'sku_id' => $skuId, 'sku_attr' => $skuAttr, 'sku_define_type' => 'sku_id', 'logistics_service_name' => $shippingService]],
        ]
    ],
    // P07: Address in Arabic as per Saudi National Address document
    'P07_arabic_address_line' => [
        'param_place_order_request4_open_api_d_t_o' => [
            'out_order_id' => 'PERM-07',
            'logistics_address' => [
                'contact_person' => 'Mostafa Bama',
                'phone_country' => '966',
                'mobile_no' => '0572124578',
                'address' => '2641 شارع النسائي، حي النسيم الغربي',
                'city' => 'الرياض',
                'province' => 'الرياض',
                'zip' => $code,
                'country' => 'SA',
            ],
            'product_items' => [['product_count' => 1, 'product_id' => $productId, 'sku_id' => $skuId, 'sku_attr' => $skuAttr, 'sku_define_type' => 'sku_id', 'logistics_service_name' => $shippingService]],
        ]
    ],
];

$results = [];

foreach ($testPayloads as $name => $payload) {
    $outOrderId = 'PERM-' . date('YmdHis') . '-' . substr(md5($name), 0, 6);
    $payload['param_place_order_request4_open_api_d_t_o']['out_order_id'] = $outOrderId;

    try {
        $res = $apiClient->call('aliexpress.ds.order.create', $accessToken, $payload);
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
                'payload' => $payload,
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
    remote_script_path = f"{remote_base}/scripts/probe_saudi_address_permutations.php"
    with sftp.open(remote_script_path, 'w') as f:
        f.write(probe_php)
    sftp.close()
    
    print("[Permutations Probe] Uploaded probe_saudi_address_permutations.php to Staging")
    print("[Permutations Probe] Running permutation candidates...")
    
    cmd = f"cd {remote_base} && php scripts/probe_saudi_address_permutations.php"
    code, out, err = run_remote_cmd(client, cmd)
    print(f"\n--- Output ---\n{out}")
    if err:
        print(f"\n--- STDERR ---\n{err}")
        
    run_remote_cmd(client, f"rm -f {remote_script_path}")
    
    try:
        probe_data = json.loads(out)
        out_json_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'probe_saudi_address_perm_results.json')
        with open(out_json_path, 'w', encoding='utf-8') as f:
            json.dump(probe_data, f, indent=2)
        print(f"[Permutations Probe] Saved result to {out_json_path}")
    except Exception as e:
        print(f"[ERROR] Could not parse perm probe JSON: {e}")
        
    client.close()

if __name__ == '__main__':
    main()
