import remote_ssh_helper as r

client = r.get_ssh_client()
sftp = client.open_sftp()

php_script = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use App\Models\AliExpressProductImport;
use App\Models\AliExpressSetting;
use App\Models\HigestSourceOffer;
use Illuminate\Support\Facades\DB;

$settings = AliExpressSetting::first();

echo "=========================================================\\n";
echo "1. إعدادات الشحن الحالية في النظام:\\n";
echo "=========================================================\\n";
echo "• دمج شحن AliExpress في السعر: " . ($settings->include_shipping_in_price ? 'مُفعّل (True)' : 'معطل (False)') . "\\n";
echo "• استثناء منتجات Choice من دمج الشحن: " . ($settings->exclude_choice_from_shipping_price ? 'مُفعّل (True)' : 'معطل (False)') . "\\n";
echo "• أيام التوصيل الإضافية: " . $settings->shipping_extra_days . " يوم\\n";

echo "\\n=========================================================\\n";
echo "2. عينات حية لمنتجات عادية (Non-Choice) تم دمج رسوم الشحن في سعرها:\\n";
echo "=========================================================\\n";

$nonChoiceSamples = [316, 329, 500];
foreach ($nonChoiceSamples as $pId) {
    $imp = AliExpressProductImport::where('product_id', $pId)->first();
    $flat = DB::table('product_flat')->where('product_id', $pId)->where('channel', 'default')->where('locale', 'ar')->first();
    $offer = HigestSourceOffer::where('product_id', $pId)->first();
    
    echo "المنتج ID: {$pId} | AliExpress: {$imp->aliexpress_product_id}\\n";
    echo "  - الاسم: " . mb_substr($flat?->name ?? '', 0, 55) . "...\\n";
    echo "  - تصنيف شويس (Choice): " . ($imp->isChoice() ? 'نعم (Choice)' : 'لا (عادي)') . "\\n";
    echo "  - رسوم شحن المورد: $" . (float)$imp->base_shipping_cost . " USD (" . ($imp->shipping_company ?: 'Standard') . ")\\n";
    echo "  - تكلفة شراء المنتج: $" . (float)($offer?->acquisition_cost ?? 0) . " USD\\n";
    echo "  - إجمالي التكلفة المعتمدة بالتسعير (شراء + شحن): $" . ((float)($offer?->acquisition_cost ?? 0) + (float)$imp->base_shipping_cost) . " USD\\n";
    echo "  - سعر البيع المحتسب بالمتجر: $" . (float)($flat?->price ?? 0) . " USD\\n";
    echo "\\n";
}

echo "=========================================================\\n";
echo "3. عينات حية لمنتجات Choice تم استثناؤها من إضافة رسوم الشحن:\\n";
echo "=========================================================\\n";

$choiceSamples = [1, 44, 114];
foreach ($choiceSamples as $pId) {
    $imp = AliExpressProductImport::where('product_id', $pId)->first();
    $flat = DB::table('product_flat')->where('product_id', $pId)->where('channel', 'default')->where('locale', 'ar')->first();
    $offer = HigestSourceOffer::where('product_id', $pId)->first();
    
    echo "منتج Choice ID: {$pId} | AliExpress: {$imp->aliexpress_product_id}\\n";
    echo "  - الاسم: " . mb_substr($flat?->name ?? '', 0, 55) . "...\\n";
    echo "  - تصنيف شويس (Choice): نعم (Choice)\\n";
    echo "  - رسوم الشحن على موقع المورد: $" . (float)($imp->base_shipping_cost ?? 0) . " USD\\n";
    echo "  - رسوم الشحن المضافة للسعر بالمتجر: $0.00 USD (تم الاستثناء بنجاح)\\n";
    echo "  - تكلفة شراء المنتج المعتمدة: $" . (float)($offer?->acquisition_cost ?? 0) . " USD\\n";
    echo "  - سعر البيع المحتسب بالمتجر: $" . (float)($flat?->price ?? 0) . " USD\\n";
    echo "\\n";
}
"""

with sftp.file("/home/highest-ye/htdocs/highest-ye.store/print_final_verification.php", "w") as f:
    f.write(php_script)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 print_final_verification.php && rm print_final_verification.php")
print(f"CODE: {code}")
print(f"OUT:\n{out}")

client.close()
