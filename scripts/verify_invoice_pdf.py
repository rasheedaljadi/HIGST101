import paramiko

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect('76.13.79.242', username='highest-ye', password='YoK2PBV1fo82yujX2tDq')

php_cmd = '''php -r "
require 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$app->make('Illuminate\\\\Contracts\\\\Console\\\\Kernel')->bootstrap();

\$invoice = \\\\Webkul\\\\Sales\\\\Models\\\\Invoice::with(['order', 'items'])->latest()->first();
if (\$invoice) {
    \$orderCurrencyCode = \$invoice->order->order_currency_code;
    \$html = view('shop::customers.account.orders.pdf', compact('invoice', 'orderCurrencyCode'))->render();
    echo 'INVOICE_ID:' . \$invoice->id . PHP_EOL;
    echo 'RENDER_LEN:' . strlen(\$html) . PHP_EOL;
    echo 'HAS_BASE64_LOGO:' . (str_contains(\$html, 'data:image/png;base64') ? 'YES' : 'NO') . PHP_EOL;
    echo 'HAS_BRAND_COLOR_NAVY:' . (str_contains(\$html, '#1E3A8A') ? 'YES' : 'NO') . PHP_EOL;
    echo 'HAS_BRAND_COLOR_TINT:' . (str_contains(\$html, '#EEF2FF') ? 'YES' : 'NO') . PHP_EOL;
} else {
    echo 'NO_INVOICE_FOUND' . PHP_EOL;
}
"'''

stdin, stdout, stderr = client.exec_command(f'cd /home/highest-ye/htdocs/highest-ye.store && {php_cmd}')
print("STDOUT:", stdout.read().decode('utf-8'))
print("STDERR:", stderr.read().decode('utf-8'))
client.close()
