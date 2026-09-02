import remote_ssh_helper as r

client = r.get_ssh_client()
sftp = client.open_sftp()

php_script = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\AliExpressProductImport;

$demands = DB::table('procurement_demands')->orderByDesc('id')->take(5)->get();
echo "Procurement Demands Count: " . $demands->count() . "\\n";

foreach ($demands as $d) {
    echo "=========================================================\\n";
    echo "Demand ID: {$d->id} | Product ID: {$d->product_id} | Variant ID: " . ($d->variant_product_id ?? 'NULL') . "\\n";
    echo "State: {$d->state} | Provider: {$d->provider}\\n";
    echo "Source Snapshot: " . $d->source_snapshot . "\\n";

    $import = AliExpressProductImport::where('product_id', $d->product_id)->first();
    echo "Import Record for product_id {$d->product_id}:\\n";
    echo "  - base_shipping_cost: " . ($import?->base_shipping_cost ?? 'NULL') . "\\n";
    echo "  - isChoice: " . ($import?->isChoice() ? 'YES' : 'NO') . "\\n";
    echo "  - shipping_company: " . ($import?->shipping_company ?? 'NULL') . "\\n";
}
"""

with sftp.file("/home/highest-ye/htdocs/highest-ye.store/inspect_demands_data.php", "w") as f:
    f.write(php_script)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 inspect_demands_data.php && rm inspect_demands_data.php")
print(f"CODE: {code}")
print(f"OUT:\n{out}")

client.close()
