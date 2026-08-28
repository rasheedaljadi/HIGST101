import remote_ssh_helper as r

client = r.get_ssh_client()
sftp = client.open_sftp()

php_test = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use App\Models\HigestPricingRule;
use Webkul\Admin\Http\Controllers\Dropshipping\PricingController;
use Illuminate\Http\Request;

$rule = HigestPricingRule::where('scope', 'global')->first();
echo "Testing update on Rule ID: {$rule->id} (Current value: {$rule->value}%)\\n";

$request = new Request();
$request->replace([
    'name' => $rule->name,
    'scope' => 'global',
    'type' => 'percentage',
    'value' => '15.00',
    'source_discount_policy' => 'PASS_TO_CUSTOMER',
    'priority' => 0,
    'status' => 1,
]);

$controller = app(PricingController::class);
$start = microtime(true);
$response = $controller->updateRule($request, $rule->id);
$elapsed = round(microtime(true) - $start, 4);

echo "Execution time: {$elapsed}s\\n";
echo "Response status: " . $response->getStatusCode() . "\\n";
echo "Redirect target: " . $response->getTargetUrl() . "\\n";
echo "Session success: " . session('success') . "\\n";

$updatedRule = HigestPricingRule::find($rule->id);
echo "Updated Rule in DB: {$updatedRule->name} => Value: {$updatedRule->value}%, Version: v{$updatedRule->version}\\n";
"""

with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_update_rule_live.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_update_rule_live.php && rm test_update_rule_live.php")
print(f"CODE: {code}")
print(f"OUT:\n{out}")

client.close()
