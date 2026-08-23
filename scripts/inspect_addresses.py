import json
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from scripts.remote_ssh_helper import get_ssh_client, run_remote_cmd

def main():
    client = get_ssh_client()
    remote_base = "/home/highest-ye/htdocs/highest-ye.store"
    
    script = f"""<?php
$projDir = '{remote_base}';
require $projDir . '/vendor/autoload.php';
$app = require_once $projDir . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Illuminate\\Support\\Facades\\DB;

$allTables = DB::select('SHOW TABLES');
$tableNames = array_map(function($t) {{
    return array_values((array)$t)[0];
}}, $allTables);

$addrTables = array_filter($tableNames, function($n) {{
    return str_contains($n, 'address');
}});

$cols = [];
foreach ($addrTables as $tbl) {{
    $cols[$tbl] = DB::getSchemaBuilder()->getColumnListing($tbl);
}}

echo json_encode($cols, JSON_PRETTY_PRINT);
"""
    sftp = client.open_sftp()
    with sftp.file('/tmp/inspect_addresses.php', 'w') as f:
        f.write(script)
    sftp.close()
    
    code, out, err = run_remote_cmd(client, "php /tmp/inspect_addresses.php && rm -f /tmp/inspect_addresses.php")
    print(out)
    if err:
        print("ERR:", err)
        
    client.close()

if __name__ == '__main__':
    main()
