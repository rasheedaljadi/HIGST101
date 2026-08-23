import json
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from scripts.remote_ssh_helper import get_ssh_client, run_remote_cmd

def main():
    client = get_ssh_client()
    remote_base = "/home/highest-ye/htdocs/highest-ye.store"
    
    migration_rel_path = "packages/Webkul/Procurement/src/Database/Migrations/2026_08_22_000002_create_aliexpress_webhook_inbox_messages_table.php"
    migrate_cmd = f"cd {remote_base} && php artisan migrate --force --path={migration_rel_path}"
    code, migrate_out, err = run_remote_cmd(client, migrate_cmd)
    print("=== MIGRATE WITH --FORCE OUTPUT ===")
    print(migrate_out)
    
    code, status_out, err = run_remote_cmd(client, f"cd {remote_base} && php artisan migrate:status")
    print("=== MIGRATE STATUS (TAIL) ===")
    lines = status_out.strip().split('\n')
    print('\n'.join(lines[-10:]))
    
    # Verify table schema in MySQL
    verify_php = """<?php
$projDir = '/home/highest-ye/htdocs/highest-ye.store';
require $projDir . '/vendor/autoload.php';
$app = require_once $projDir . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

$tableName = 'aliexpress_webhook_inbox_messages';
$hasTable = Schema::hasTable($tableName);
$columns = $hasTable ? Schema::getColumnListing($tableName) : [];
$indexes = $hasTable ? DB::select("SHOW INDEX FROM {$tableName}") : [];

echo json_encode([
    'table_created' => $hasTable,
    'columns' => $columns,
    'indexes_count' => count($indexes),
    'indexes' => array_map(function($idx) {
        return [
            'key_name' => $idx->Key_name,
            'column_name' => $idx->Column_name,
            'non_unique' => $idx->Non_unique,
        ];
    }, $indexes),
], JSON_PRETTY_PRINT);
"""
    sftp = client.open_sftp()
    with sftp.file('/tmp/verify_inbox_schema.php', 'w') as f:
        f.write(verify_php)
    sftp.close()
    
    code, verify_out, err = run_remote_cmd(client, "php /tmp/verify_inbox_schema.php")
    print("=== TABLE VERIFICATION OUTPUT ===")
    print(verify_out)
    
    client.close()

if __name__ == '__main__':
    main()
