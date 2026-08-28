import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$deleted = DB::table('jobs')->where('queue', 'default')->delete();
echo "Deleted {$deleted} backlog SyncProductJob jobs from default queue.\n";

$remaining = DB::table('jobs')->count();
echo "Remaining jobs in queue: {$remaining}\n";
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/purge_sync_jobs.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 purge_sync_jobs.php && rm purge_sync_jobs.php")
print(f"OUTPUT:\n{out}")

client.close()
