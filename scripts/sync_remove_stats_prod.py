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
    'packages/Webkul/Admin/src/Resources/views/reporting/detailed/products.blade.php',
]

for rel in files_to_sync:
    local_path = os.path.join(LOCAL_ROOT, rel.replace('/', os.sep))
    remote_path = f"{APP_DIR}/{rel}".replace('\\', '/')
    print(f"Uploading: {rel} -> {remote_path}")
    sftp.put(local_path, remote_path)

sftp.close()

def run_cmd(cmd):
    print(f"\n>>> {cmd}")
    stdin, stdout, stderr = client.exec_command(cmd)
    out = stdout.read().decode('utf-8', errors='replace')
    err = stderr.read().decode('utf-8', errors='replace')
    if out.strip():
        print(out.strip())
    if err.strip():
        print("STDERR:\n" + err.strip())

run_cmd(f"cd {APP_DIR} && php artisan view:clear && php artisan view:cache")

client.close()
print("\n[OK] View updated and cached on production!")
