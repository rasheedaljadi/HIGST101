import sys
import paramiko
import json

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')

HOST = '76.13.79.242'
USER = 'highest-ye'
PASS = 'YoK2PBV1fo82yujX2tDq'
APP_DIR = '/home/highest-ye/htdocs/highest-ye.store'

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(HOST, username=USER, password=PASS, timeout=20)
sftp = client.open_sftp()

php_script = """<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use App\\Models\\HigestPricingRule;
use App\\Services\\Pricing\\PricingEngine;
use App\\Services\\Pricing\\DTO\\PricingContext;

$rule = HigestPricingRule::find(3);
echo "=== PRICING RULE DETAILS ===\\n";
echo json_encode($rule ? $rule->toArray() : [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\\n";

$pricingEngine = app(PricingEngine::class);
$context = new PricingContext(
    sourceProvider: 'aliexpress',
    currency: 'USD',
    acquisitionOriginalCost: 1088.21,
    shippingCost: 0
);
$res = $pricingEngine->calculate(97.94, $rule, $context);

echo "\\n=== CALCULATION BREAKDOWN FOR 97.94 ===\\n";
echo json_encode([
    'sellingPrice' => $res->sellingPrice,
    'specialPrice' => $res->specialPrice,
    'acquisitionCost' => $res->acquisitionCost,
    'breakdown' => $res->breakdown,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\\n";
"""

with sftp.open(f"{APP_DIR}/inspect_breakdown.php", 'w') as f:
    f.write(php_script)
sftp.close()

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php inspect_breakdown.php && rm inspect_breakdown.php")
print(stdout.read().decode('utf-8', errors='replace'))
client.close()
