import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$jobTypes = DB::table('jobs')
    ->select('queue', DB::raw('count(*) as total'))
    ->groupBy('queue')
    ->get();

print_r($jobTypes);

$samples = DB::table('jobs')
    ->where('queue', '!=', 'broadcastable')
    ->take(5)
    ->get();

print_r($samples);
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/inspect_all_queues.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 inspect_all_queues.php && rm inspect_all_queues.php")
print(f"OUTPUT:\n{out}")

client.close()
