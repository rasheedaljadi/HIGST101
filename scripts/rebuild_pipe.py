import remote_ssh_helper as r

client = r.get_ssh_client()

remote_base = "/home/highest-ye/htdocs/highest-ye.store"

php_test = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Webkul\\Sales\\Services\\Lifecycle\\OrderLifecycleRebuildService;

$rebuilder = app(OrderLifecycleRebuildService::class);
$rebuilder->rebuild();
echo "Lifecycle pipeline views re-projected successfully!\\n";
"""

sftp = client.open_sftp()
with sftp.file(f"{remote_base}/rebuild_pipe.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, f"cd {remote_base} && php8.4 rebuild_pipe.php && rm rebuild_pipe.php && php8.4 artisan cache:clear")
print(f"OUT:\n{out}")

client.close()
