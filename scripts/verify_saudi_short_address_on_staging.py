import json
import os
import sys

# Ensure UTF-8 output encoding for Windows console
if sys.platform == 'win32':
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')
    sys.stderr.reconfigure(encoding='utf-8', errors='replace')

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from scripts.remote_ssh_helper import get_ssh_client, run_remote_cmd

def main():
    client = get_ssh_client()
    remote_base = "/home/highest-ye/htdocs/highest-ye.store"
    
    script_php = """<?php
$projDir = '""" + remote_base + """';
require $projDir . '/vendor/autoload.php';
$app = require_once $projDir . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Illuminate\\Support\\Facades\\DB;
use Webkul\\Procurement\\Gateways\\AliExpressOrderSubmissionGateway;
use Webkul\\Fulfillment\\DataObjects\\ShippingAddress;

// 1. Baseline Counts Before Check
$tables = [
    'inventory_sources',
    'supplier_purchase_orders',
    'external_platform_orders',
    'procurement_audit_logs',
    'orders',
    'order_items',
    'product_inventories',
    'invoices',
    'shipments',
    'refunds'
];

$countsBefore = [];
foreach ($tables as $t) {
    $countsBefore[$t] = DB::table($t)->count();
}

// 2. Inspect Key Management Source
$source = DB::table('inventory_sources')->where('code', 'default')->first();

if (!$source) {
    echo json_encode(['ruling' => 'BLOCKER_DEFAULT_INVENTORY_SOURCE_MISSING']);
    exit(1);
}

$rawPostcode = (string) ($source->postcode ?? '');
$cleanPostcode = strtoupper(trim($rawPostcode));
$postcodeLen = strlen($cleanPostcode);
$matchesPattern = (bool) preg_match('/^[A-Z]{4}[0-9]{4}$/', $cleanPostcode);

$maskedPostcode = ($postcodeLen >= 4)
    ? substr($cleanPostcode, 0, 2) . '****' . substr($cleanPostcode, -2)
    : '****';

$companionFields = [
    'contact_name' => !empty(trim((string)($source->contact_name ?? ''))),
    'contact_number' => !empty(trim((string)($source->contact_number ?? ''))),
    'street' => !empty(trim((string)($source->street ?? ''))),
    'city' => !empty(trim((string)($source->city ?? ''))),
    'state' => !empty(trim((string)($source->state ?? ''))),
    'country_is_sa' => (strtoupper(trim((string)($source->country ?? ''))) === 'SA'),
];

$allCompanionsNonEmpty = !in_array(false, array_values($companionFields), true);

// 3. Inspect V1 Address Builder (Read-Only Simulation)
$v1AddressStr = $source->street ?? '';
$v1FirstName = $source->contact_name ?? 'Al-Miftah';
$v1LastName = 'Transport Office';
if (mb_strpos($v1AddressStr, 'العزيزية') !== false || mb_strpos($v1AddressStr, 'المفتاح') !== false) {
    $v1FirstName = 'Al-Miftah';
    $v1LastName = 'Transport Office';
    $v1AddressStr = 'Southern Ring Road, Al-Shabab District, Al-Aziziyah';
}

$v1ShippingAddress = new ShippingAddress(
    firstName: $v1FirstName,
    lastName: $v1LastName,
    address: $v1AddressStr,
    city: $source->city ?? 'Riyadh',
    state: $source->state ?? 'Riyadh',
    postcode: $cleanPostcode,
    country: $source->country ?? 'SA',
    phone: $source->contact_number ?? '0500000000',
    email: $source->contact_email ?? 'warehouse@example.com',
    companyName: $source->name ?? 'Al-Miftah Main Hub'
);

$v1PayloadAddress = [
    'contact_person' => $v1ShippingAddress->fullName(),
    'phone_num' => $v1ShippingAddress->phone ?? '',
    'mobile_no' => $v1ShippingAddress->phone ?? '',
    'phone_country' => '966',
    'address' => $v1ShippingAddress->address,
    'city' => $v1ShippingAddress->city,
    'province' => $v1ShippingAddress->state ?? '',
    'zip' => $v1ShippingAddress->postcode ?? '',
    'country' => $v1ShippingAddress->country ?? '',
    'company_name' => $v1ShippingAddress->companyName ?? '',
];

$v1Zip = (string) ($v1PayloadAddress['zip'] ?? '');
$v1Verification = [
    'zip_present' => isset($v1PayloadAddress['zip']),
    'zip_length' => strlen($v1Zip),
    'zip_matches_pattern' => (bool) preg_match('/^[A-Z]{4}[0-9]{4}$/', $v1Zip),
    'zip_equals_normalized_source' => ($v1Zip === $cleanPostcode),
    'contact_person_present' => !empty($v1PayloadAddress['contact_person']),
    'phone_country_is_966' => ($v1PayloadAddress['phone_country'] === '966'),
    'mobile_present' => !empty($v1PayloadAddress['mobile_no']),
    'address_present' => !empty($v1PayloadAddress['address']),
    'city_present' => !empty($v1PayloadAddress['city']),
    'province_present' => !empty($v1PayloadAddress['province']),
    'country_is_sa' => ($v1PayloadAddress['country'] === 'SA'),
];

// 4. Inspect V2 Address Builder (AliExpressOrderSubmissionGateway)
$gateway = app(AliExpressOrderSubmissionGateway::class);
$v2Address = $gateway->resolveWarehouseShippingAddress();

$v2Zip = (string) ($v2Address['zip'] ?? '');
$v2Verification = [
    'zip_present' => isset($v2Address['zip']),
    'zip_length' => strlen($v2Zip),
    'zip_matches_pattern' => (bool) preg_match('/^[A-Z]{4}[0-9]{4}$/', $v2Zip),
    'zip_equals_normalized_source' => ($v2Zip === $cleanPostcode),
    'contact_person_present' => !empty($v2Address['contact_person']),
    'phone_country_is_966' => ($v2Address['phone_country'] === '966'),
    'mobile_present' => !empty($v2Address['mobile_no']),
    'address_present' => !empty($v2Address['address']),
    'city_present' => !empty($v2Address['city']),
    'province_present' => !empty($v2Address['province']),
    'country_is_sa' => ($v2Address['country'] === 'SA'),
];

// 5. Test Invalid SA Fixture Guard
$invalidFixtureThrown = false;
$invalidFixtureException = null;
try {
    $gateway->resolveWarehouseShippingAddress([
        'contact_person' => 'Test',
        'phone_num' => '0500000000',
        'mobile_no' => '0500000000',
        'phone_country' => '966',
        'address' => 'Test Street',
        'city' => 'Riyadh',
        'province' => 'Riyadh',
        'zip' => '11564', // 5-digit invalid SA postal
        'country' => 'SA',
    ]);
} catch (\\DomainException $e) {
    $invalidFixtureThrown = true;
    $invalidFixtureException = $e->getMessage();
}

$guardStatus = $invalidFixtureThrown ? 'GUARD_ACTIVE' : 'ADDRESS_GUARD_IMPLEMENTATION_REQUIRED';

// 6. Baseline Counts After Check
$countsAfter = [];
foreach ($tables as $t) {
    $countsAfter[$t] = DB::table($t)->count();
}

$deltas = [];
$allZeroDeltas = true;
foreach ($tables as $t) {
    $delta = $countsAfter[$t] - $countsBefore[$t];
    $deltas[$t] = $delta;
    if ($delta !== 0) {
        $allZeroDeltas = false;
    }
}

$isReady = (
    $matchesPattern &&
    $postcodeLen === 8 &&
    $allCompanionsNonEmpty &&
    $v1Verification['zip_matches_pattern'] &&
    $v1Verification['zip_equals_normalized_source'] &&
    $v2Verification['zip_matches_pattern'] &&
    $v2Verification['zip_equals_normalized_source'] &&
    $allZeroDeltas
);

echo json_encode([
    'ruling' => $isReady ? 'SA_SHORT_ADDRESS_SAVED_AND_PAYLOAD_READY' : 'SA_SHORT_ADDRESS_VERIFICATION_FAILED',
    'timestamp' => date('Y-m-d H:i:s P'),
    'source_inspection' => [
        'table' => 'inventory_sources',
        'code' => 'default',
        'postcode_masked' => $maskedPostcode,
        'postcode_length' => $postcodeLen,
        'matches_pattern' => $matchesPattern,
        'companion_fields' => $companionFields,
    ],
    'v1_verification' => $v1Verification,
    'v2_verification' => $v2Verification,
    'invalid_sa_fixture_guard' => [
        'status' => $guardStatus,
        'exception' => $invalidFixtureException,
    ],
    'db_deltas' => $deltas,
    'all_zero_deltas' => $allZeroDeltas,
], JSON_PRETTY_PRINT);
?>""";

    sftp = client.open_sftp()
    remote_script_path = f"{remote_base}/scripts/verify_sa_short_address.php"
    with sftp.open(remote_script_path, 'w') as f:
        f.write(script_php)
    sftp.close()
    
    print("[SSH] Uploaded address verification script. Executing read-only check on Staging...")
    cmd = f"cd {remote_base} && php scripts/verify_sa_short_address.php"
    code, out, err = run_remote_cmd(client, cmd)
    print(f"\n--- Staging Address Verification Output ---\n{out}")
    if err:
        print(f"\n--- Staging STDERR ---\n{err}")
        
    client.close()
    
    # Save output locally
    try:
        data = json.loads(out)
        with open('scripts/sa_short_address_verification_result.json', 'w', encoding='utf-8') as f:
            json.dump(data, f, indent=2, ensure_ascii=False)
        print("\n[Result] Saved to scripts/sa_short_address_verification_result.json")
    except Exception as e:
        print(f"[Result] Could not parse JSON output: {e}")

if __name__ == '__main__':
    main()
