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

print(f"Connecting to {HOST} as {USER}...")
client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(HOST, username=USER, password=PASS, timeout=20)
sftp = client.open_sftp()

files_to_sync = [
    'packages/Webkul/Admin/src/Config/menu.php',
    'packages/Webkul/Admin/src/Config/acl.php',
    'packages/Webkul/Admin/src/Routes/web.php',
    'packages/Webkul/Admin/src/Routes/detailed-reports-routes.php',
    'packages/Webkul/Admin/src/Http/Controllers/Reporting/DetailedReportController.php',
    'packages/Webkul/Admin/src/Exports/DetailedProductReportExport.php',
    'packages/Webkul/Admin/src/Exports/DetailedCustomerReportExport.php',
    'packages/Webkul/Admin/src/Resources/views/reporting/detailed/products.blade.php',
    'packages/Webkul/Admin/src/Resources/views/reporting/detailed/customers.blade.php',
    'packages/Webkul/Admin/src/Resources/views/reporting/detailed/customers-pdf.blade.php',
    'packages/Webkul/Admin/src/Resources/views/reporting/detailed/pdf.blade.php',
    'packages/Webkul/Admin/src/Resources/lang/ar/app.php',
    'packages/Webkul/Admin/src/Resources/lang/en/app.php',
]

def ensure_remote_dir(sftp, remote_dir):
    parts = remote_dir.strip('/').split('/')
    current = ''
    for part in parts:
        current += '/' + part
        try:
            sftp.stat(current)
        except IOError:
            print(f"Creating directory: {current}")
            sftp.mkdir(current)

for rel_path in files_to_sync:
    local_path = os.path.join(LOCAL_ROOT, rel_path.replace('/', os.sep))
    remote_path = f"{APP_DIR}/{rel_path}".replace('\\', '/')
    remote_dir = os.path.dirname(remote_path)

    ensure_remote_dir(sftp, remote_dir)
    print(f"Uploading: {rel_path} -> {remote_path}")
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

print("\n--- Clearing and Rebuilding Caches on Production ---")
run_cmd(f"cd {APP_DIR} && php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache")

print("\n--- Testing Route Resolution on Production ---")
run_cmd(f"cd {APP_DIR} && php artisan route:list --name=detailed_reports")

client.close()
print("\n[SUCCESS] Detailed Reports module deployed to production successfully!")
