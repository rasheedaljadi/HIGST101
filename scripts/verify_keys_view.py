import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use App\\Http\\Controllers\\AliExpress\\AliExpressKeysController;
use Illuminate\\Support\\ViewErrorBag;
use Illuminate\\Support\\Facades\\View;

View::share('errors', new ViewErrorBag);

$controller = app(AliExpressKeysController::class);
$view = $controller->index();
$rendered = $view->render();

echo "Keys View Rendered Successfully! Bytes: " . strlen($rendered) . "\\n";
echo "Contains tab-btn-communications: " . (str_contains($rendered, 'tab-btn-communications') ? 'YES ✅' : 'NO ❌') . "\\n";
echo "Contains tab-panel-communications: " . (str_contains($rendered, 'tab-panel-communications') ? 'YES ✅' : 'NO ❌') . "\\n";
echo "Contains الاتصالات اليومية: " . (str_contains($rendered, 'الاتصالات اليومية') ? 'YES ✅' : 'NO ❌') . "\\n";
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_render_keys2.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_render_keys2.php && rm test_render_keys2.php")
print(f"OUT:\n{out}")

client.close()
