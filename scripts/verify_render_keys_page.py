import remote_ssh_helper as r

client = r.get_ssh_client()
sftp = client.open_sftp()

php_test = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

view()->share('errors', new \\Illuminate\\Support\\ViewErrorBag());
$adminUser = \\Webkul\\User\\Models\\Admin::first();
auth()->guard('admin')->setUser($adminUser);

$controller = app(\\App\\Http\\Controllers\\AliExpress\\AliExpressKeysController::class);
$view = $controller->index();
$html = $view->render();

echo "Rendered HTML length: " . strlen($html) . "\\n";
echo "Contains include_shipping_in_price: " . (str_contains($html, 'include_shipping_in_price') ? 'YES' : 'NO') . "\\n";
echo "Contains exclude_choice_from_shipping_price: " . (str_contains($html, 'exclude_choice_from_shipping_price') ? 'YES' : 'NO') . "\\n";
echo "Contains tab-panel-shipping: " . (str_contains($html, 'tab-panel-shipping') ? 'YES' : 'NO') . "\\n";
"""

with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_render_keys_page.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_render_keys_page.php && rm test_render_keys_page.php")
print(f"CODE: {code}")
print(f"OUT:\n{out}")

client.close()
