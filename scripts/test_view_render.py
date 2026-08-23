import paramiko

hostname = '76.13.79.242'
username = 'highest-ye'
password = 'YoK2PBV1fo82yujX2tDq'

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(hostname, username=username, password=password)

tinker_cmd = """cd /home/highest-ye/htdocs/highest-ye.store && php artisan tinker --execute="
    \\$service = app(\\Webkul\\Admin\\Services\\HayestDashboardAggregationService::class);
    \\$data = \\$service->getAdvancedData();
    \\$html = view('admin::dashboard.advanced.index', ['advancedData' => \\$data])->render();
    echo 'RENDER_SUCCESS_LEN:' . strlen(\\$html);
" """

stdin, stdout, stderr = ssh.exec_command(tinker_cmd)
out = stdout.read().decode('utf-8')
err = stderr.read().decode('utf-8')

print("=== VIEW RENDER TEST RESULT ===")
print("Output:", out)
print("Stderr:", err)

ssh.close()
