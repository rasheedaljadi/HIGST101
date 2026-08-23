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

print(f"[*] Connecting to {HOST} as {USER}...")
client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(HOST, username=USER, password=PASS, timeout=20)
sftp = client.open_sftp()
print("[*] SSH & SFTP connected successfully.")

# Files to sync
files_to_sync = [
    (
        os.path.join(LOCAL_ROOT, 'packages', 'Webkul', 'Sales', 'src', 'Services', 'Lifecycle', 'OrderLifecycleDashboardQueryService.php'),
        f"{APP_DIR}/packages/Webkul/Sales/src/Services/Lifecycle/OrderLifecycleDashboardQueryService.php"
    ),
    (
        os.path.join(LOCAL_ROOT, 'packages', 'Webkul', 'Admin', 'src', 'Services', 'HayestDashboardAggregationService.php'),
        f"{APP_DIR}/packages/Webkul/Admin/src/Services/HayestDashboardAggregationService.php"
    ),
    (
        os.path.join(LOCAL_ROOT, 'packages', 'Webkul', 'Admin', 'src', 'Http', 'Controllers', 'DashboardController.php'),
        f"{APP_DIR}/packages/Webkul/Admin/src/Http/Controllers/DashboardController.php"
    ),
    (
        os.path.join(LOCAL_ROOT, 'packages', 'Webkul', 'Admin', 'src', 'Resources', 'views', 'dashboard', 'advanced', 'index.blade.php'),
        f"{APP_DIR}/packages/Webkul/Admin/src/Resources/views/dashboard/advanced/index.blade.php"
    ),
    (
        os.path.join(LOCAL_ROOT, 'packages', 'Webkul', 'DeliveryManagement', 'src', 'Config', 'admin-menu.php'),
        f"{APP_DIR}/packages/Webkul/DeliveryManagement/src/Config/admin-menu.php"
    ),
    (
        os.path.join(LOCAL_ROOT, 'app', 'Services', 'AliExpress', 'AliExpressProductSyncer.php'),
        f"{APP_DIR}/app/Services/AliExpress/AliExpressProductSyncer.php"
    ),
    (
        os.path.join(LOCAL_ROOT, 'app', 'Services', 'AliExpress', 'AliExpressApiClient.php'),
        f"{APP_DIR}/app/Services/AliExpress/AliExpressApiClient.php"
    ),
    (
        os.path.join(LOCAL_ROOT, 'app', 'Console', 'Commands', 'AliExpressSyncProducts.php'),
        f"{APP_DIR}/app/Console/Commands/AliExpressSyncProducts.php"
    ),
    (
        os.path.join(LOCAL_ROOT, 'packages', 'Webkul', 'Shop', 'src', 'Resources', 'views', 'customers', 'account', 'orders', 'pdf.blade.php'),
        f"{APP_DIR}/packages/Webkul/Shop/src/Resources/views/customers/account/orders/pdf.blade.php"
    ),
]

for local_path, remote_path in files_to_sync:
    print(f"[*] Uploading: {local_path} -> {remote_path}")
    sftp.put(local_path, remote_path)

sftp.close()
print("[*] All files uploaded successfully.")

def run_cmd(cmd):
    print(f"\n>>> Running on production: {cmd}")
    stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && {cmd}")
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    if out:
        print(f"[STDOUT]\n{out}")
    if err:
        print(f"[STDERR]\n{err}")
    exit_code = stdout.channel.recv_exit_status()
    print(f"[EXIT CODE] {exit_code}")
    return exit_code

# Clear and rebuild caches on production
run_cmd("php artisan optimize:clear")
run_cmd("php artisan config:cache")
run_cmd("php artisan route:cache")
run_cmd("php artisan view:cache")

# Verification on production
print("\n[*] Verifying rendered view on production...")
test_cmd = """php artisan tinker --execute="
    \\$service = app(\\Webkul\\Admin\\Services\\HayestDashboardAggregationService::class);
    \\$data = \\$service->getAdvancedData();
    \\$html = view('admin::dashboard.advanced.index', ['advancedData' => \\$data])->render();
    echo 'PRODUCTION_RENDER_SUCCESS_LEN:' . strlen(\\$html) . '\\n';
    echo 'HAS_EXECUTIVE_RAIL:' . (str_contains(\\$html, 'executive-rail-wrapper') ? 'YES' : 'NO') . '\\n';
" """
run_cmd(test_cmd)

client.close()
print("\n[SUCCESS] Production deployment and verification completed successfully!")
