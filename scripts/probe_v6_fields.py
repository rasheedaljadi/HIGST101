import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\AliExpress\AliExpressApiClient;
use App\Services\AliExpress\AliExpressOAuthService;

$oauth = app(AliExpressOAuthService::class);
$token = $oauth->latestToken();
$accessToken = $token->access_token;
$apiClient = app(AliExpressApiClient::class);

$productId = '1005010737996063';
$skuId = '12000053357140815';
$skuAttr = '14:201447015#NO PAD';
$shippingService = 'CAINIAO_FULFILLMENT_STD';

$tests = [];

// T1: is_foreigner=false, rut_no=RMAD3455
$tests['T1_is_foreigner_false'] = [
    'logistics_address' => [
        'contact_person' => 'Mostafa Mohammed Ahmed Bamashmoos',
        'full_name' => 'Mostafa Mohammed Ahmed Bamashmoos',
        'phone_country' => '966',
        'phone_num' => '572124578',
        'mobile_no' => '572124578',
        'address' => '3455 أحمد بن رشد, حي العزيزية',
        'address2' => '7664',
        'city' => 'الرياض',
        'province' => 'منطقة الرياض',
        'zip' => 'RMAD3455',
        'country' => 'SA',
        'is_foreigner' => 'false',
        'foreigner_passport_no' => 'RMAD3455',
        'rut_no' => 'RMAD3455',
    ],
];

// T2: is_foreigner=true (maybe DS accounts are treated as foreigners)
$tests['T2_is_foreigner_true'] = [
    'logistics_address' => [
        'contact_person' => 'Mostafa Mohammed Ahmed Bamashmoos',
        'full_name' => 'Mostafa Mohammed Ahmed Bamashmoos',
        'phone_country' => '966',
        'phone_num' => '572124578',
        'mobile_no' => '572124578',
        'address' => '3455 أحمد بن رشد, حي العزيزية',
        'address2' => '7664',
        'city' => 'الرياض',
        'province' => 'منطقة الرياض',
        'zip' => 'RMAD3455',
        'country' => 'SA',
        'is_foreigner' => 'true',
        'foreigner_passport_no' => 'RMAD3455',
    ],
];

// T3: cpf field for national ID (some DS tools use cpf for national IDs)
$tests['T3_cpf_national_id'] = [
    'logistics_address' => [
        'contact_person' => 'Mostafa Mohammed Ahmed Bamashmoos',
        'full_name' => 'Mostafa Mohammed Ahmed Bamashmoos',
        'phone_country' => '966',
        'phone_num' => '572124578',
        'mobile_no' => '572124578',
        'address' => '3455 أحمد بن رشد, حي العزيزية',
        'address2' => '7664',
        'city' => 'الرياض',
        'province' => 'منطقة الرياض',
        'zip' => 'RMAD3455',
        'country' => 'SA',
        'cpf' => 'RMAD3455',
    ],
];

// T4: ALL identification fields set together
$tests['T4_all_id_fields'] = [
    'logistics_address' => [
        'contact_person' => 'Mostafa Mohammed Ahmed Bamashmoos',
        'full_name' => 'Mostafa Mohammed Ahmed Bamashmoos',
        'phone_country' => '966',
        'phone_num' => '572124578',
        'mobile_no' => '572124578',
        'address' => '3455 أحمد بن رشد, حي العزيزية, RMAD3455',
        'address2' => '7664',
        'city' => 'الرياض',
        'province' => 'منطقة الرياض',
        'zip' => 'RMAD3455',
        'country' => 'SA',
        'is_foreigner' => 'false',
        'foreigner_passport_no' => 'RMAD3455',
        'passport_no' => 'RMAD3455',
        'tax_number' => 'RMAD3455',
        'cpf' => 'RMAD3455',
        'rut_no' => 'RMAD3455',
        'vat_no' => 'RMAD3455',
    ],
];

// T5: Try locale parameter in the top-level DTO
$tests['T5_with_locale'] = [
    'locale' => 'ar_SA',
    'logistics_address' => [
        'contact_person' => 'Mostafa Mohammed Ahmed Bamashmoos',
        'full_name' => 'Mostafa Mohammed Ahmed Bamashmoos',
        'phone_country' => '966',
        'phone_num' => '572124578',
        'mobile_no' => '572124578',
        'address' => '3455 أحمد بن رشد, حي العزيزية',
        'address2' => '7664',
        'city' => 'الرياض',
        'province' => 'منطقة الرياض',
        'zip' => 'RMAD3455',
        'country' => 'SA',
    ],
];

// T6: Try with short_address as SEPARATE top-level field in the DTO
$tests['T6_top_level_short_address'] = [
    'short_address' => 'RMAD3455',
    'national_address' => 'RMAD3455',
    'logistics_address' => [
        'contact_person' => 'Mostafa Mohammed Ahmed Bamashmoos',
        'full_name' => 'Mostafa Mohammed Ahmed Bamashmoos',
        'phone_country' => '966',
        'phone_num' => '572124578',
        'mobile_no' => '572124578',
        'address' => '3455 أحمد بن رشد, حي العزيزية',
        'address2' => '7664',
        'city' => 'الرياض',
        'province' => 'منطقة الرياض',
        'zip' => 'RMAD3455',
        'country' => 'SA',
    ],
];

// T7: Try with SIMPLIFIED address (minimal fields)
$tests['T7_minimal_fields'] = [
    'logistics_address' => [
        'full_name' => 'Mostafa Bamashmoos',
        'phone_country' => '966',
        'mobile_no' => '572124578',
        'address' => 'RMAD3455',
        'city' => 'Riyadh',
        'province' => 'Riyadh',
        'zip' => 'RMAD3455',
        'country' => 'SA',
    ],
];

// T8: Try with zip containing ONLY the 4-letter prefix + building number  
$tests['T8_custom_zip_format'] = [
    'logistics_address' => [
        'contact_person' => 'Mostafa Mohammed Ahmed Bamashmoos',
        'full_name' => 'Mostafa Mohammed Ahmed Bamashmoos',
        'phone_country' => '966',
        'phone_num' => '572124578',
        'mobile_no' => '572124578',
        'address' => '3455 Ahmad Bin Rushd St, Al Aziziyah Dist, Riyadh, RMAD3455',
        'address2' => 'Short Address: RMAD3455, Additional: 7664',
        'city' => 'Riyadh',
        'province' => 'Riyadh',
        'country' => 'SA',
        'zip' => 'RMAD3455',
        'passport_no' => 'RMAD3455',
        'is_foreigner' => 'false',
    ],
];

$idx = 0;
foreach ($tests as $name => $dtoExtra) {
    $idx++;
    $logAddr = $dtoExtra['logistics_address'];
    unset($dtoExtra['logistics_address']);
    
    $dto = array_merge($dtoExtra, [
        'out_order_id' => "V6-{$name}-" . time(),
        'logistics_address' => $logAddr,
        'product_items' => [[
            'product_count' => 1,
            'product_id' => $productId,
            'sku_id' => $skuId,
            'sku_attr' => $skuAttr,
            'sku_define_type' => 'sku_id',
            'logistics_service_name' => $shippingService,
        ]],
    ]);
    
    echo "=== [{$idx}] {$name} ===\n";
    $res = $apiClient->call('aliexpress.ds.order.create', $accessToken, [
        'param_place_order_request4_open_api_d_t_o' => $dto,
    ]);
    
    $body = $res['body'] ?? [];
    $resp = $body['aliexpress_ds_order_create_response'] ?? $body;
    $result = $resp['result'] ?? [];
    $err = $body['error_response'] ?? [];
    
    $success = $result['is_success'] ?? false;
    $errCode = $result['error_code'] ?? ($err['code'] ?? null);
    $errMsg = $result['error_msg'] ?? ($err['msg'] ?? null);
    
    if ($success) {
        echo "✅✅✅ SUCCESS! Order: " . json_encode($result['order_list'] ?? null) . "\n";
        echo "WINNING DTO: " . json_encode($dto, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        break;
    } else {
        // Check if error is DIFFERENT from the usual one
        if ($errCode !== 'B_DROPSHIPPER_DELIVERY_ADDRESS_VALIDATE_FAIL') {
            echo "⚡ DIFFERENT ERROR! {$errCode}: {$errMsg}\n";
        } else {
            echo "❌ Same error: {$errCode}\n";
        }
    }
    usleep(500000);
}

echo "\n=== DONE ===\n";
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/probe_v6_fields.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 probe_v6_fields.php && rm probe_v6_fields.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
