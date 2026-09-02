import remote_ssh_helper as r

client = r.get_ssh_client()
sftp = client.open_sftp()

php_script = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use App\Models\HigestCalculatedPriceHistory;
use App\Models\HigestSourceOffer;
use Webkul\Product\Models\Product;
use Illuminate\Support\Facades\DB;

foreach ([316, 317, 329, 330, 500] as $id) {
    echo "=========================================================\\n";
    echo "ID: {$id}\\n";
    echo "=========================================================\\n";
    $h = HigestCalculatedPriceHistory::where('variant_id', $id)->orderByDesc('id')->first();
    if ($h) {
        echo "History for Variant {$id}:\\n";
        echo "  - Rule: " . $h->pricing_rule_id . "\\n";
        echo "  - AcqCost: " . $h->acquisition_cost . "\\n";
        echo "  - SellingPrice: " . $h->selling_price . "\\n";
        echo "  - SpecialPrice: " . ($h->special_price ?? 'None') . "\\n";
        echo "  - Breakdown: " . json_encode($h->calculation_breakdown, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\\n";
    } else {
        echo "No history found for {$id}\\n";
    }

    $flat = DB::table('product_flat')->where('product_id', $id)->where('channel', 'default')->where('locale', 'ar')->first();
    if ($flat) {
        echo "Flat Table Price: {$flat->price} | Special: {$flat->special_price}\\n";
    }
}
"""

with sftp.file("/home/highest-ye/htdocs/highest-ye.store/inspect_breakdown_detail.php", "w") as f:
    f.write(php_script)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 inspect_breakdown_detail.php && rm inspect_breakdown_detail.php")
print(f"CODE: {code}")
print(f"OUT:\n{out}")

client.close()
