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
use Illuminate\Support\Facades\Storage;
use Webkul\Product\Models\Product;

// Clean up any failed attempt
DB::table('products')->where('sku', 'like', 'HIG-INT-%')->delete();

$internalProducts = [
    [
        'sku' => 'HIG-INT-HONEY-01',
        'name' => 'عسل سدر دوعني ملكي فاخر - 1 كيلو',
        'url_key' => 'royal-doani-sidr-honey-1kg',
        'price' => 35000,
        'special_price' => 29900,
        'short_description' => 'عسل سدر دوعني يمني طبيعي 100% مستخرج من مناحل وادي دوعن الشهير بحضرموت، نقي وغني بأعلى الفوائد الغذائية.',
        'description' => '<p>عسل سدر دوعني ملكي فاخر قطفة أولى من أزهار شجر السدر في وديان دوعن، يتميز بكثافته وقوامه الذهبي ومذاقه الفريد الذي لا يضاهى.</p>',
        'qty' => 50,
        'bg_r' => 218, 'bg_g' => 165, 'bg_b' => 32,
        'title' => 'عسل سدر دوعني',
        'subtitle' => 'Royal Sidr Honey',
    ],
    [
        'sku' => 'HIG-INT-COFFEE-02',
        'name' => 'بن يمني حرازي أصيل محمص ومطحون - 500 جرام',
        'url_key' => 'authentic-harazi-yemeni-coffee-500g',
        'price' => 12000,
        'special_price' => 9500,
        'short_description' => 'بن حرازي أصيل من أعالي مدرجات حراز، محمص بعناية بدرجة متوسطة ونكهة متوازنة برائحة تأسر الحواس.',
        'description' => '<p>قهوة يمنية فاخرة مقطوفة يدوياً ومجففة طبيعياً بالشمس، تعكس التراث العريق للبن اليمني الأصيل بجودة استثنائية.</p>',
        'qty' => 80,
        'bg_r' => 111, 'bg_g' => 78, 'bg_b' => 55,
        'title' => 'بن يمني حرازي',
        'subtitle' => 'Harazi Coffee',
    ],
    [
        'sku' => 'HIG-INT-SAFFRON-03',
        'name' => 'زعفران يمني نقي سوبر نقيل - 10 جرام',
        'url_key' => 'pure-yemeni-saffron-super-naqil-10g',
        'price' => 25000,
        'special_price' => 21000,
        'short_description' => 'خيوط زعفران نقي درجة أولى سوبر نقيل، لون قرمزي طبيعي ونكهة عطرية فواحة للطهي والمشروبات الملكية.',
        'description' => '<p>زعفران سوبر نقيل منتقى حبة بحبة، يتميز بنقاوته الفائقة وخلوه التام من أي شوائب، يضفي لوناً ذهبياً ورائحة ساحرة.</p>',
        'qty' => 30,
        'bg_r' => 196, 'bg_g' => 30, 'bg_b' => 58,
        'title' => 'زعفران سوبر نقيل',
        'subtitle' => 'Super Negin Saffron',
    ],
    [
        'sku' => 'HIG-INT-SESAME-04',
        'name' => 'زيت سمسم بلدي معصور على البارد - 1 لتر',
        'url_key' => 'cold-pressed-baladi-sesame-oil-1l',
        'price' => 8000,
        'special_price' => 6800,
        'short_description' => 'زيت سمسم بلدي طبيعي 100% معصور على البارد بالطريقة التقليدية بدون أي حرارة أو إضافات كيميائية.',
        'description' => '<p>زيت سمسم بلدي نقي معصور على البارد للحفاظ على كامل العناصر الغذائية والأحماض الدهنية الأساسية والنكهة البلدي الأصيلة.</p>',
        'qty' => 60,
        'bg_r' => 198, 'bg_g' => 139, 'bg_b' => 89,
        'title' => 'زيت سمسم بلدي',
        'subtitle' => 'Pure Sesame Oil',
    ],
    [
        'sku' => 'HIG-INT-DATES-05',
        'name' => 'تمر مجدول فاخر درجة أولى - 2 كيلو',
        'url_key' => 'premium-medjool-dates-grade-a-2kg',
        'price' => 15000,
        'special_price' => 12500,
        'short_description' => 'تمور مجدول ملكية حبة جامبو طرية وحلاوة طبيعية معتدلة غنية بالمعادن والفيتامينات والطاقة.',
        'description' => '<p>تمر مجدول فاخر منتقى بعناية من أفضل المزارع، يتميز بملمسه الطري ومذاقه الكراميلي اللذيذ ليكون خيار الضيافة الأول.</p>',
        'qty' => 100,
        'bg_r' => 74, 'bg_g' => 46, 'bg_b' => 24,
        'title' => 'تمر مجدول فاخر',
        'subtitle' => 'Medjool Dates',
    ],
];

function generateProductImagePng($title, $subtitle, $r, $g, $b) {
    $width = 700;
    $height = 700;
    $img = imagecreatetruecolor($width, $height);
    
    // Colors
    $white = imagecolorallocate($img, 255, 255, 255);
    $cardBg = imagecolorallocate($img, 248, 250, 252);
    $borderColor = imagecolorallocate($img, 226, 232, 240);
    $primaryNavy = imagecolorallocate($img, 6, 12, 59);
    $accentColor = imagecolorallocate($img, $r, $g, $b);
    $textMuted = imagecolorallocate($img, 100, 116, 139);

    // Background
    imagefilledrectangle($img, 0, 0, $width, $height, $white);
    
    // Rounded Card Area
    imagefilledrectangle($img, 40, 40, $width - 40, $height - 40, $cardBg);
    imagerectangle($img, 40, 40, $width - 40, $height - 40, $borderColor);
    
    // Central Badge / Circle
    imagefilledellipse($img, $width / 2, 280, 240, 240, $accentColor);
    imagefilledellipse($img, $width / 2, 280, 210, 210, $white);
    imagefilledellipse($img, $width / 2, 280, 180, 180, $accentColor);

    // Bottom Badge Container
    imagefilledrectangle($img, 100, 470, $width - 100, 540, $primaryNavy);
    
    // Draw Subtitle / SKU
    imagestring($img, 5, 240, 272, $subtitle, $white);
    imagestring($img, 5, 220, 495, "HIGESTO INTERNAL PRODUCT", $white);

    // Tag
    imagefilledrectangle($img, 220, 570, $width - 220, 610, $accentColor);
    imagestring($img, 4, 270, 582, "100% QUALITY", $white);

    ob_start();
    imagepng($img);
    $data = ob_get_clean();
    imagedestroy($img);
    return $data;
}

$channel = DB::table('channels')->first();
$channelId = $channel->id ?? 1;
$inventorySource = DB::table('inventory_sources')->first();
$inventorySourceId = $inventorySource->id ?? 1;
$rootCategory = DB::table('categories')->first();
$rootCategoryId = $rootCategory->id ?? 1;

$originAttr = DB::table('attributes')->where('code', 'origin_type')->first();
$originAttrId = $originAttr->id ?? null;
$originInternalOption = null;
if ($originAttrId) {
    $opt = DB::table('attribute_options')->where('attribute_id', $originAttrId)->where('admin_name', 'internal')->first();
    $originInternalOption = $opt->id ?? null;
}

$family = DB::table('attribute_families')->first();
$familyId = $family->id ?? 1;

foreach ($internalProducts as $item) {
    $productId = DB::table('products')->insertGetId([
        'type' => 'simple',
        'attribute_family_id' => $familyId,
        'sku' => $item['sku'],
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Product Category
    DB::table('product_categories')->insert([
        'product_id' => $productId,
        'category_id' => $rootCategoryId,
    ]);

    // Product Channel
    DB::table('product_channels')->insert([
        'product_id' => $productId,
        'channel_id' => $channelId,
    ]);

    // Product Inventory
    DB::table('product_inventories')->insert([
        'product_id' => $productId,
        'inventory_source_id' => $inventorySourceId,
        'qty' => $item['qty'],
    ]);

    // Attributes
    $textAttrs = [
        'name' => $item['name'],
        'url_key' => $item['url_key'],
        'short_description' => $item['short_description'],
        'description' => $item['description'],
        'sku' => $item['sku'],
    ];

    foreach ($textAttrs as $code => $val) {
        $attr = DB::table('attributes')->where('code', $code)->first();
        if ($attr) {
            DB::table('product_attribute_values')->insert([
                'product_id' => $productId,
                'attribute_id' => $attr->id,
                'text_value' => $val,
            ]);
        }
    }

    $numAttrs = [
        'price' => $item['price'],
        'special_price' => $item['special_price'],
        'weight' => 1.0,
    ];
    foreach ($numAttrs as $code => $val) {
        $attr = DB::table('attributes')->where('code', $code)->first();
        if ($attr) {
            DB::table('product_attribute_values')->insert([
                'product_id' => $productId,
                'attribute_id' => $attr->id,
                'float_value' => $val,
            ]);
        }
    }

    $boolAttrs = [
        'status' => 1,
        'visible_individually' => 1,
        'featured' => 1,
        'new' => 1,
    ];
    foreach ($boolAttrs as $code => $val) {
        $attr = DB::table('attributes')->where('code', $code)->first();
        if ($attr) {
            DB::table('product_attribute_values')->insert([
                'product_id' => $productId,
                'attribute_id' => $attr->id,
                'boolean_value' => $val,
            ]);
        }
    }

    if ($originAttrId) {
        DB::table('product_attribute_values')->insert([
            'product_id' => $productId,
            'attribute_id' => $originAttrId,
            'integer_value' => $originInternalOption,
            'text_value' => 'internal',
        ]);
    }

    // Generate PNG image
    $pngData = generateProductImagePng($item['title'], $item['subtitle'], $item['bg_r'], $item['bg_g'], $item['bg_b']);
    $relPath = 'product/' . $productId . '/product_' . $productId . '_main.png';
    Storage::disk('public')->put($relPath, $pngData);

    DB::table('product_images')->insert([
        'product_id' => $productId,
        'type' => 'images',
        'path' => $relPath,
        'position' => 1,
    ]);

    echo "Created internal product ID: {$productId} [{$item['sku']}] -> {$item['name']}" . PHP_EOL;
}

// Run Indexers
$productRepo = app(\Webkul\Product\Repositories\ProductRepository::class);
$allInternal = Product::where('sku', 'like', 'HIG-INT-%')->get();
$flatIndexer = app(\Webkul\Product\Helpers\Indexers\Flat::class);
$priceIndexer = app(\Webkul\Product\Helpers\Indexers\Price::class);
$inventoryIndexer = app(\Webkul\Product\Helpers\Indexers\Inventory::class);

foreach ($allInternal as $p) {
    $flatIndexer->refresh($p);
    $priceIndexer->refresh($p);
    $inventoryIndexer->refresh($p);
}

echo "All 5 internal products indexed successfully into product_flat & price tables!" . PHP_EOL;
?>
"""

sftp = client.open_sftp()
with sftp.file('/home/highest-ye/htdocs/highest-ye.store/create_internal_prods.php', 'w') as f:
    f.write(php_script)
sftp.close()

cmd = "cd /home/highest-ye/htdocs/highest-ye.store && php create_internal_prods.php && rm create_internal_prods.php"
stdin, stdout, stderr = client.exec_command(cmd)
print("STDOUT:\n", stdout.read().decode('utf-8'))
print("STDERR:\n", stderr.read().decode('utf-8'))

cmd_cache = "cd /home/highest-ye/htdocs/highest-ye.store && php artisan view:clear && php artisan route:clear && php artisan config:clear && php artisan cache:clear"
stdin, stdout, stderr = client.exec_command(cmd_cache)
print("CACHE CLEAR:\n", stdout.read().decode('utf-8'))

client.close()
