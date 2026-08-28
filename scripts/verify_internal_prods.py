import paramiko

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect('76.13.79.242', username='highest-ye', password='YoK2PBV1fo82yujX2tDq')

cmd = "cd /home/highest-ye/htdocs/highest-ye.store && php -r \"require 'vendor/autoload.php'; \\$app = require_once 'bootstrap/app.php'; \\$kernel = \\$app->make(Illuminate\\Contracts\\Console\\Kernel::class); \\$kernel->bootstrap(); \\$prods = \\Webkul\\Product\\Models\\Product::where('sku', 'like', 'HIG-INT-%')->get(); foreach(\\$prods as \\$p) { echo 'ID: ' . \\$p->id . ' | SKU: ' . \\$p->sku . ' | Name: ' . \\$p->name . ' | Price: ' . \\$p->price . ' | Images: ' . \\$p->images->count() . ' | URL: ' . \\$p->url_key . PHP_EOL; }\""

stdin, stdout, stderr = client.exec_command(cmd)
print("STDOUT:\n", stdout.read().decode('utf-8'))
print("STDERR:\n", stderr.read().decode('utf-8'))
client.close()
