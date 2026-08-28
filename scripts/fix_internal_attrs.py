import paramiko

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect('76.13.79.242', username='highest-ye', password='YoK2PBV1fo82yujX2tDq')

php_script = r"""<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Webkul\Product\Models\Product;

$names = [
    'HIG-INT-HONEY-01' => ['name' => 'عسل سدر دوعني ملكي فاخر - 1 كيلو', 'url_key' => 'royal-doani-sidr-honey-1kg'],
    'HIG-INT-COFFEE-02' => ['name' => 'بن يمني حرازي أصيل محمص ومطحون - 500 جرام', 'url_key' => 'authentic-harazi-yemeni-coffee-500g'],
    'HIG-INT-SAFFRON-03' => ['name' => 'زعفران يمني نقي سوبر نقيل - 10 جرام', 'url_key' => 'pure-yemeni-saffron-super-naqil-10g'],
    'HIG-INT-SESAME-04' => ['name' => 'زيت سمسم بلدي معصور على البارد - 1 لتر', 'url_key' => 'cold-pressed-baladi-sesame-oil-1l'],
    'HIG-INT-DATES-05' => ['name' => 'تمر مجدول فاخر درجة أولى - 2 كيلو', 'url_key' => 'premium-medjool-dates-grade-a-2kg'],
];

$locales = ['ar', 'en'];
$channels = ['default'];

$nameAttr = DB::table('attributes')->where('code', 'name')->first();
$urlAttr = DB::table('attributes')->where('code', 'url_key')->first();

foreach ($names as $sku => $data) {
    $product = Product::where('sku', $sku)->first();
    if (! $product) continue;

    foreach ($locales as $loc) {
        foreach ($channels as $ch) {
            // Update or insert name
            DB::table('product_attribute_values')->updateOrInsert(
                ['product_id' => $product->id, 'attribute_id' => $nameAttr->id, 'locale' => $loc, 'channel' => $ch],
                ['text_value' => $data['name']]
            );
            // Update or insert url_key
            DB::table('product_attribute_values')->updateOrInsert(
                ['product_id' => $product->id, 'attribute_id' => $urlAttr->id, 'locale' => $loc, 'channel' => $ch],
                ['text_value' => $data['url_key'] . '-' . $loc]
            );
        }
    }

    app(\Webkul\Product\Helpers\Indexers\Flat::class)->refresh($product);
}

echo "Attributes updated & Flat table refreshed successfully!" . PHP_EOL;
?>
"""

sftp = client.open_sftp()
with sftp.file('/home/highest-ye/htdocs/highest-ye.store/fix_attrs.php', 'w') as f:
    f.write(php_script)
sftp.close()

cmd = "cd /home/highest-ye/htdocs/highest-ye.store && php fix_attrs.php && rm fix_attrs.php && php artisan cache:clear"
stdin, stdout, stderr = client.exec_command(cmd)
print("STDOUT:\n", stdout.read().decode('utf-8'))
print("STDERR:\n", stderr.read().decode('utf-8'))

client.close()
