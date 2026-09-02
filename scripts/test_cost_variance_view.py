import remote_ssh_helper as r

client = r.get_ssh_client()
sftp = client.open_sftp()

test_php = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use App\\Models\\AliExpressSetting;
use App\\Http\\Controllers\\AliExpress\\AliExpressKeysController;
use Illuminate\\Support\\ViewErrorBag;
use Webkul\\User\\Models\\Admin;

view()->share('errors', new ViewErrorBag());
$admin = Admin::first();
if ($admin) {
    auth()->guard('admin')->setUser($admin);
}

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
echo "2. TESTING BLADE VIEW RENDER WITH NEW TAB\\n";
echo "=========================================================\\n";
$controller = app(AliExpressKeysController::class);
$view = $controller->index();
$html = $view->render();
echo "Render Success! HTML Length: " . strlen($html) . " bytes\\n";
echo "Contains 'tab-btn-cost-variance': " . (str_contains($html, 'tab-btn-cost-variance') ? "YES ✅" : "NO ❌") . "\\n";
echo "Contains 'tab-panel-cost-variance': " . (str_contains($html, 'tab-panel-cost-variance') ? "YES ✅" : "NO ❌") . "\\n";
echo "Contains 'درع حماية هامش الربح الأدنى': " . (str_contains($html, 'درع حماية هامش الربح الأدنى') ? "YES ✅" : "NO ❌") . "\\n";
echo "Contains 'حد التسامح في رسوم الشحن': " . (str_contains($html, 'حد التسامح في رسوم الشحن') ? "YES ✅" : "NO ❌") . "\\n";
"""

with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_cost_variance_view.php", "w") as f:
    f.write(test_php)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_cost_variance_view.php && rm test_cost_variance_view.php")
print(f"CODE: {code}")
print(f"OUT:\n{out}")

client.close()
