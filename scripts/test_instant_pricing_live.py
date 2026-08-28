import urllib.request
import time
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
    'shipping_enabled' => 1,
    'include_shipping_in_price' => 1,
    'exclude_choice_from_shipping_price' => 1,
]);

$controller = app(\\App\\Http\\Controllers\\AliExpress\\AliExpressKeysController::class);
$response = $controller->store($request);

echo "1. Shipping Settings Saved (include_shipping = 1)\\n";
"""

with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_save_shipping_now.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_save_shipping_now.php && rm test_save_shipping_now.php")
print(f"CODE: {code}")
print(f"OUT:\n{out}")

client.close()

print("\\n[Test] Fetching Feelworld Product URL to test instant on-demand price refresh...")
time.sleep(1)

url = "https://highest-ye.store/feelworld-lut7-7-inch-2200nit-touchscreen-4k-hdmi-camera-field-monitor-with-3d-lut-waveform-automatic-light-sensor-1920x1200"
req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0'})
try:
    with urllib.request.urlopen(req, timeout=15) as resp:
        html = resp.read().decode('utf-8')
        print(f"Fetched {len(html)} bytes")
        
        # Check price in HTML
        lines = html.splitlines()
        for i, l in enumerate(lines):
            if any(term in l for term in ['307.24', '252.24', '274.99', '219.99']):
                print(f"Match line {i+1}: {l.strip()}")
except Exception as e:
    print(f"Error: {e}")
