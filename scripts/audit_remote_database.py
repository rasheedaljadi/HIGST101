import paramiko

HOST = '76.13.79.242'
USER = 'highest-ye'
PASS = 'YoK2PBV1fo82yujX2tDq'
APP_DIR = '/home/highest-ye/htdocs/highest-ye.store'

def connect():
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    client.connect(HOST, username=USER, password=PASS, timeout=15)
    return client

def run_cmd(client, cmd):
    print(f"\n>>> Running: {cmd}")
    stdin, stdout, stderr = client.exec_command(cmd)
    out = stdout.read().decode('utf-8', errors='replace')
    err = stderr.read().decode('utf-8', errors='replace')
    exit_code = stdout.channel.recv_exit_status()
    if out:
        print(f"[STDOUT]\n{out.strip()}")
    if err:
        print(f"[STDERR]\n{err.strip()}")
    print(f"[EXIT CODE] {exit_code}")
    return exit_code, out, err

if __name__ == '__main__':
    client = connect()
    
    php_script = """<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use Illuminate\\Support\\Facades\\DB;

echo "=== REMOTE DATABASE READ-ONLY AUDIT (highest-db) ===\\n";
echo "DB Database: " . config('database.connections.mysql.database') . "\\n";

$sources = DB::table('inventory_sources')->get();
foreach ($sources as $s) {
    $pCount = DB::table('product_inventories')->where('inventory_source_id', $s->id)->count();
    $qSum = DB::table('product_inventories')->where('inventory_source_id', $s->id)->sum('qty');
    echo "ID: {$s->id} | Code: {$s->code} | Name: {$s->name} | Prods: {$pCount} | QtySum: {$qSum}\\n";
}

$defaultSource = DB::table('inventory_sources')->where('code', 'default')->first();
if ($defaultSource) {
    $defProds = DB::table('product_inventories')->where('inventory_source_id', $defaultSource->id)->count();
    $defSum = DB::table('product_inventories')->where('inventory_source_id', $defaultSource->id)->sum('qty');
    echo "\\nREMOTE DEFAULT SOURCE (ID {$defaultSource->id}): Linked Prods={$defProds}, QtySum={$defSum}\\n";
}

echo "\\n=== SEARCHING FOR 6733983 ON REMOTE DATABASE ===\\n";
$tables = DB::select("SHOW TABLES");
foreach ($tables as $tObj) {
    $tName = array_values((array)$tObj)[0];
    $columns = DB::select("SHOW COLUMNS FROM `{$tName}`");
    foreach ($columns as $cObj) {
        $col = $cObj->Field;
        if (in_array(strtolower($col), ['qty', 'quantity', 'total_quantity', 'available_qty', 'amount'])) {
            $c = DB::table($tName)->where($col, 6733983)->count();
            if ($c > 0) {
                echo "FOUND EXACT MATCH! Table: {$tName} | Column: {$col} | Count: {$c}\\n";
            }
            $s = DB::table($tName)->sum($col);
            if ($s == 6733983) {
                echo "FOUND SUM MATCH! Table: {$tName} | Column: {$col} | SUM: {$s}\\n";
            }
        }
    }
}
"""

    sftp = client.open_sftp()
    with sftp.file(f"{APP_DIR}/remote_audit_temp.php", "w") as f:
        f.write(php_script)
    sftp.close()

    run_cmd(client, f"cd {APP_DIR} && php remote_audit_temp.php")
    run_cmd(client, f"rm -f {APP_DIR}/remote_audit_temp.php")

    client.close()
