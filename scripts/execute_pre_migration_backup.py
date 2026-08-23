import json
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from scripts.remote_ssh_helper import get_ssh_client, run_remote_cmd

def main():
    client = get_ssh_client()
    remote_base = "/home/highest-ye/htdocs/highest-ye.store"
    backup_dir = "/home/highest-ye/backups"
    
    backup_script = f"""<?php
$projDir = '{remote_base}';
require $projDir . '/vendor/autoload.php';
$app = require_once $projDir . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$host = config('database.connections.mysql.host');
$port = config('database.connections.mysql.port', '3306');
$db = config('database.connections.mysql.database');
$user = config('database.connections.mysql.username');
$pass = config('database.connections.mysql.password');

$backupFile = '{backup_dir}/staging_db_backup_pre_webhook_' . date('Ymd_His') . '.sql.gz';
$cmd = sprintf(
    'mysqldump --no-tablespaces -h %s -P %s -u %s -p%s %s | gzip > %s',
    escapeshellarg($host),
    escapeshellarg($port),
    escapeshellarg($user),
    escapeshellarg($pass),
    escapeshellarg($db),
    escapeshellarg($backupFile)
);

exec($cmd, $output, $returnCode);

if ($returnCode === 0 && file_exists($backupFile) && filesize($backupFile) > 0) {{
    $sha = hash_file('sha256', $backupFile);
    $size = filesize($backupFile);
    
    // Verify readability of gzip
    exec('gzip -t ' . escapeshellarg($backupFile), $testOut, $testCode);
    
    echo json_encode([
        'success' => true,
        'backup_path' => $backupFile,
        'sha256' => $sha,
        'size_bytes' => $size,
        'size_human' => round($size / 1024 / 1024, 2) . ' MB',
        'gzip_integrity_valid' => ($testCode === 0),
        'timestamp' => date('Y-m-d H:i:s P'),
    ], JSON_PRETTY_PRINT);
}} else {{
    echo json_encode([
        'success' => false,
        'return_code' => $returnCode,
    ]);
}}
"""
    sftp = client.open_sftp()
    with sftp.file('/tmp/take_db_backup2.php', 'w') as f:
        f.write(backup_script)
    sftp.close()
    
    code, out, err = run_remote_cmd(client, "php /tmp/take_db_backup2.php")
    print(out)
    run_remote_cmd(client, "rm -f /tmp/take_db_backup2.php")
    client.close()

if __name__ == '__main__':
    main()
