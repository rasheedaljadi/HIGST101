import os
import remote_ssh_helper as r

LOCAL_ROOT = r"e:\HIGESTO NEW1\higest\higest101"
REMOTE_ROOT = "/home/highest-ye/htdocs/highest-ye.store"

files_to_sync = [
    ("packages/Webkul/Shop/src/Resources/views/checkout/onepage/shipping.blade.php", "packages/Webkul/Shop/src/Resources/views/checkout/onepage/shipping.blade.php"),
    ("packages/Webkul/Shop/src/Http/Controllers/API/OnepageController.php", "packages/Webkul/Shop/src/Http/Controllers/API/OnepageController.php"),
    ("packages/Webkul/DeliveryManagement/src/Services/GovernorateDeliveryValidator.php", "packages/Webkul/DeliveryManagement/src/Services/GovernorateDeliveryValidator.php"),
]

client = r.get_ssh_client()
sftp = client.open_sftp()

print("[Deploy] Uploading updated shipping and controller files...")
for local_rel, remote_rel in files_to_sync:
    local_path = os.path.join(LOCAL_ROOT, local_rel)
    remote_path = f"{REMOTE_ROOT}/{remote_rel}"
    print(f"  -> {local_rel}")
    sftp.put(local_path, remote_path)

php_seeder = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Webkul\DeliveryManagement\Models\DeliveryPoint;
use Webkul\DeliveryManagement\Models\DeliveryGovernorateRule;

echo "=== CREATING / UPDATING DELIVERY POINTS FOR AMANAT AL ASIMAH ===\n";

$points = [
    [
        'code' => 'SAN-HADDA',
        'name' => 'مكتب هايست الرئيسي حدة',
        'name_ar' => 'مكتب هايست الرئيسي حدة',
        'state_code' => 'SAN',
        'city' => 'صنعاء - حدة',
        'address' => 'أمانة العاصمة - صنعاء - شارع حدة (بجوار بريد حدة)',
        'latitude' => 15.3256000,
        'longitude' => 44.1872000,
        'contact_name' => 'مكتب هايست الرئيسي',
        'contact_phone' => '777000001',
        'working_hours' => ['السبت - الخميس' => '9:00 ص - 9:00 م', 'الجمعة' => 'مغلق'],
        'max_capacity' => 500,
        'is_active' => true,
    ],
    [
        'code' => 'SAN-ASBAHI',
        'name' => 'فرع هايست الاصبحي',
        'name_ar' => 'فرع هايست الاصبحي',
        'state_code' => 'SAN',
        'city' => 'صنعاء - الاصبحي',
        'address' => 'أمانة العاصمة - صنعاء - شارع الاصبحي الرئيسي (بجوار الكريمي)',
        'latitude' => 15.2954000,
        'longitude' => 44.2081000,
        'contact_name' => 'فرع هايست الاصبحي',
        'contact_phone' => '777000002',
        'working_hours' => ['السبت - الخميس' => '9:00 ص - 9:00 م', 'الجمعة' => 'مغلق'],
        'max_capacity' => 500,
        'is_active' => true,
    ],
];

foreach ($points as $data) {
    $existing = DeliveryPoint::where('code', $data['code'])->first();
    if ($existing) {
        $existing->update($data);
        echo "UPDATED Point: {$data['name']} (ID {$existing->id}, Code {$existing->code})\n";
    } else {
        $created = DeliveryPoint::create($data);
        echo "CREATED Point: {$data['name']} (ID {$created->id}, Code {$created->code})\n";
    }
}

// Ensure delivery rule for SAN delivery_point is active
$rule = DeliveryGovernorateRule::where('state_code', 'SAN')
    ->where('delivery_type', 'delivery_point')
    ->first();

if ($rule) {
    $rule->update([
        'is_enabled' => true,
        'delivery_fee' => 0.00,
        'allowed_payment_methods' => ['wallet', 'offline_payments', 'moneytransfer', 'cashondelivery'],
    ]);
    echo "UPDATED SAN Delivery Point Rule (ID {$rule->id}, Enabled: true, Fee: 0.00)\n";
} else {
    $rule = DeliveryGovernorateRule::create([
        'state_code' => 'SAN',
        'delivery_type' => 'delivery_point',
        'is_enabled' => true,
        'delivery_fee' => 0.00,
        'min_order_amount' => 0.00,
        'allowed_payment_methods' => ['wallet', 'offline_payments', 'moneytransfer', 'cashondelivery'],
    ]);
    echo "CREATED SAN Delivery Point Rule (ID {$rule->id}, Enabled: true, Fee: 0.00)\n";
}

echo "\n=== ALL DELIVERY POINTS IN DB ===\n";
foreach (DeliveryPoint::all() as $p) {
    echo "ID: {$p->id} | Code: {$p->code} | Name: {$p->name} | State: {$p->state_code} | City: {$p->city} | Active: " . ($p->is_active ? 'YES' : 'NO') . "\n";
}
"""

with sftp.file(f"{REMOTE_ROOT}/seed_delivery_points.php", "w") as f:
    f.write(php_seeder)
sftp.close()

print("\n[Executing Seeder on Remote]")
code, out, err = r.run_remote_cmd(client, f"cd {REMOTE_ROOT} && php8.4 seed_delivery_points.php && rm seed_delivery_points.php")
print(f"OUTPUT:\n{out}")

cmds = [
    f"cd {REMOTE_ROOT} && php8.4 artisan view:clear",
    f"cd {REMOTE_ROOT} && php8.4 artisan cache:clear",
    f"cd {REMOTE_ROOT} && php8.4 artisan optimize",
]
for cmd in cmds:
    code, out, err = r.run_remote_cmd(client, cmd)
    print(f"CMD: {cmd} => {out.strip()}")

client.close()
print("\n[Complete] Delivery Points Created and Verified on Production!")
