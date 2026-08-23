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
    
    # 1. Take backup of .env
    env_backup_path = f"{backup_dir}/staging_env_backup_pre_queue_{os.urandom(3).hex()}.env"
    code, out, err = run_remote_cmd(client, f"cp {remote_base}/.env {env_backup_path} && chmod 600 {env_backup_path}")
    print(f"Env backup taken: {env_backup_path}")
    
    # 2. Update .env: set QUEUE_CONNECTION=database
    update_env_php = f"""<?php
$envFile = '{remote_base}/.env';
$content = file_get_contents($envFile);
if (preg_match('/^QUEUE_CONNECTION=.*/m', $content)) {{
    $content = preg_replace('/^QUEUE_CONNECTION=.*/m', 'QUEUE_CONNECTION=database', $content);
}} else {{
    $content .= "\nQUEUE_CONNECTION=database\n";
}}
file_put_contents($envFile, $content);
echo "QUEUE_CONNECTION set to database in .env";
"""
    sftp = client.open_sftp()
    with sftp.file('/tmp/update_env_queue.php', 'w') as f:
        f.write(update_env_php)
    sftp.close()
    
    run_remote_cmd(client, "php /tmp/update_env_queue.php && rm -f /tmp/update_env_queue.php")
    
    # 3. Upload updated code files
    files = [
        ('app/Http/Controllers/AliExpress/AliExpressWebhookController.php', f'{remote_base}/app/Http/Controllers/AliExpress/AliExpressWebhookController.php'),
        ('packages/Webkul/Procurement/src/Jobs/ProcessAliExpressWebhookJob.php', f'{remote_base}/packages/Webkul/Procurement/src/Jobs/ProcessAliExpressWebhookJob.php'),
    ]
    sftp = client.open_sftp()
    for local_rel, remote_abs in files:
        local_path = os.path.join(base_dir, local_rel)
        with open(local_path, 'r', encoding='utf-8') as f:
            content = f.read()
        with sftp.file(remote_abs, 'w') as rf:
            rf.write(content)
        print(f"Uploaded: {local_rel}")
    sftp.close()
    
    # 4. Clear and rebuild config cache
    clear_cmd = f"cd {remote_base} && php artisan config:clear && php artisan route:clear && php artisan cache:clear"
    code, clear_out, err = run_remote_cmd(client, clear_cmd)
    print("Cache cleared:", clear_out.strip())
    
    # 5. Create Systemd User Service
    service_content = f"""[Unit]
Description=Highest AliExpress Webhooks Queue Worker
After=network.target

[Service]
Type=simple
WorkingDirectory={remote_base}
ExecStart=/usr/bin/php8.4 {remote_base}/artisan queue:work database --queue=aliexpress-webhooks,default --sleep=1 --tries=3 --backoff=10 --timeout=90
Restart=always
RestartSec=5
StandardOutput=append:{remote_base}/storage/logs/queue-webhooks.log
StandardError=append:{remote_base}/storage/logs/queue-webhooks.log

[Install]
WantedBy=default.target
"""
    sftp = client.open_sftp()
    with sftp.file('/home/highest-ye/.config/systemd/user/highest-queue-aliexpress-webhooks.service', 'w') as f:
        f.write(service_content)
    sftp.close()
    
    # Reload and start systemd user service
    systemd_cmds = [
        "systemctl --user daemon-reload",
        "systemctl --user enable highest-queue-aliexpress-webhooks.service",
        "systemctl --user restart highest-queue-aliexpress-webhooks.service",
        "sleep 2",
        "systemctl --user status highest-queue-aliexpress-webhooks.service 2>&1 | head -n 15",
    ]
    for scmd in systemd_cmds:
        code, out, err = run_remote_cmd(client, scmd)
        print(f"[{scmd}]:\n{out}\n")
        
    # Verify programmatically
    verify_php = f"""<?php
$projDir = '{remote_base}';
require $projDir . '/vendor/autoload.php';
$app = require_once $projDir . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo json_encode([
    'queue_default' => config('queue.default'),
    'app_debug' => config('app.debug') ? 'true' : 'false',
    'app_env' => app()->environment(),
], JSON_PRETTY_PRINT);
"""
    sftp = client.open_sftp()
    with sftp.file('/tmp/verify_queue_config.php', 'w') as f:
        f.write(verify_php)
    sftp.close()
    
    code, vout, err = run_remote_cmd(client, "php /tmp/verify_queue_config.php && rm -f /tmp/verify_queue_config.php")
    print("Verification result:\n", vout)
    
    client.close()

if __name__ == '__main__':
    main()
