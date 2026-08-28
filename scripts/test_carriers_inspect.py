import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

echo "=== REGISTERED CARRIERS IN CONFIG ===\n";
foreach (Config::get('carriers') as $key => $c) {
    echo "Key: {$key} | Code: {$c['code']} | Class: {$c['class']} | Active in config: " . ($c['active'] ? 'YES' : 'NO') . "\n";
    $obj = new $c['class'];
    echo "  -> isAvailable(): " . ($obj->isAvailable() ? 'YES' : 'NO') . "\n";
    echo "  -> getConfigData('active'): " . var_export(core()->getConfigData('sales.carriers.'.$obj->getCode().'.active'), true) . "\n";
}

echo "\n=== CORE CONFIG TABLE FOR CARRIERS ===\n";
$configs = DB::table('core_config')->where('code', 'like', 'sales.carriers%')->get();
foreach ($configs as $cfg) {
    echo "Code: {$cfg->code} | Value: {$cfg->value} | Channel: {$cfg->channel_code} | Locale: {$cfg->locale_code}\n";
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_carriers_inspect.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_carriers_inspect.php && rm test_carriers_inspect.php")
print(f"OUTPUT:\n{out}")
client.close()
