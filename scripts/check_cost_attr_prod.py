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

script = """<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Illuminate\\Support\\Facades\\DB;

$costAttr = DB::table('attributes')->where('code', 'cost')->first();
echo "Cost Attribute: " . json_encode($costAttr) . "\\n";

if ($costAttr) {
    $costValues = DB::table('product_attribute_values')
        ->where('attribute_id', $costAttr->id)
        ->take(10)
        ->get();
    echo "Sample Cost Values (" . count($costValues) . "):\\n";
    foreach ($costValues as $cv) {
        echo "  Product #" . $cv->product_id . " => float: " . $cv->float_value . ", text: " . $cv->text_value . "\\n";
    }
}
"""

sftp = client.open_sftp()
with sftp.file(f"{APP_DIR}/check_cost_attr.php", 'w') as f:
    f.write(script)
sftp.close()

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php check_cost_attr.php && rm check_cost_attr.php")
print(stdout.read().decode('utf-8', errors='replace'))

client.close()
