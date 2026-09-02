import remote_ssh_helper as r

client = r.get_ssh_client()
sftp = client.open_sftp()

files_to_sync = [
    ("app/Http/Controllers/AliExpress/AliExpressKeysController.php", "app/Http/Controllers/AliExpress/AliExpressKeysController.php"),
    ("resources/views/aliexpress/keys.blade.php", "resources/views/aliexpress/keys.blade.php"),
]

local_base = r"e:\HIGESTO NEW1\higest\higest101"
remote_base = "/home/highest-ye/htdocs/highest-ye.store"

for rel_local, rel_remote in files_to_sync:
    local_path = f"{local_base}/{rel_local}"
    remote_path = f"{remote_base}/{rel_remote}"
    print(f"Uploading {rel_local} -> {remote_path}")
    sftp.put(local_path, remote_path)

sftp.close()

# Clear view cache
code, out, err = r.run_remote_cmd(client, f"cd {remote_base} && php8.4 artisan view:clear && php8.4 artisan view:cache")
print(f"View Cache: CODE {code}\n{out}")

# Test rendering keys controller directly
test_php = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use App\\Http\\Controllers\\AliExpress\\AliExpressKeysController;

$controller = app(AliExpressKeysController::class);
$view = $controller->index();
$rendered = $view->render();

echo "Keys View Rendered Successfully! Bytes: " . strlen($rendered) . "\\n";
echo "Contains tab-btn-communications: " . (str_contains($rendered, 'tab-btn-communications') ? 'YES ✅' : 'NO ❌') . "\\n";
echo "Contains tab-panel-communications: " . (str_contains($rendered, 'tab-panel-communications') ? 'YES ✅' : 'NO ❌') . "\\n";
"""

sftp2 = client.open_sftp()
with sftp2.file(f"{remote_base}/test_render_keys.php", "w") as f:
    f.write(test_php)
sftp2.close()

code, out, err = r.run_remote_cmd(client, f"cd {remote_base} && php8.4 test_render_keys.php && rm test_render_keys.php")
print(f"\nVerification Output:\n{out}")

client.close()
