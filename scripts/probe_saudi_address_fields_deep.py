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

// Test matrix with different field name placements & combinations
$candidates = [
    // D01: All known national identity fields populated simultaneously with RQNA2641
    'D01_all_identity_fields' => [
        'contact_person' => 'Mostafa Bama',
        'full_name' => 'Mostafa Bama',
        'phone_country' => '966',
        'mobile_no' => '0572124578',
        'phone_num' => '0572124578',
        'address' => '2641 Al Nasai St, West Naseem Dist',
        'address2' => 'RQNA2641',
        'city' => 'Riyadh',
        'province' => 'Riyadh',
        'zip' => '14233',
        'country' => 'SA',
        'national_address' => 'RQNA2641',
        'national_number' => 'RQNA2641',
        'national_code' => 'RQNA2641',
        'national_id' => 'RQNA2641',
        'short_address' => 'RQNA2641',
        'tax_number' => 'RQNA2641',
        'passport_no' => 'RQNA2641',
        'foreigner_passport_no' => 'RQNA2641',
    ],
    // D02: All known fields with zip = RQNA2641
    'D02_all_identity_fields_zip_short' => [
        'contact_person' => 'Mostafa Bama',
        'full_name' => 'Mostafa Bama',
        'phone_country' => '966',
        'mobile_no' => '0572124578',
        'phone_num' => '0572124578',
        'address' => '2641 Al Nasai St, West Naseem Dist',
        'address2' => 'RQNA2641',
        'city' => 'Riyadh',
        'province' => 'Riyadh',
        'zip' => 'RQNA2641',
        'country' => 'SA',
        'national_address' => 'RQNA2641',
        'national_number' => 'RQNA2641',
        'national_code' => 'RQNA2641',
        'national_id' => 'RQNA2641',
        'short_address' => 'RQNA2641',
        'tax_number' => 'RQNA2641',
        'passport_no' => 'RQNA2641',
        'foreigner_passport_no' => 'RQNA2641',
    ],
    // D03: Example fixture 'ABCD1234' literally from error message
    'D03_literal_abcd1234_fixture' => [
        'contact_person' => 'Mostafa Bama',
        'phone_country' => '966',
        'mobile_no' => '0572124578',
        'phone_num' => '0572124578',
        'address' => 'Al Nasai St, West Naseem Dist',
        'city' => 'Riyadh',
        'province' => 'Riyadh',
        'zip' => 'ABCD1234',
        'country' => 'SA',
    ],
    // D04: Real SPL registered short address fixture: RNNA4124
    'D04_rnna4124_fixture' => [
        'contact_person' => 'Mostafa Bama',
        'phone_country' => '966',
        'mobile_no' => '0572124578',
        'phone_num' => '0572124578',
        'address' => 'King Fahd Rd, Al Olaya',
        'city' => 'Riyadh',
        'province' => 'Riyadh',
        'zip' => 'RNNA4124',
        'country' => 'SA',
    ],
    // D05: Country = 'SAU' (ISO-3)
    'D05_country_sau_iso3' => [
        'contact_person' => 'Mostafa Bama',
        'phone_country' => '966',
        'mobile_no' => '0572124578',
        'phone_num' => '0572124578',
        'address' => '2641 Al Nasai St, West Naseem Dist',
        'city' => 'Riyadh',
        'province' => 'Riyadh',
        'zip' => 'RQNA2641',
        'country' => 'SAU',
    ],
    // D06: Location tree / province 'Riyadh Province' + 'Riyadh'
    'D06_riyadh_province_string' => [
        'contact_person' => 'Mostafa Bama',
        'phone_country' => '966',
        'mobile_no' => '0572124578',
        'phone_num' => '0572124578',
        'address' => '2641 Al Nasai St, West Naseem Dist',
        'city' => 'Riyadh',
        'province' => 'Riyadh Province',
        'zip' => 'RQNA2641',
        'country' => 'SA',
    ],
    // D07: Without province (province empty or same as city)
    'D07_province_riyadh_city_riyadh' => [
        'contact_person' => 'Mostafa Bama',
        'phone_country' => '966',
        'mobile_no' => '0572124578',
        'phone_num' => '0572124578',
        'address' => '2641 Al Nasai St, West Naseem Dist, Short Address: RQNA2641',
        'city' => 'Riyadh',
        'province' => 'Riyadh',
        'zip' => 'RQNA2641',
        'country' => 'SA',
    ],
    // D08: Full short address formatted as 'RQNA-2641'
    'D08_hyphenated_short_address' => [
        'contact_person' => 'Mostafa Bama',
        'phone_country' => '966',
        'mobile_no' => '0572124578',
        'phone_num' => '0572124578',
        'address' => '2641 Al Nasai St, West Naseem Dist',
        'city' => 'Riyadh',
        'province' => 'Riyadh',
        'zip' => 'RQNA-2641',
        'country' => 'SA',
    ],
];

$results = [];

foreach ($candidates as $name => $addr) {
    $outOrderId = 'PROBE2-' . date('YmdHis') . '-' . substr(md5($name), 0, 6);
    
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
    remote_script_path = f"{remote_base}/scripts/probe_saudi_address_fields_deep.php"
    with sftp.open(remote_script_path, 'w') as f:
        f.write(probe_php)
    sftp.close()
    
    print("[Deep Probe] Uploaded probe_saudi_address_fields_deep.php to Staging")
    print("[Deep Probe] Starting deep address candidate probe...")
    
    cmd = f"cd {remote_base} && php scripts/probe_saudi_address_fields_deep.php"
    code, out, err = run_remote_cmd(client, cmd)
    print(f"\n--- Output ---\n{out}")
    if err:
        print(f"\n--- STDERR ---\n{err}")
        
    run_remote_cmd(client, f"rm -f {remote_script_path}")
    
    try:
        probe_data = json.loads(out)
        out_json_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'probe_saudi_address_deep_results.json')
        with open(out_json_path, 'w', encoding='utf-8') as f:
            json.dump(probe_data, f, indent=2)
        print(f"[Deep Probe] Saved result to {out_json_path}")
    except Exception as e:
        print(f"[ERROR] Could not parse deep probe JSON: {e}")
        
    client.close()

if __name__ == '__main__':
    main()
