import json
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from scripts.remote_ssh_helper import get_ssh_client, run_remote_cmd

def main():
    client = get_ssh_client()
    remote_base = "/home/highest-ye/htdocs/highest-ye.store"
    
    audit_db_php = """<?php
$projDir = '/home/highest-ye/htdocs/highest-ye.store';
require $projDir . '/vendor/autoload.php';
$app = require_once $projDir . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$columns = Schema::getColumnListing('external_platform_orders');
$allRows = DB::table('external_platform_orders')->get();

$invMovements = Schema::hasTable('inventory_movements') ? DB::table('inventory_movements')->count() : 0;
$ledgerEntries = Schema::hasTable('ledger_entries') ? DB::table('ledger_entries')->count() : 0;
$defaultSource = DB::table('inventory_sources')->where('code', 'default')->first();

echo json_encode([
    'columns' => $columns,
    'total_external_orders' => count($allRows),
    'orders' => $allRows,
    'inventory_movements_count' => $invMovements,
    'ledger_entries_count' => $ledgerEntries,
    'default_inventory_source' => $defaultSource ? [
        'code' => $defaultSource->code,
        'name' => $defaultSource->name,
        'country' => $defaultSource->country,
        'state' => $defaultSource->state,
        'city' => $defaultSource->city,
        'status' => $defaultSource->status,
    ] : null,
], JSON_PRETTY_PRINT);
"""
    sftp = client.open_sftp()
    with sftp.file('/tmp/audit_cols.php', 'w') as f:
        f.write(audit_db_php)
    sftp.close()
    
    code, db_out, err = run_remote_cmd(client, "php /tmp/audit_cols.php && rm -f /tmp/audit_cols.php")
    print(db_out)
    client.close()

if __name__ == '__main__':
    main()
