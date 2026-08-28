import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\AliExpress\AliExpressApiClient;
use App\Models\AliExpressToken;

$latestToken = AliExpressToken::latest()->first();
$apiClient = app(AliExpressApiClient::class);

$productId = '1005010737996063';
$skuId = '12000053357140815';

$allPossibleFields = [
    'contact_person' => 'Mostafa Mo Bamashmous',
    'contactPerson' => 'Mostafa Mo Bamashmous',
    'full_name' => 'Mostafa Mo Bamashmous',
    'fullName' => 'Mostafa Mo Bamashmous',
    'phone_country' => '966',
    'phoneCountry' => '966',
    'mobile_no' => '572124578',
    'mobileNo' => '572124578',
    'phone_num' => '572124578',
    'phoneNum' => '572124578',
    'country' => 'SA',
    'province' => 'Riyadh',
    'city' => 'Riyadh',
    'address' => 'حي العزيزية 3455',
    'address2' => '3455',
    'zip' => '14512',
    'postcode' => '14512',
    // All possible national address field aliases
    'national_address' => 'RMAD3455',
    'nationalAddress' => 'RMAD3455',
    'short_address' => 'RMAD3455',
    'shortAddress' => 'RMAD3455',
    'national_code' => 'RMAD3455',
    'nationalCode' => 'RMAD3455',
    'sa_national_address' => 'RMAD3455',
    'saNationalAddress' => 'RMAD3455',
    'national_number' => 'RMAD3455',
    'nationalNumber' => 'RMAD3455',
    'tax_number' => 'RMAD3455',
    'taxNumber' => 'RMAD3455',
    'passport_no' => 'RMAD3455',
    'passportNo' => 'RMAD3455',
    'cpf' => 'RMAD3455',
    'vat_no' => 'RMAD3455',
    'vatNo' => 'RMAD3455',
    'rut_no' => 'RMAD3455',
    'rutNo' => 'RMAD3455',
    'id_number' => 'RMAD3455',
    'idNumber' => 'RMAD3455',
    'id_card' => 'RMAD3455',
    'idCard' => 'RMAD3455',
    'location' => 'RMAD3455',
    'district' => 'RMAD3455',
    'short_code' => 'RMAD3455',
    'shortCode' => 'RMAD3455',
    'building_number' => '3455',
    'buildingNumber' => '3455',
    'street_number' => '3455',
    'house_number' => '3455',
    'extra_address' => 'RMAD3455',
    'extraAddress' => 'RMAD3455',
    'address3' => 'RMAD3455',
    'delivery_address' => 'RMAD3455',
    'deliveryAddress' => 'RMAD3455',
];

$params = [
    'param_place_order_request4_open_api_d_t_o' => [
        'out_order_id' => 'TEST_ALL_' . time(),
        'logistics_address' => $allPossibleFields,
        'product_items' => [
            [
                'product_count' => 1,
                'product_id' => $productId,
                'sku_id' => $skuId,
                'sku_attr' => '14:201447015#NO PAD',
                'sku_define_type' => 'sku_id',
                'logistics_service_name' => 'CAINIAO_FULFILLMENT_STD',
            ]
        ],
    ],
];

$res = $apiClient->call('aliexpress.ds.order.create', $latestToken->access_token, $params);
$body = $res['body'] ?? [];
$result = $body['aliexpress_ds_order_create_response']['result'] ?? [];

echo "Result:\n";
print_r($result);
echo "Full Body:\n";
print_r($body);
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_all_fields.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_all_fields.php && rm test_all_fields.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
