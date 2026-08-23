import json
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from scripts.remote_ssh_helper import get_ssh_client, run_remote_cmd

def main():
    client = get_ssh_client()
    remote_base = "/home/highest-ye/htdocs/highest-ye.store"
    backup_dir = "/home/highest-ye/backups"
    service_path = "/home/highest-ye/.config/systemd/user/highest-queue-aliexpress-webhooks.service"
    
    # 1. Backup existing unit file
    unit_backup_path = f"{backup_dir}/service_unit_backup_pre_isolation_{os.urandom(3).hex()}.service"
    run_remote_cmd(client, f"cp {service_path} {unit_backup_path}")
    print(f"Service unit backup created at: {unit_backup_path}")
    
    # 2. Write strictly isolated unit file
    strictly_isolated_service = f"""[Unit]
Description=Highest AliExpress Webhooks Queue Worker (Strictly Isolated)
After=network.target

[Service]
Type=simple
WorkingDirectory={remote_base}
ExecStart=/usr/bin/php8.4 {remote_base}/artisan queue:work database --queue=aliexpress-webhooks --sleep=1 --tries=3 --backoff=10 --timeout=90
Restart=always
RestartSec=5
StandardOutput=append:{remote_base}/storage/logs/queue-webhooks.log
StandardError=append:{remote_base}/storage/logs/queue-webhooks.log

[Install]
WantedBy=default.target
"""
    sftp = client.open_sftp()
    with sftp.file(service_path, 'w') as f:
        f.write(strictly_isolated_service)
    sftp.close()
    
    # 3. Reload and restart systemd service
    cmds = [
        "systemctl --user daemon-reload",
        "systemctl --user enable highest-queue-aliexpress-webhooks.service",
        "systemctl --user restart highest-queue-aliexpress-webhooks.service",
        "sleep 2",
        "systemctl --user status highest-queue-aliexpress-webhooks.service | head -n 15",
        "ps aux | grep 'queue:work database' | grep -v grep",
    ]
    
    for cmd in cmds:
        code, out, err = run_remote_cmd(client, cmd)
        print(f"[{cmd}]:\n{out}\n")
        
    client.close()

if __name__ == '__main__':
    main()
