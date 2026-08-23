import json
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from scripts.remote_ssh_helper import get_ssh_client, run_remote_cmd

def main():
    client = get_ssh_client()
    remote_base = "/home/highest-ye/htdocs/highest-ye.store"
    
    update_php = """<?php
$projDir = '""" + remote_base + """';
require $projDir . '/vendor/autoload.php';
$app = require_once $projDir . '/bootstrap/app.php';
$kernel = $app->make(\\Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Illuminate\\Support\\Facades\\DB;

DB::table('inventory_sources')->where('code', 'default')->update([
    'contact_name' => 'Rasheed Aljadi',
    'contact_number' => '501234567',
    'contact_email' => 'warehouse@hayest.com',
    'street' => 'Sheikh Zayed Road, Trade Centre 1',
    'city' => 'Dubai',
    'state' => 'Dubai',
    'postcode' => '00000',
    'country' => 'AE',
]);

$src = DB::table('inventory_sources')->where('code', 'default')->first();
echo json_encode($src, JSON_PRETTY_PRINT);
"""

    sftp = client.open_sftp()
    remote_script_path = f"{remote_base}/scripts/update_warehouse_source.php"
    with sftp.open(remote_script_path, 'w') as f:
        f.write(update_php)
    sftp.close()
    
    try:
        cmd = f"cd {remote_base} && php scripts/update_warehouse_source.php"
        code, out, err = run_remote_cmd(client, cmd)
        print(f"[Warehouse Update Output]\n{out}")
    finally:
        try:
            run_remote_cmd(client, f"rm -f {remote_script_path}")
        except Exception:
            pass
        client.close()

if __name__ == '__main__':
    main()
