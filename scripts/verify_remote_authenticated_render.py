import paramiko

hostname = '76.13.79.242'
username = 'highest-ye'
password = 'YoK2PBV1fo82yujX2tDq'

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(hostname, username=username, password=password)

tinker_cmd = """cd /home/highest-ye/htdocs/highest-ye.store && php artisan tinker --execute="
    \\$admin = \\Webkul\\User\\Models\\Admin::first();
    if (\\$admin) { auth()->guard('admin')->login(\\$admin); }
    \\$service = app(\\Webkul\\Admin\\Services\\HayestDashboardAggregationService::class);
    \\$data = \\$service->getAdvancedData();
    \\$html = view('admin::dashboard.advanced.index', ['advancedData' => \\$data])->render();
    echo 'AUTH_RENDER_LEN:' . strlen(\\$html) . '\\n';
    echo 'HAS_RAIL:' . (str_contains(\\$html, 'ORDER LIFECYCLE PIPELINE') ? 'YES' : 'NO') . '\\n';
    echo 'HAS_ARABIC:' . (str_contains(\\$html, 'المسار التشغيلي الموحد لدورة حياة الطلبات') ? 'YES' : 'NO') . '\\n';
" """

stdin, stdout, stderr = ssh.exec_command(tinker_cmd)
out = stdout.read().decode('utf-8').strip()
err = stderr.read().decode('utf-8').strip()

print("=== REMOTE AUTHENTICATED VIEW RENDER TEST ===")
print("Output:", out)
if err:
    print("Stderr:", err)

ssh.close()
