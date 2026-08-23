import json
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from scripts.remote_ssh_helper import get_ssh_client, run_remote_cmd

def main():
    client = get_ssh_client()
    base_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    remote_base = "/home/highest-ye/htdocs/highest-ye.store"
    backup_dir = "/home/highest-ye/backups"
    
    # 1. Take Pre-Deploy Database Backup
    backup_php = f"""<?php
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

$backupFile = '{backup_dir}/staging_db_backup_pre_strict_gw_' . date('Ymd_His') . '.sql.gz';
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
    exec('gzip -t ' . escapeshellarg($backupFile), $testOut, $testCode);
    echo json_encode([
        'success' => true,
        'backup_path' => $backupFile,
        'sha256' => $sha,
        'size_bytes' => $size,
        'size_human' => round($size / 1024 / 1024, 2) . ' MB',
        'gzip_valid' => ($testCode === 0),
        'timestamp' => date('Y-m-d H:i:s P'),
    ], JSON_PRETTY_PRINT);
}} else {{
    echo json_encode(['success' => false, 'code' => $returnCode]);
}}
"""
    sftp = client.open_sftp()
    with sftp.file('/tmp/take_db_backup_strict.php', 'w') as f:
        f.write(backup_php)
    sftp.close()
    
    code, db_backup_out, err = run_remote_cmd(client, "php /tmp/take_db_backup_strict.php && rm -f /tmp/take_db_backup_strict.php")
    print("=== DB BACKUP MANIFEST ===")
    print(db_backup_out)
    
    # 2. Backup existing gateway files
    gw_backup_dir = f"{backup_dir}/gw_backup_{os.urandom(3).hex()}"
    run_remote_cmd(client, f"mkdir -p {gw_backup_dir}")
    run_remote_cmd(client, f"cp {remote_base}/packages/Webkul/Procurement/src/Gateways/AliExpressOrderSubmissionGateway.php {gw_backup_dir}/ 2>/dev/null || true")
    run_remote_cmd(client, f"cp {remote_base}/packages/Webkul/Procurement/src/DTO/AliExpressOrderPreflight.php {gw_backup_dir}/ 2>/dev/null || true")
    run_remote_cmd(client, f"cp {remote_base}/packages/Webkul/Procurement/src/Support/AliExpressMoneyNormalizer.php {gw_backup_dir}/ 2>/dev/null || true")
    print(f"Gateway files backup created in: {gw_backup_dir}")
    
    # 3. Synchronize verified strict gateway files
    files_to_sync = [
        ('packages/Webkul/Procurement/src/Support/AliExpressMoneyNormalizer.php', f'{remote_base}/packages/Webkul/Procurement/src/Support/AliExpressMoneyNormalizer.php'),
        ('packages/Webkul/Procurement/src/DTO/AliExpressOrderPreflight.php', f'{remote_base}/packages/Webkul/Procurement/src/DTO/AliExpressOrderPreflight.php'),
        ('packages/Webkul/Procurement/src/Gateways/AliExpressOrderSubmissionGateway.php', f'{remote_base}/packages/Webkul/Procurement/src/Gateways/AliExpressOrderSubmissionGateway.php'),
    ]
    
    run_remote_cmd(client, f"mkdir -p {remote_base}/packages/Webkul/Procurement/src/Support {remote_base}/packages/Webkul/Procurement/src/DTO {remote_base}/packages/Webkul/Procurement/src/Gateways")
    
    sftp = client.open_sftp()
    for local_rel, remote_abs in files_to_sync:
        local_path = os.path.join(base_dir, local_rel)
        with open(local_path, 'r', encoding='utf-8') as f:
            content = f.read()
        with sftp.file(remote_abs, 'w') as rf:
            rf.write(content)
        print(f"Uploaded: {local_rel} -> {remote_abs}")
    sftp.close()
    
    # 4. Clear caches
    clear_cmd = f"cd {remote_base} && php artisan config:clear && php artisan route:clear && php artisan cache:clear"
    code, clear_out, err = run_remote_cmd(client, clear_cmd)
    print("Cache cleared:\n", clear_out.strip())
    
    # 5. Restart worker gracefully to pick up new class files
    run_remote_cmd(client, f"cd {remote_base} && php artisan queue:restart")
    run_remote_cmd(client, "systemctl --user restart highest-queue-aliexpress-webhooks.service")
    
    client.close()

if __name__ == '__main__':
    main()
