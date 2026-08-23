import sys
import os
import paramiko

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')

HOST = '76.13.79.242'
USER = 'highest-ye'
PASS = 'YoK2PBV1fo82yujX2tDq'
APP_DIR = '/home/highest-ye/htdocs/highest-ye.store'
LOCAL_ROOT = r'e:\HIGESTO NEW1\higest\higest101'

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(HOST, username=USER, password=PASS, timeout=15)
sftp = client.open_sftp()

def sftp_sync_dir(sftp, local_dir, remote_dir):
    print(f"[*] Syncing directory: {local_dir} -> {remote_dir}")
    for root, dirs, files in os.walk(local_dir):
        rel_path = os.path.relpath(root, local_dir).replace('\\', '/')
        if rel_path == '.':
            target_remote_dir = remote_dir
        else:
            target_remote_dir = f"{remote_dir}/{rel_path}"

        try:
            sftp.stat(target_remote_dir)
        except FileNotFoundError:
            parts = target_remote_dir.strip('/').split('/')
            cur = ""
            for p in parts:
                cur += "/" + p
                try:
                    sftp.stat(cur)
                except FileNotFoundError:
                    sftp.mkdir(cur)

        for f in files:
            local_file = os.path.join(root, f)
            remote_file = f"{target_remote_dir}/{f}"
            sftp.put(local_file, remote_file)

# Sync entire DeliveryManagement package
sftp_sync_dir(sftp, os.path.join(LOCAL_ROOT, 'packages', 'Webkul', 'DeliveryManagement'), f"{APP_DIR}/packages/Webkul/DeliveryManagement")
sftp.close()

def run_cmd(cmd):
    print(f"\n>>> {cmd}")
    stdin, stdout, stderr = client.exec_command(cmd)
    out = stdout.read().decode('utf-8', errors='replace')
    print(out.strip())

run_cmd(f"cd {APP_DIR} && php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache")

client.close()
print("\n[OK] Unified Courier UI synchronized to production and caches rebuilt!")
