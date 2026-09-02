import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Illuminate\\Support\\Facades\\DB;

$startTime = '2026-08-30 02:30:00';
$endTime   = '2026-08-30 02:41:00';

$syncedImports = DB::table('aliexpress_product_imports')
    ->whereBetween('updated_at', [$startTime, $endTime])
    ->get();

echo "=========================================================\\n";
echo "FULL AUDIT BREAKDOWN OF 342 SYNCED ITEMS\\n";
echo "=========================================================\\n";
echo "Total Imported Records Updated in DB: " . $syncedImports->count() . "\\n";

$configurableCount = 0;
$simpleCount = 0;
$totalVariantsCount = 0;

foreach ($syncedImports as $imp) {
    $prod = DB::table('products')->where('id', $imp->product_id)->first();
    if ($prod?->type === 'configurable') {
        $configurableCount++;
        $varCount = DB::table('products')->where('parent_id', $imp->product_id)->count();
        $totalVariantsCount += $varCount;
    } else {
        $simpleCount++;
    }
}

echo "Configurable Products: {$configurableCount} (with {$totalVariantsCount} Child Variants)\\n";
echo "Simple / Single Products: {$simpleCount}\\n";
echo "Total Catalog Items Synchronized (Parents + Variants): " . ($syncedImports->count() + $totalVariantsCount) . "\\n";
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/full_sync_audit_stats.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 full_sync_audit_stats.php && rm full_sync_audit_stats.php")
print(f"OUT:\n{out}")

client.close()
