import sys
import paramiko

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')

HOST = '76.13.79.242'
USER = 'highest-ye'
PASS = 'YoK2PBV1fo82yujX2tDq'
APP_DIR = '/home/highest-ye/htdocs/highest-ye.store'

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(HOST, username=USER, password=PASS, timeout=15)

cmd = f"cd {APP_DIR} && php -r \"" \
      f"require 'vendor/autoload.php'; " \
      f"\\$app = require 'bootstrap/app.php'; " \
      f"\\$app->make('Illuminate\\\\Contracts\\\\Console\\\\Kernel')->bootstrap(); " \
      f"foreach(\\Webkul\\Inventory\\Models\\InventorySource::all() as \\$s) {{ " \
      f"  echo 'ID: ' . \\$s->id . ' | Code: ' . \\$s->code . ' | Name: ' . \\$s->name . ' | Type: ' . (is_object(\\$s->source_type) ? \\$s->source_type->value : \\$s->source_type) . ' | Salable: ' . \\$s->is_salable . ' | Delivery: ' . \\$s->is_delivery_source . PHP_EOL; " \
      f"}}\""

stdin, stdout, stderr = client.exec_command(cmd)
print(stdout.read().decode('utf-8', errors='replace'))
client.close()
