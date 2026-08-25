import sys
import os
import paramiko

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')

HOST = '76.13.79.242'
USER = 'highest-ye'
PASS = 'YoK2PBV1fo82yujX2tDq'
APP_DIR = '/home/highest-ye/htdocs/highest-ye.store'
LOCAL_ROOT = r'e:\HIGESTO NEW1\higest\higest101'

print(f"Connecting to {HOST}...")
client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(HOST, username=USER, password=PASS, timeout=20)
sftp = client.open_sftp()

files_to_sync = [
    ('app/Services/AliExpress/AliExpressAxisNormalizer.php', 'app/Services/AliExpress/AliExpressAxisNormalizer.php'),
    ('app/Services/AliExpress/AliExpressProductImporter.php', 'app/Services/AliExpress/AliExpressProductImporter.php'),
    ('app/Console/Commands/AliExpressRebuildProjections.php', 'app/Console/Commands/AliExpressRebuildProjections.php'),
]

for src, dst in files_to_sync:
    local_path = os.path.join(LOCAL_ROOT, src.replace('/', os.sep))
    remote_path = f"{APP_DIR}/{dst}"
    print(f"Uploading {src} -> {dst}")
    sftp.put(local_path, remote_path)

# Script to inspect and resync product 1005011735920938 on production
php_check = """<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use App\\Models\\AliExpressProductImport;
use App\\Models\\ExternalVariantProjection;
use App\\Services\\AliExpress\\AliExpressProductSyncer;
use Illuminate\\Support\\Facades\\DB;
use Spatie\\ResponseCache\\Facades\\ResponseCache;
use Webkul\\Product\\Models\\Product;

$aeId = '1005011735920938';
$import = AliExpressProductImport::where('aliexpress_product_id', $aeId)->first();

if (!$import) {
    echo "Import record not found for AE ID: {$aeId}\\n";
    exit(1);
}

echo "Found Import ID: {$import->id} | Local Product ID: {$import->product_id}\\n";

// Show current variant projections and prices before sync
$product = Product::with(['variants.attribute_values'])->find($import->product_id);
if ($product) {
    echo "\\n--- BEFORE SYNC ---\\n";
    foreach ($product->variants as $v) {
        $proj = ExternalVariantProjection::where('variant_product_id', $v->id)->first();
        echo "Variant ID: {$v->id} | SKU: {$v->sku} | Price: {$v->price} | Ext SKU: " . ($proj ? $proj->external_sku_id : 'NONE') . "\\n";
    }
}

// Run Rebuild Projections
echo "\\n--- RUNNING REBUILD PROJECTIONS ---\\n";
\\Illuminate\\Support\\Facades\\Artisan::call('aliexpress:rebuild-projections', ['--id' => $import->product_id]);
echo \\Illuminate\\Support\\Facades\\Artisan::output();

// Run Syncer
echo "\\n--- RUNNING SYNCER ---\\n";
$syncer = app(AliExpressProductSyncer::class);
$syncer->sync($import);

// Process Outbox
echo "\\n--- PROCESSING OUTBOX ---\\n";
$processor = app(\\Webkul\\Fulfillment\\Services\\Application\\OutboxEventProcessor::class);
$count = $processor->processPending();
echo "Processed {$count} outbox events.\\n";

// Show variant prices AFTER sync
$product = Product::with(['variants.attribute_values'])->find($import->product_id);
if ($product) {
    echo "\\n--- AFTER SYNC ---\\n";
    foreach ($product->variants as $v) {
        $proj = ExternalVariantProjection::where('variant_product_id', $v->id)->first();
        
        // get variant options
        $labels = DB::table('product_attribute_values')
            ->where('product_attribute_values.product_id', $v->id)
            ->join('attribute_options', 'attribute_options.id', '=', 'product_attribute_values.integer_value')
            ->join('attribute_option_translations', 'attribute_option_translations.attribute_option_id', '=', 'attribute_options.id')
            ->pluck('attribute_option_translations.label')
            ->implode(' / ');
            
        echo "Variant ID: {$v->id} | Options: [{$labels}] | Price: {$v->price} | Ext SKU: " . ($proj ? $proj->external_sku_id : 'NONE') . "\\n";
    }
}

// Clear response cache
if (class_exists(ResponseCache::class)) {
    ResponseCache::clear();
    echo "\\nResponseCache cleared.\\n";
}
"""

with sftp.open(f"{APP_DIR}/inspect_product_sync.php", 'w') as f:
    f.write(php_check)

sftp.close()

def run_cmd(cmd):
    print(f"\n>>> {cmd}")
    stdin, stdout, stderr = client.exec_command(cmd)
    out = stdout.read().decode('utf-8', errors='replace')
    err = stderr.read().decode('utf-8', errors='replace')
    if out:
        print(out.strip())
    if err:
        print("STDERR:", err.strip())

run_cmd(f"cd {APP_DIR} && php inspect_product_sync.php && rm inspect_product_sync.php")
run_cmd(f"cd {APP_DIR} && php artisan optimize:clear")

client.close()
