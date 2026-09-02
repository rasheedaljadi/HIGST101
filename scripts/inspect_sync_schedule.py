import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use App\Models\AliExpressSetting;
use Illuminate\\Support\\Facades\\Artisan;

$settings = AliExpressSetting::current();

echo "=========================================================\\n";
echo "ALIEXPRESS SETTINGS FOR INVENTORY & PRICE SYNC:\\n";
echo "=========================================================\\n";
if ($settings) {
    echo "Sync Enabled: " . ($settings->sync_enabled ? 'YES (Active) ✅' : 'NO (Disabled) ❌') . "\\n";
    echo "Sync Schedule: " . ($settings->sync_schedule ?? 'default daily') . "\\n";
    echo "Sync Price Enabled: " . ($settings->sync_price_enabled ? 'YES' : 'NO') . "\\n";
    echo "Sync Stock Enabled: " . ($settings->sync_stock_enabled ? 'YES' : 'NO') . "\\n";
    echo "Auto Disable OOS Products: " . ($settings->auto_disable_oos ? 'YES' : 'NO') . "\\n";
} else {
    echo "No AliExpressSetting found.\\n";
}

echo "\\n=========================================================\\n";
echo "LIST OF REGISTERED SCHEDULED COMMANDS IN ARTISAN:\\n";
echo "=========================================================\\n";
Artisan::call('schedule:list');
echo Artisan::output();
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_sync_schedule.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_sync_schedule.php && rm test_sync_schedule.php")
print(f"PHP OUT:\n{out}")

code2, out2, err2 = r.run_remote_cmd(client, "crontab -l")
print(f"\nCRONTAB OUT:\n{out2}")

client.close()
