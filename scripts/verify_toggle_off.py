import remote_ssh_helper as r

client = r.get_ssh_client()
sftp = client.open_sftp()

php_test = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

$request = new \\Illuminate\\Http\\Request();
$request->replace([
    'section' => 'shipping',
    'shipping_extra_days' => 0,
    'shipping_margin' => 0,
    'shipping_enabled' => 0,
    'include_shipping_in_price' => 0,
    'exclude_choice_from_shipping_price' => 0,
]);

$controller = app(\\App\\Http\\Controllers\\AliExpress\\AliExpressKeysController::class);
$response = $controller->store($request);

$settings = \\App\\Models\\AliExpressSetting::first();
echo "Updated settings in DB (Toggled Off): include_shipping=" . ($settings->include_shipping_in_price ? 'true':'false') . ", exclude_choice=" . ($settings->exclude_choice_from_shipping_price ? 'true':'false') . ", extra_days=" . $settings->shipping_extra_days . "\\n";
"""

with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_save_shipping_runner2.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_save_shipping_runner2.php && rm test_save_shipping_runner2.php")
print(f"CODE: {code}")
print(f"OUT:\n{out}")

client.close()
