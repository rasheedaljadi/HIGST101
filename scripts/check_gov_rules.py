import remote_ssh_helper as r

client = r.get_ssh_client()
sftp = client.open_sftp()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Webkul\DeliveryManagement\Services\ShippingMethodAdapter;
use Webkul\DeliveryManagement\Services\GovernorateDeliveryValidator;

$adapter = app(ShippingMethodAdapter::class);
$validator = app(GovernorateDeliveryValidator::class);

echo "=== SHIPPING METHOD CANONICALIZATION ===\n";
$shippingMethods = ['flatrate_flatrate', 'free_free', 'courier_courier', 'delivery_point', 'home_delivery', 'mpcourier_mpcourier'];
foreach ($shippingMethods as $sm) {
    echo "  - '{$sm}' => '" . $adapter->canonicalize($sm) . "'\n";
}

echo "\n=== DELIVERY GOVERNORATE RULES IN DB ===\n";
$rules = DB::table('delivery_governorate_rules')->get();
echo "Total rules in DB: " . $rules->count() . "\n";
foreach ($rules as $r) {
    echo "  - State: '{$r->state_code}' | Type: '{$r->delivery_type}' | Enabled: {$r->is_enabled} | Allowed Methods: " . json_encode($r->allowed_payment_methods) . "\n";
}

echo "\n=== TESTING ACTIVE RULES FOR DIFFERENT STATES ===\n";
$states = ['Sanaa', 'SANAA', 'SA', 'YE', 'AD', 'Adan', 'Aden', 'صنعاء', 'عدن'];
foreach ($states as $st) {
    $ruleHome = $validator->getActiveRule($st, 'home_delivery');
    $rulePoint = $validator->getActiveRule($st, 'delivery_point');
    echo "  - State '{$st}':\n";
    echo "      home_delivery rule: " . ($ruleHome ? "ID {$ruleHome->id}, Enabled: {$ruleHome->is_enabled}, Methods: " . json_encode($ruleHome->allowed_payment_methods) : "NULL") . "\n";
    echo "      delivery_point rule: " . ($rulePoint ? "ID {$rulePoint->id}, Enabled: {$rulePoint->is_enabled}, Methods: " . json_encode($rulePoint->allowed_payment_methods) : "NULL") . "\n";
}
"""

with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_gov_rules.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_gov_rules.php && rm test_gov_rules.php")
print(f"CODE: {code}")
print(f"OUT:\n{out}")

client.close()
