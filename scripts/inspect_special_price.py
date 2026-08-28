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
client.connect(HOST, username=USER, password=PASS, timeout=20)

php_code = """<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use Webkul\\Product\\Models\\Product;
use Webkul\\Product\\Models\\ProductFlat;

$product = Product::find(3639);
$flat = ProductFlat::where('product_id', 3639)->first();

echo "Product ID: 3639\n";
echo "Cost: " . $product->cost . "\n";
echo "Price (Regular): " . $product->price . "\n";
echo "Special Price: " . $product->special_price . "\n";
echo "Special Price From: " . $product->special_price_from . "\n";
echo "Special Price To: " . $product->special_price_to . "\n";

if ($flat) {
    echo "\nIn ProductFlat:\n";
    echo "Flat Price: " . $flat->price . "\n";
    echo "Flat Special Price: " . $flat->special_price . "\n";
    echo "Flat Min Price: " . $flat->min_price . "\n";
    echo "Flat Max Price: " . $flat->max_price . "\n";
}
"""

sftp = client.open_sftp()
with sftp.file(f'{APP_DIR}/inspect_special_price.php', 'w') as f:
    f.write(php_code)
sftp.close()

stdin, stdout, stderr = client.exec_command(f'cd {APP_DIR} && php inspect_special_price.php && rm -f inspect_special_price.php')
print(stdout.read().decode('utf-8'))
print(stderr.read().decode('utf-8'))
client.close()
