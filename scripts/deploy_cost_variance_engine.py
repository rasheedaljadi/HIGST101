import remote_ssh_helper as r
import os

client = r.get_ssh_client()
sftp = client.open_sftp()

files_to_sync = [
    ("database/migrations/2026_08_29_000001_add_cost_variance_settings_to_aliexpress_settings_table.php", "database/migrations/2026_08_29_000001_add_cost_variance_settings_to_aliexpress_settings_table.php"),
    ("app/Models/AliExpressSetting.php", "app/Models/AliExpressSetting.php"),
    ("app/Http/Controllers/AliExpress/AliExpressKeysController.php", "app/Http/Controllers/AliExpress/AliExpressKeysController.php"),
    ("resources/views/aliexpress/keys.blade.php", "resources/views/aliexpress/keys.blade.php"),
    ("packages/Webkul/Procurement/src/Services/ProcurementBatchService.php", "packages/Webkul/Procurement/src/Services/ProcurementBatchService.php"),
    ("packages/Webkul/Procurement/src/Services/ProcurementSubmitService.php", "packages/Webkul/Procurement/src/Services/ProcurementSubmitService.php"),
    ("packages/Webkul/Procurement/src/Services/AliExpressPollingService.php", "packages/Webkul/Procurement/src/Services/AliExpressPollingService.php"),
]

local_base = r"e:\HIGESTO NEW1\higest\higest101"
remote_base = "/home/highest-ye/htdocs/highest-ye.store"

for rel_local, rel_remote in files_to_sync:
    local_path = os.path.join(local_base, rel_local)
    remote_path = f"{remote_base}/{rel_remote}"
    print(f"Uploading {rel_local} -> {remote_path}")
    sftp.put(local_path, remote_path)

sftp.close()

# 1. Run Migration
code, out, err = r.run_remote_cmd(client, f"cd {remote_base} && php8.4 artisan migrate --force")
print(f"Migration Output: CODE {code}\n{out}")

# 2. Clear cache
code, out, err = r.run_remote_cmd(client, f"cd {remote_base} && php8.4 artisan view:clear && php8.4 artisan cache:clear")
print(f"Cache Clear: CODE {code}\n{out}")

# 3. Test verification script on server
test_php = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use App\\Models\\AliExpressSetting;
use App\\Http\\Controllers\\AliExpress\\AliExpressKeysController;
use Illuminate\\Http\\Request;

echo "=========================================================\\n";
echo "1. VERIFYING ALIEXPRESS SETTING SCHEMA & VALUES\\n";
echo "=========================================================\\n";
$settings = AliExpressSetting::current();
echo "variance_product_type: " . ($settings->variance_product_type ?? 'NULL') . "\\n";
echo "variance_product_limit: " . ($settings->variance_product_limit ?? 'NULL') . "\\n";
echo "variance_shipping_type: " . ($settings->variance_shipping_type ?? 'NULL') . "\\n";
echo "variance_shipping_limit: " . ($settings->variance_shipping_limit ?? 'NULL') . "\\n";
echo "variance_auto_approve: " . ($settings->variance_auto_approve ? 'YES' : 'NO') . "\\n";
echo "variance_profit_guard_enabled: " . ($settings->variance_profit_guard_enabled ? 'YES' : 'NO') . "\\n";
echo "variance_min_profit_margin: " . ($settings->variance_min_profit_margin ?? 'NULL') . "\\n";

echo "\\n=========================================================\\n";
echo "2. TESTING CONTROLLER STORE FOR COST_VARIANCE SECTION\\n";
echo "=========================================================\\n";
$controller = app(AliExpressKeysController::class);
$req = Request::create('/admin/dropshipping/keys', 'POST', [
    'section' => 'cost_variance',
    'variance_product_type' => 'percentage',
    'variance_product_limit' => '10.00',
    'variance_shipping_type' => 'fixed',
    'variance_shipping_limit' => '5.00',
    'variance_auto_approve' => '1',
    'variance_profit_guard_enabled' => '1',
    'variance_min_profit_margin' => '7.5',
]);

$resp = $controller->store($req);
echo "Store Response Status: " . $resp->getStatusCode() . "\\n";

$settingsFresh = AliExpressSetting::current()->fresh();
echo "Updated variance_shipping_type: " . $settingsFresh->variance_shipping_type . "\\n";
echo "Updated variance_shipping_limit: " . $settingsFresh->variance_shipping_limit . "\\n";
echo "Updated variance_min_profit_margin: " . $settingsFresh->variance_min_profit_margin . "\\n";

echo "\\n=========================================================\\n";
echo "3. TESTING BLADE VIEW RENDER WITH NEW TAB\\n";
echo "=========================================================\\n";
$view = $controller->index();
$html = $view->render();
echo "Render Success! HTML Length: " . strlen($html) . " bytes\\n";
echo "Contains 'tab-btn-cost-variance': " . (str_contains($html, 'tab-btn-cost-variance') ? "YES ✅" : "NO ❌") . "\\n";
echo "Contains 'tab-panel-cost-variance': " . (str_contains($html, 'tab-panel-cost-variance') ? "YES ✅" : "NO ❌") . "\\n";
echo "Contains 'درع حماية هامش الربح الأدنى': " . (str_contains($html, 'درع حماية هامش الربح الأدنى') ? "YES ✅" : "NO ❌") . "\\n";
"""

sftp2 = client.open_sftp()
with sftp2.file(f"{remote_base}/test_cost_variance_deployment.php", "w") as f:
    f.write(test_php)
sftp2.close()

code, out, err = r.run_remote_cmd(client, f"cd {remote_base} && php8.4 test_cost_variance_deployment.php && rm test_cost_variance_deployment.php")
print(f"\nTest Execution Output:\n{out}")

client.close()
