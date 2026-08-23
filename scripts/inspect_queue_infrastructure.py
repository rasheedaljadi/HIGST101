import json
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from scripts.remote_ssh_helper import get_ssh_client, run_remote_cmd

def main():
    client = get_ssh_client()
    remote_base = "/home/highest-ye/htdocs/highest-ye.store"
    
    inspect_php = """<?php
$projDir = '/home/highest-ye/htdocs/highest-ye.store';
require $projDir . '/vendor/autoload.php';
$app = require_once $projDir . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$hasJobsTable = Schema::hasTable('jobs');
$hasFailedJobsTable = Schema::hasTable('failed_jobs');

$jobsCount = $hasJobsTable ? DB::table('jobs')->count() : null;
$failedJobsCount = $hasFailedJobsTable ? DB::table('failed_jobs')->count() : null;

$info = [
    'queue_default_config' => config('queue.default'),
    'available_connections' => array_keys(config('queue.connections')),
    'has_jobs_table' => $hasJobsTable,
    'jobs_count' => $jobsCount,
    'has_failed_jobs_table' => $hasFailedJobsTable,
    'failed_jobs_count' => $failedJobsCount,
    'php_binary' => PHP_BINARY,
    'cwd' => getcwd(),
    'current_user' => get_current_user(),
];

echo json_encode($info, JSON_PRETTY_PRINT);
"""
    sftp = client.open_sftp()
    with sftp.file('/tmp/inspect_queue.php', 'w') as f:
        f.write(inspect_php)
    sftp.close()
    
    commands = {
        'php_info': "php /tmp/inspect_queue.php",
        'whoami': "whoami",
        'systemctl_user_check': "systemctl --user list-units --type=service 2>&1 | head -n 10",
        'systemctl_system_check': "systemctl status 2>&1 | head -n 5",
        'supervisor_check': "which supervisord supervisorctl 2>&1",
        'crontab_list': "crontab -l 2>&1",
        'running_processes': "ps aux | grep -E 'php|queue|cron' | grep -v grep | head -n 20",
    }
    
    results = {}
    for key, cmd in commands.items():
        code, out, err = run_remote_cmd(client, cmd)
        results[key] = out.strip()
        
    print(json.dumps(results, indent=2))
    run_remote_cmd(client, "rm -f /tmp/inspect_queue.php")
    client.close()

if __name__ == '__main__':
    main()
