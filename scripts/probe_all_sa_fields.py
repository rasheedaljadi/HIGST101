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
$code = 'RMAD3455';

$probes = [];

// Probe 1: national_address field in logistics_address
$probes['field_national_address'] = [
    'contact_person' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'full_name' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'phone_country' => '966', 'phone_num' => '572124578', 'mobile_no' => '572124578',
    'address' => '3455 Ahmad Bin Rushd St',
    'address2' => 'Al Aziziyah Dist 7664',
    'city' => 'Riyadh', 'province' => 'Riyadh',
    'zip' => '14512', 'country' => 'SA',
    'national_address' => $code,
];

// Probe 2: national_address_code field
$probes['field_national_address_code'] = [
    'contact_person' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'full_name' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'phone_country' => '966', 'phone_num' => '572124578', 'mobile_no' => '572124578',
    'address' => '3455 Ahmad Bin Rushd St',
    'address2' => 'Al Aziziyah Dist 7664',
    'city' => 'Riyadh', 'province' => 'Riyadh',
    'zip' => '14512', 'country' => 'SA',
    'national_address_code' => $code,
];

// Probe 3: short_address field
$probes['field_short_address'] = [
    'contact_person' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'full_name' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'phone_country' => '966', 'phone_num' => '572124578', 'mobile_no' => '572124578',
    'address' => '3455 Ahmad Bin Rushd St',
    'address2' => 'Al Aziziyah Dist 7664',
    'city' => 'Riyadh', 'province' => 'Riyadh',
    'zip' => '14512', 'country' => 'SA',
    'short_address' => $code,
];

// Probe 4: short_national_address field
$probes['field_short_national_address'] = [
    'contact_person' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'full_name' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'phone_country' => '966', 'phone_num' => '572124578', 'mobile_no' => '572124578',
    'address' => '3455 Ahmad Bin Rushd St',
    'address2' => 'Al Aziziyah Dist 7664',
    'city' => 'Riyadh', 'province' => 'Riyadh',
    'zip' => '14512', 'country' => 'SA',
    'short_national_address' => $code,
];

// Probe 5: sa_national_address field
$probes['field_sa_national_address'] = [
    'contact_person' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'full_name' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'phone_country' => '966', 'phone_num' => '572124578', 'mobile_no' => '572124578',
    'address' => '3455 Ahmad Bin Rushd St',
    'address2' => 'Al Aziziyah Dist 7664',
    'city' => 'Riyadh', 'province' => 'Riyadh',
    'zip' => '14512', 'country' => 'SA',
    'sa_national_address' => $code,
];

// Probe 6: building_number + secondary_number + district + national_address
$probes['field_full_spl_breakdown'] = [
    'contact_person' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'full_name' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'phone_country' => '966', 'phone_num' => '572124578', 'mobile_no' => '572124578',
    'address' => 'Ahmad Bin Rushd St',
    'building_number' => '3455',
    'secondary_number' => '7664',
    'district' => 'Al Aziziyah',
    'city' => 'Riyadh', 'province' => 'Riyadh',
    'zip' => '14512', 'country' => 'SA',
    'national_address' => $code,
    'short_address' => $code,
];

// Probe 7: zip=RMAD3455 with NO other optional fields (clean payload)
$probes['clean_zip_rmad_only'] = [
    'contact_person' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'full_name' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'phone_country' => '966',
    'mobile_no' => '572124578',
    'address' => '3455 Ahmad Bin Rushd St',
    'city' => 'Riyadh',
    'province' => 'Riyadh',
    'zip' => 'RMAD3455',
    'country' => 'SA',
];

// Probe 8: zip=RMAD3455 with lowercase rmad3455
$probes['clean_zip_rmad_lowercase'] = [
    'contact_person' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'full_name' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'phone_country' => '966',
    'mobile_no' => '572124578',
    'address' => '3455 Ahmad Bin Rushd St',
    'city' => 'Riyadh',
    'province' => 'Riyadh',
    'zip' => 'rmad3455',
    'country' => 'SA',
];

// Probe 9: zip=14512 with tax_number=RMAD3455
$probes['field_tax_number_rmad'] = [
    'contact_person' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'full_name' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'phone_country' => '966',
    'mobile_no' => '572124578',
    'address' => '3455 Ahmad Bin Rushd St',
    'city' => 'Riyadh',
    'province' => 'Riyadh',
    'zip' => '14512',
    'country' => 'SA',
    'tax_number' => $code,
];

// Probe 10: out_order_id with top level national_address
$probes['top_level_national_address'] = [
    '_top_level' => ['national_address' => $code, 'national_address_code' => $code, 'short_address' => $code],
    'contact_person' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'full_name' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'phone_country' => '966',
    'mobile_no' => '572124578',
    'address' => '3455 Ahmad Bin Rushd St',
    'city' => 'Riyadh',
    'province' => 'Riyadh',
    'zip' => '14512',
    'country' => 'SA',
];

$idx = 0;
foreach ($probes as $name => $logistics) {
    $idx++;
    $topLevel = [];
    if (isset($logistics['_top_level'])) {
        $topLevel = $logistics['_top_level'];
        unset($logistics['_top_level']);
    }

    $dto = array_merge($topLevel, [
        'out_order_id' => 'PROBE-SA-' . time() . '-' . $idx,
        'logistics_address' => $logistics,
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
    $orders = $result['order_list'] ?? null;

    if ($success) {
        echo "🎉🎉🎉 SUCCESS! Order: " . json_encode($orders) . "\n";
        echo "WINNING FORMAT: {$name}\n";
        print_r($dto);
        break;
    } else {
        echo "❌ {$errCode}: {$errMsg}\n";
    }
    usleep(400000);
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/probe_all_sa_fields.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 probe_all_sa_fields.php && rm probe_all_sa_fields.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
