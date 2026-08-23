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

$db = (string) config('database.connections.mysql.database');
$maskedDb = substr($db, 0, 3) . '***' . substr($db, -2);

$tables = [
    'external_platform_orders',
    'supplier_purchase_orders',
    'procurement_batches',
    'procurement_demands',
    'procurement_demand_allocations',
    'procurement_cost_snapshots',
    'procurement_audit_logs',
    'aliexpress_webhook_inbox_messages'
];
$counts = [];
foreach ($tables as $t) {
    $counts[$t] = Schema::hasTable($t) ? DB::table($t)->count() : null;
}

$info = [
    'app_env' => app()->environment(),
    'app_debug' => config('app.debug') ? 'true' : 'false',
    'queue_connection' => config('queue.default'),
    'db_name_masked' => $maskedDb,
    'total_tables' => count(DB::select('SHOW TABLES')),
    'failed_jobs_count' => Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 'no_table',
    'procurement_counts' => $counts,
];

echo json_encode($info, JSON_PRETTY_PRINT);
"""

    sftp = client.open_sftp()
    with sftp.file('/tmp/inspect_staging.php', 'w') as f:
        f.write(inspect_php)
    sftp.close()

    code, git_status, err = run_remote_cmd(client, f"cd {remote_base} && git status --short")
    code, git_head, err = run_remote_cmd(client, f"cd {remote_base} && git rev-parse HEAD")
    code, git_branch, err = run_remote_cmd(client, f"cd {remote_base} && git branch --show-current")
    code, php_version, err = run_remote_cmd(client, "php -v | head -n 1")
    code, laravel_version, err = run_remote_cmd(client, f"cd {remote_base} && php artisan --version")
    code, queue_ps, err = run_remote_cmd(client, "ps aux | grep -E 'queue:work|horizon|supervisor' | grep -v grep")
    code, migrate_status, err = run_remote_cmd(client, f"cd {remote_base} && php artisan migrate:status")
    code, inspect_out, err = run_remote_cmd(client, "php /tmp/inspect_staging.php")

    res = {
        'git_status': git_status.strip(),
        'git_head': git_head.strip(),
        'git_branch': git_branch.strip(),
        'php_version': php_version.strip(),
        'laravel_version': laravel_version.strip(),
        'queue_process': queue_ps.strip(),
        'laravel_info': json.loads(inspect_out.strip() if inspect_out.strip().startswith('{') else '{}'),
        'raw_inspect': inspect_out.strip(),
        'migrate_status': migrate_status.strip(),
    }

    print(json.dumps(res, indent=2))
    client.close()

if __name__ == '__main__':
    main()
