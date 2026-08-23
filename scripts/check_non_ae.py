import paramiko

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect('76.13.79.242', username='highest-ye', password='YoK2PBV1fo82yujX2tDq')

php_script = """<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\\\\Contracts\\\\Console\\\\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$p = DB::table('products')->where('sku', 'not like', 'ae-%')->first();
echo json_encode($p) . PHP_EOL;
"""

sftp = client.open_sftp()
with sftp.file('/home/highest-ye/htdocs/highest-ye.store/check_non_ae.php', 'w') as f:
    f.write(php_script)
sftp.close()

stdin, stdout, stderr = client.exec_command('cd /home/highest-ye/htdocs/highest-ye.store && php check_non_ae.php && rm check_non_ae.php')
print(stdout.read().decode('utf-8'))
print(stderr.read().decode('utf-8'))
client.close()
