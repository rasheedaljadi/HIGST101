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

files_to_sync = [
    ('packages/Webkul/Inventory/src/Config/admin-menu.php', f"{APP_DIR}/packages/Webkul/Inventory/src/Config/admin-menu.php"),
    ('packages/Webkul/Inventory/src/Resources/lang/ar/app.php', f"{APP_DIR}/packages/Webkul/Inventory/src/Resources/lang/ar/app.php"),
    ('packages/Webkul/Inventory/src/Resources/lang/en/app.php', f"{APP_DIR}/packages/Webkul/Inventory/src/Resources/lang/en/app.php"),
]

for local_rel, remote_path in files_to_sync:
    local_path = os.path.join(LOCAL_ROOT, local_rel.replace('/', os.sep))
    print(f"Uploading {local_rel} -> {remote_path}")
    sftp.put(local_path, remote_path)

sftp.close()

# Rebuild cache on production
def run_cmd(cmd):
    print(f"\n>>> {cmd}")
    stdin, stdout, stderr = client.exec_command(cmd)
    out = stdout.read().decode('utf-8', errors='replace')
    print(out.strip())

run_cmd(f"cd {APP_DIR} && php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache")

client.close()
print("\n[OK] Menu and Icon updated and caches cleared on production successfully!")
