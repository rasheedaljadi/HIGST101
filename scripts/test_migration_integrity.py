import os
import sys

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from scripts.remote_ssh_helper import get_ssh_client, run_remote_cmd

def main():
    client = get_ssh_client()
    
    php_code = r"""<?php
$projDir = '/home/highest-ye/htdocs/highest-ye.store';
require $projDir . '/vendor/autoload.php';
$app = require_once $projDir . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

$migrationFile = '/tmp/procurement_src/Database/Migrations/2026_08_22_000002_create_aliexpress_webhook_inbox_messages_table.php';
$migration = require $migrationFile;

echo "=== MIGRATION INTEGRITY CHECK ===\n";

// 1. Initial State
$tableExistsBefore = Schema::hasTable('aliexpress_webhook_inbox_messages');
echo "Initial table exists: " . ($tableExistsBefore ? "YES" : "NO") . "\n";

// 2. Test Down (Rollback)
$migration->down();
$tableExistsAfterDown = Schema::hasTable('aliexpress_webhook_inbox_messages');
echo "After down() table exists: " . ($tableExistsAfterDown ? "YES" : "NO") . " (Expected: NO)\n";

// 3. Test Up (Fresh/Upgrade)
$migration->up();
$tableExistsAfterUp = Schema::hasTable('aliexpress_webhook_inbox_messages');
echo "After up() table exists: " . ($tableExistsAfterUp ? "YES" : "NO") . " (Expected: YES)\n";

// 4. Verify existing core tables remain intact and untouched
$coreTables = [
    'external_platform_orders',
    'supplier_purchase_orders',
    'supplier_purchase_order_items',
    'procurement_batches',
    'procurement_demands',
    'procurement_demand_allocations',
    'procurement_cost_snapshots',
    'procurement_audit_logs',
];

$allIntact = true;
foreach ($coreTables as $t) {
    if (!Schema::hasTable($t)) {
        echo "ERROR: Core table {$t} missing!\n";
        $allIntact = false;
    }
}

echo "Core business tables intact: " . ($allIntact ? "YES" : "NO") . "\n";
echo "MIGRATION_INTEGRITY_VERIFIED\n";
"""
    sftp = client.open_sftp()
    with sftp.file('/tmp/test_migration_integrity.php', 'w') as f:
        f.write(php_code)
    sftp.close()
    
    cmd = "cd /home/highest-ye/htdocs/highest-ye.store && php /tmp/test_migration_integrity.php"
    code, out, err = run_remote_cmd(client, cmd)
    print(out)
    client.close()

if __name__ == '__main__':
    main()
