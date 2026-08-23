<?php

require __DIR__.'/../vendor/autoload.php';

use App\Services\AliExpress\Shipping\AliExpressShippingAddressValidator;

$val = AliExpressShippingAddressValidator::normalizeAndValidate([
    'contact_person' => 'Al-Miftah Transport',
    'phone' => '572124578',
    'phone_country' => '966',
    'street' => '2641 Al Nasai St, West Naseem Dist, RQNA2641',
    'city' => 'Riyadh',
    'province' => 'Riyadh',
    'zip' => 'RQNA2641',
    'country' => 'SA',
]);

$arr = $val->toLogisticsAddressArray();

echo json_encode($arr, JSON_PRETTY_PRINT)."\n";

assert($arr['passport_no'] === 'RQNA2641', 'passport_no must be RQNA2641');
assert($arr['address2'] === 'RQNA2641', 'address2 must be RQNA2641');
assert($arr['zip'] === 'RQNA2641', 'zip must be RQNA2641');
assert($arr['country'] === 'SA', 'country must be SA');
assert($arr['phone_country'] === '966', 'phone_country must be 966');

echo "=== ALL LOCAL PAYLOAD ASSERTIONS PASSED! ===\n";
