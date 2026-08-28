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

// Test XLSX export with search filter
$reqXlsx = Illuminate\\Http\\Request::create('/admin/detailed-reports/products/export?search=ae&format=xlsx', 'GET');
$respXlsx = $ctrl->exportProducts($reqXlsx);
echo 'XLSX Export: ' . get_class($respXlsx) . PHP_EOL;

// Test PDF export with search filter
$reqPdf = Illuminate\\Http\\Request::create('/admin/detailed-reports/products/export?search=ae&format=pdf', 'GET');
$respPdf = $ctrl->exportProducts($reqPdf);
echo 'PDF Export: ' . get_class($respPdf) . PHP_EOL;
"""

sftp = ssh.open_sftp()
with sftp.file('/home/highest-ye/htdocs/highest-ye.store/test_quick_export.php', 'w') as f:
    f.write(f"<?php\n{php_test}\n")
sftp.close()

stdin, stdout, stderr = ssh.exec_command('cd /home/highest-ye/htdocs/highest-ye.store && php test_quick_export.php && rm test_quick_export.php')
print("STDOUT:\n", stdout.read().decode())
print("STDERR:\n", stderr.read().decode())

ssh.close()
