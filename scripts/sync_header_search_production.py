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

# Sync Header index.blade.php
sftp.put(
    os.path.join(LOCAL_ROOT, 'packages', 'Webkul', 'Admin', 'src', 'Resources', 'views', 'components', 'layouts', 'header', 'index.blade.php'),
    f"{APP_DIR}/packages/Webkul/Admin/src/Resources/views/components/layouts/header/index.blade.php"
)

sftp.close()

def run_cmd(cmd):
    print(f"\n>>> {cmd}")
    stdin, stdout, stderr = client.exec_command(cmd)
    out = stdout.read().decode('utf-8', errors='replace')
    print(out.strip())

run_cmd(f"cd {APP_DIR} && php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache")

client.close()
print("\n[OK] Header updated on production and view cache rebuilt!")
