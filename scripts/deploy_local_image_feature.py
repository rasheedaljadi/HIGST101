import paramiko

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect('76.13.79.242', username='highest-ye', password='YoK2PBV1fo82yujX2tDq')

cmds = [
    "cd /home/highest-ye/htdocs/highest-ye.store && git fetch origin && git reset --hard origin/feat/delivery-admin-ui-rebuild",
    "cd /home/highest-ye/htdocs/highest-ye.store && php artisan migrate --force",
    "cd /home/highest-ye/htdocs/highest-ye.store && php -r \"require 'vendor/autoload.php'; \\$app = require_once 'bootstrap/app.php'; \\$kernel = \\$app->make(Illuminate\\Contracts\\Console\\Kernel::class); \\$kernel->bootstrap(); \\DB::table('product_images')->join('products', 'product_images.product_id', '=', 'products.id')->where('products.sku', 'like', 'HIG-INT-%')->update(['product_images.is_local' => 1]);\"",
    "cd /home/highest-ye/htdocs/highest-ye.store && php artisan view:clear && php artisan route:clear && php artisan config:clear && php artisan cache:clear",
]

for cmd in cmds:
    print("=== RUNNING:", cmd, "===")
    stdin, stdout, stderr = client.exec_command(cmd)
    out = stdout.read().decode('utf-8')
    err = stderr.read().decode('utf-8')
    if out:
        print(out)
    if err:
        print("STDERR:", err)

client.close()
