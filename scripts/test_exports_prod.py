import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('76.13.79.242', username='highest-ye', password='YoK2PBV1fo82yujX2tDq')

php_test = """
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

$user = Webkul\\User\\Models\\Admin::first();
auth()->guard('admin')->login($user);

$ctrl = app(Webkul\\Admin\\Http\\Controllers\\Reporting\\DetailedReportController::class);

// Test View Render with per_page=all
$reqView = Illuminate\\Http\\Request::create('/admin/detailed-reports/products?per_page=all', 'GET');
$respView = $ctrl->products($reqView);
echo 'View Render per_page=all SUCCESS! HTML Length: ' . strlen($respView->render()) . PHP_EOL;
"""

sftp = ssh.open_sftp()
with sftp.file('/home/highest-ye/htdocs/highest-ye.store/test_all_runner.php', 'w') as f:
    f.write(f"<?php\n{php_test}\n")
sftp.close()

stdin, stdout, stderr = ssh.exec_command('cd /home/highest-ye/htdocs/highest-ye.store && php test_all_runner.php && rm test_all_runner.php')
out = stdout.read().decode()
err = stderr.read().decode()

print("STDOUT:\n", out)
if err:
    print("STDERR:\n", err)

ssh.close()
