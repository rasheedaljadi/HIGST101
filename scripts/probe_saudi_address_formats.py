import json
import os
import sys
import time

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

// Product info from verified preflight
$productId = '1005010378829324';
$skuId = '12000052207602660';
$skuAttr = '14:29;200000124:200000364';
$shippingService = 'CAINIAO_FULFILLMENT_STD';

// Test matrix of address candidate structures
$candidates = [
    // 1. Short code in zip, 10-digit phone
    'T01_zip_short_phone_10' => [
        'contact_person' => 'Al-Miftah Transport',
        'phone_country' => '966',
        'mobile_no' => '0572124578',
        'phone_num' => '0572124578',
        'address' => '2641 Al Nasai St, West Naseem Dist',
        'city' => 'Riyadh',
        'province' => 'Riyadh',
        'zip' => 'RQNA2641',
        'country' => 'SA',
    ],
    // 2. Short code in zip, 9-digit phone
    'T02_zip_short_phone_9' => [
        'contact_person' => 'Al-Miftah Transport',
        'phone_country' => '966',
        'mobile_no' => '572124578',
        'phone_num' => '572124578',
        'address' => '2641 Al Nasai St, West Naseem Dist',
        'city' => 'Riyadh',
        'province' => 'Riyadh',
        'zip' => 'RQNA2641',
        'country' => 'SA',
    ],
    // 3. 5-digit zip, short code in passport_no
    'T03_zip_5digit_passport_short' => [
        'contact_person' => 'Al-Miftah Transport',
        'phone_country' => '966',
        'mobile_no' => '0572124578',
        'phone_num' => '0572124578',
        'address' => '2641 Al Nasai St, West Naseem Dist',
        'city' => 'Riyadh',
        'province' => 'Riyadh',
        'zip' => '14233',
        'passport_no' => 'RQNA2641',
        'country' => 'SA',
    ],
    // 4. 5-digit zip, short code in address2
    'T04_zip_5digit_address2_short' => [
        'contact_person' => 'Al-Miftah Transport',
        'phone_country' => '966',
        'mobile_no' => '0572124578',
        'phone_num' => '0572124578',
        'address' => '2641 Al Nasai St',
        'address2' => 'RQNA2641',
        'city' => 'Riyadh',
        'province' => 'Riyadh',
        'zip' => '14233',
        'country' => 'SA',
    ],
    // 5. Short code in zip with space: 'RQNA 2641'
    'T05_zip_short_with_space' => [
        'contact_person' => 'Al-Miftah Transport',
        'phone_country' => '966',
        'mobile_no' => '0572124578',
        'phone_num' => '0572124578',
        'address' => '2641 Al Nasai St, West Naseem Dist',
        'city' => 'Riyadh',
        'province' => 'Riyadh',
        'zip' => 'RQNA 2641',
        'country' => 'SA',
    ],
    // 6. Province/City: 'Ar Riyadh' / 'Ar Riyadh'
    'T06_ar_riyadh_naming' => [
        'contact_person' => 'Al-Miftah Transport',
        'phone_country' => '966',
        'mobile_no' => '0572124578',
        'phone_num' => '0572124578',
        'address' => '2641 Al Nasai St, West Naseem Dist',
        'city' => 'Ar Riyadh',
        'province' => 'Ar Riyadh',
        'zip' => 'RQNA2641',
        'country' => 'SA',
    ],
    // 7. Full combined address in address line 1
    'T07_full_combined_address' => [
        'contact_person' => 'Al-Miftah Transport',
        'phone_country' => '966',
        'mobile_no' => '0572124578',
        'phone_num' => '0572124578',
        'address' => '2641 Al Nasai St, 8125 West Naseem Dist, RQNA2641',
        'city' => 'Riyadh',
        'province' => 'Riyadh',
        'zip' => '14233',
        'country' => 'SA',
    ],
    // 8. Individual recipient name rather than company
    'T08_individual_name' => [
        'contact_person' => 'Mostafa Bama',
        'full_name' => 'Mostafa Bama',
        'phone_country' => '966',
        'mobile_no' => '0572124578',
        'phone_num' => '0572124578',
        'address' => '2641 Al Nasai St, West Naseem Dist',
        'city' => 'Riyadh',
        'province' => 'Riyadh',
        'zip' => 'RQNA2641',
        'country' => 'SA',
    ],
    // 9. Individual recipient name + 5-digit zip + passport_no short code
    'T09_individual_name_5digit_passport' => [
        'contact_person' => 'Mostafa Bama',
        'full_name' => 'Mostafa Bama',
        'phone_country' => '966',
        'mobile_no' => '0572124578',
        'phone_num' => '0572124578',
        'address' => '2641 Al Nasai St, West Naseem Dist',
        'city' => 'Riyadh',
        'province' => 'Riyadh',
        'zip' => '14233',
        'passport_no' => 'RQNA2641',
        'country' => 'SA',
    ],
    // 10. Only basic street name: 'Al Nasai' + short code
    'T10_simple_street_name' => [
        'contact_person' => 'Mostafa Bama',
        'phone_country' => '966',
        'mobile_no' => '0572124578',
        'phone_num' => '0572124578',
        'address' => 'Al Nasai',
        'city' => 'Riyadh',
        'province' => 'Riyadh',
        'zip' => 'RQNA2641',
        'country' => 'SA',
    ],
];

$results = [];

foreach ($candidates as $name => $addr) {
    $outOrderId = 'PROBE-' . date('YmdHis') . '-' . substr(md5($name), 0, 6);
    
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
        
        // If success is achieved, break immediately to prevent duplicate orders!
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
    
    usleep(500000); // 500ms between calls
}

echo json_encode($results, JSON_PRETTY_PRINT);
"""

    sftp = client.open_sftp()
    remote_script_path = f"{remote_base}/scripts/probe_saudi_address_formats.php"
    with sftp.open(remote_script_path, 'w') as f:
        f.write(probe_php)
    sftp.close()
    
    print("[Probe] Uploaded probe_saudi_address_formats.php to Staging")
    print("[Probe] Starting systematic address candidate probe...")
    
    cmd = f"cd {remote_base} && php scripts/probe_saudi_address_formats.php"
    code, out, err = run_remote_cmd(client, cmd)
    print(f"\n--- Output ---\n{out}")
    if err:
        print(f"\n--- STDERR ---\n{err}")
        
    run_remote_cmd(client, f"rm -f {remote_script_path}")
    
    try:
        probe_data = json.loads(out)
        out_json_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'probe_saudi_address_results.json')
        with open(out_json_path, 'w', encoding='utf-8') as f:
            json.dump(probe_data, f, indent=2)
        print(f"[Probe] Saved result to {out_json_path}")
    except Exception as e:
        print(f"[ERROR] Could not parse probe JSON: {e}")
        
    client.close()

if __name__ == '__main__':
    main()
