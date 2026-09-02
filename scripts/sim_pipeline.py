import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Illuminate\\Support\\Facades\\DB;
use Webkul\\Sales\\Models\\Order;
use Webkul\\Sales\\Models\\OrderItem;

echo "=========================================================\\n";
echo "SIMULATION: RESOLVING STAGES WITH ACCURATE IMPORT & DEMAND DETECTION\\n";
echo "=========================================================\\n";

$orders = Order::with('items')->get();
$stagesCount = [
    'new' => 0,
    'payment_pending' => 0,
    'confirmed' => 0,
    'sourcing_required' => 0,
    'po_created' => 0,
    'supplier_shipped' => 0,
    'sa_received' => 0,
    'ye_in_transit' => 0,
    'ye_received' => 0,
    'handed_off' => 0,
    'delivered' => 0,
];

foreach ($orders as $order) {
    if (in_array($order->status, ['canceled', 'closed'])) continue;

    $orderStage = 'new';
    if ($order->status === 'pending') {
        $orderStage = 'new';
    } elseif ($order->status === 'pending_payment') {
        $orderStage = 'payment_pending';
    } else {
        // Check items
        $hasImported = false;
        $highestItemStage = 'confirmed';

        foreach ($order->items as $item) {
            $isImport = str_starts_with((string)$item->sku, 'ae-')
                || DB::table('aliexpress_product_imports')->where('product_id', $item->product_id)->exists()
                || DB::table('procurement_demands')->where('order_item_id', $item->id)->exists();

            if ($isImport) {
                $hasImported = true;
                // Check demand state
                $demand = DB::table('procurement_demands')->where('order_item_id', $item->id)->first();
                $po = DB::table('purchase_orders')->where('order_id', $order->id)->first();

                if ($po && ($po->state === 'shipped' || !empty($po->tracking_number))) {
                    $itemStage = 'supplier_shipped';
                } elseif (($po && in_array($po->state, ['approved', 'submitting', 'needs_manual_review'])) || ($demand && in_array($demand->state, ['batched', 'ordered_external']))) {
                    $itemStage = 'po_created';
                } elseif ($demand && $demand->state === 'open_for_batching') {
                    $itemStage = 'sourcing_required';
                } else {
                    $itemStage = 'sourcing_required';
                }
            } else {
                $itemStage = 'confirmed';
            }
        }

        $orderStage = $hasImported ? $itemStage : 'confirmed';
    }

    if (isset($stagesCount[$orderStage])) {
        $stagesCount[$orderStage]++;
    }
}

echo "Accurate Stage Distribution Preview:\\n";
foreach ($stagesCount as $stg => $cnt) {
    echo "  Stage [{$stg}] => {$cnt} orders\\n";
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/sim_pipeline.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 sim_pipeline.php && rm sim_pipeline.php")
print(f"OUT:\n{out}")

client.close()
