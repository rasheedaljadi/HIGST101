import remote_ssh_helper as r

client = r.get_ssh_client()
sftp = client.open_sftp()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Config;
use Webkul\Payment\Facades\Payment;

echo "All registered payment methods in config('payment_methods'):\n";
foreach (Config::get('payment_methods') as $code => $config) {
    echo "  - Code: {$code} | Class: {$config['class']} | Active in config: " . ($config['active'] ?? 'N/A') . "\n";
    try {
        $instance = app($config['class']);
        echo "     -> isAvailable(): " . ($instance->isAvailable() ? 'YES' : 'NO') . "\n";
        echo "     -> getTitle(): " . $instance->getTitle() . "\n";
        echo "     -> admin active config: " . core()->getConfigData('sales.payment_methods.' . $code . '.active') . "\n";
    } catch (\Throwable $e) {
        echo "     -> ERROR instantiating: " . $e->getMessage() . "\n";
    }
}
"""

with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_payment_configs.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_payment_configs.php && rm test_payment_configs.php")
print(f"CODE: {code}")
print(f"OUT:\n{out}")
print(f"ERR:\n{err}")

client.close()
