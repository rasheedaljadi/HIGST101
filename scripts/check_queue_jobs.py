import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

if (Illuminate\Support\Facades\Schema::hasTable('jobs')) {
    $jobsCount = DB::table('jobs')->count();
    echo "Jobs in queue: {$jobsCount}\n";
    $firstJobs = DB::table('jobs')->take(5)->get();
    foreach ($firstJobs as $j) {
        echo "Job ID: {$j->id}, Queue: {$j->queue}, Payload: " . substr($j->payload, 0, 150) . "\n";
    }
} else {
    echo "No jobs table.\n";
}

if (Illuminate\Support\Facades\Schema::hasTable('failed_jobs')) {
    $failedCount = DB::table('failed_jobs')->count();
    echo "Failed jobs count: {$failedCount}\n";
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/check_queue_jobs.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 check_queue_jobs.php && rm check_queue_jobs.php")
print(f"OUTPUT:\n{out}")

client.close()
