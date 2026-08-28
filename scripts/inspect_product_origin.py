import paramiko

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect('76.13.79.242', username='highest-ye', password='YoK2PBV1fo82yujX2tDq')

cmd = "cd /home/highest-ye/htdocs/highest-ye.store && php -r \"require 'vendor/autoload.php'; \\$app = require_once 'bootstrap/app.php'; \\$kernel = \\$app->make(Illuminate\\Contracts\\Console\\Kernel::class); \\$kernel->bootstrap(); \\$skus = \\DB::table('products')->pluck('sku'); foreach(\\$skus as \\$s) { echo \\$s . PHP_EOL; }\""

stdin, stdout, stderr = client.exec_command(cmd)
print("STDOUT:\n", stdout.read().decode('utf-8')[:1500])
client.close()
