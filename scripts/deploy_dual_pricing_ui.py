import remote_ssh_helper as r

client = r.get_ssh_client()
sftp = client.open_sftp()

local_path = r"e:\HIGESTO NEW1\higest\higest101\packages\Webkul\Admin\src\Resources\views\catalog\products\edit\dropshipping-shipping.blade.php"
remote_path = "/home/highest-ye/htdocs/highest-ye.store/packages/Webkul/Admin/src/Resources/views/catalog/products/edit/dropshipping-shipping.blade.php"

print(f"Uploading {local_path} -> {remote_path}")
sftp.put(local_path, remote_path)
sftp.close()

# Clear views and app cache
code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 artisan view:clear && php8.4 artisan cache:clear")
print(f"Cache & View Clear: CODE {code} | {out}")

# Test rendering blade for products 9135 and 500
php_test = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Webkul\Product\Models\Product;
use App\Models\AliExpressProductImport;

foreach ([9135, 500] as $pid) {
    echo "=========================================================\\n";
    echo "TESTING RENDER FOR PRODUCT {$pid}...\\n";
    echo "=========================================================\\n";
    $product = Product::find($pid);
    $aeImport = AliExpressProductImport::where('product_id', $pid)->first();

    try {
        $html = view('admin::catalog.products.edit.dropshipping-shipping', [
            'import' => $aeImport,
            'product' => $product,
        ])->render();

        echo "RENDER SUCCESS! HTML Length: " . strlen($html) . " bytes\\n";
        echo "Contains 'سعر البيع شامل الربح فقط (بدون شحن)': " . (str_contains($html, 'سعر البيع شامل الربح فقط (بدون شحن)') ? "YES ✅" : "NO ❌") . "\\n";
        echo "Contains 'سعر البيع شامل الربح ورسوم الشحن': " . (str_contains($html, 'سعر البيع شامل الربح ورسوم الشحن') ? "YES ✅" : "NO ❌") . "\\n";
    } catch (Throwable $e) {
        echo "RENDER ERROR: " . $e->getMessage() . "\\n";
    }
}
"""

sftp2 = client.open_sftp()
with sftp2.file("/home/highest-ye/htdocs/highest-ye.store/test_render_dual_pricing.php", "w") as f:
    f.write(php_test)
sftp2.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_render_dual_pricing.php && rm test_render_dual_pricing.php")
print(f"\nTest Render Output:\n{out}")

client.close()
