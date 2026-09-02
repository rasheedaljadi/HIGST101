import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Schema;
use App\\Services\\AliExpress\\AliExpressOAuthService;
use App\\Services\\AliExpress\\AliExpressApiClient;

$targetOrderId = '1122571315031333';

echo "=========================================================\\n";
echo "1. SEARCHING FOR ORDER {$targetOrderId} ACROSS DATABASE\\n";
echo "=========================================================\\n";

// Search in purchase_orders
if (Schema::hasTable('purchase_orders')) {
    $pos = DB::table('purchase_orders')
        ->where('id', $targetOrderId)
        ->orWhere('external_order_id', $targetOrderId)
        ->orWhere('supplier_order_id', $targetOrderId)
        ->orWhere('order_number', $targetOrderId)
        ->get();
    echo "Found in purchase_orders: " . $pos->count() . "\\n";
    foreach ($pos as $po) {
        echo "  PO Record: ID={$po->id}, OrderID={$po->order_id}, State={$po->state}, ExternalID=" . ($po->external_order_id ?? 'null') . ", Tracking=" . ($po->tracking_number ?? 'null') . "\\n";
        echo "  Full Row: " . json_encode($po, JSON_UNESCAPED_UNICODE) . "\\n";
    }
}

// Search in procurement_batches
if (Schema::hasTable('procurement_batches')) {
    $batches = DB::table('procurement_batches')
        ->where('external_batch_id', $targetOrderId)
        ->orWhere('id', $targetOrderId)
        ->orWhere('external_order_id', $targetOrderId)
        ->orWhere('supplier_order_id', $targetOrderId)
        ->get();
    echo "Found in procurement_batches by ID: " . $batches->count() . "\\n";
    foreach ($batches as $b) {
        echo "  Batch Record: ID={$b->id}, State={$b->state}, ExternalBatchID={$b->external_batch_id}\\n";
        echo "  Full Row: " . json_encode($b, JSON_UNESCAPED_UNICODE) . "\\n";
    }
    if ($batches->isEmpty()) {
        // Search json metadata
        $batchesWithOrder = DB::table('procurement_batches')->where('metadata', 'like', "%{$targetOrderId}%")->orWhere('payload', 'like', "%{$targetOrderId}%")->get();
        echo "Found in procurement_batches by JSON payload: " . $batchesWithOrder->count() . "\\n";
        foreach ($batchesWithOrder as $b) {
            echo "  Batch #{$b->id} (State: {$b->state}):\\n";
            echo "    Metadata: " . json_encode($b->metadata, JSON_UNESCAPED_UNICODE) . "\\n";
        }
    }
}

// Search in procurement_demands
if (Schema::hasTable('procurement_demands')) {
    $demands = DB::table('procurement_demands')
        ->where('supplier_product_id', $targetOrderId)
        ->orWhere('active_fingerprint', 'like', "%{$targetOrderId}%")
        ->get();
    echo "Found in procurement_demands: " . $demands->count() . "\\n";
}

// Search in all tables if needed
$tables = DB::select('SHOW TABLES');
$dbName = DB::getDatabaseName();
$col = "Tables_in_" . $dbName;
foreach ($tables as $t) {
    $tName = $t->$col;
    if (str_contains($tName, 'procurement') || str_contains($tName, 'order') || str_contains($tName, 'payment') || str_contains($tName, 'batch')) {
        $cols = Schema::getColumnListing($tName);
        foreach ($cols as $c) {
            try {
                $match = DB::table($tName)->where($c, $targetOrderId)->count();
                if ($match > 0) {
                    echo "MATCH IN TABLE [{$tName}] COLUMN [{$c}] => {$match} rows!\\n";
                }
            } catch (\\Throwable $e) {}
        }
    }
}

echo "\\n=========================================================\\n";
echo "2. CALLING LIVE ALIEXPRESS API FOR ORDER {$targetOrderId}\\n";
echo "=========================================================\\n";

try {
    $oauth = app(AliExpressOAuthService::class);
    $apiClient = app(AliExpressApiClient::class);
    $token = $oauth->latestToken();

    if ($token && $token->isAccessTokenValid()) {
        $res = $apiClient->call('aliexpress.trade.ds.order.get', $token->access_token, [
            'order_id' => $targetOrderId,
        ]);

        echo "AliExpress API Response ok: " . ($res['ok'] ? 'YES' : 'NO') . "\\n";
        $body = $res['body']['aliexpress_trade_ds_order_get_response'] ?? $res['body'];
        $result = $body['result'] ?? $body;
        
        echo "Live AliExpress Order Status:\\n";
        echo "  order_status: " . ($result['order_status'] ?? 'N/A') . "\\n";
        echo "  biz_type: " . ($result['biz_type'] ?? 'N/A') . "\\n";
        echo "  gmt_create: " . ($result['gmt_create'] ?? 'N/A') . "\\n";
        echo "  gmt_pay_time: " . ($result['gmt_pay_time'] ?? 'N/A') . "\\n";
        echo "  logistics_status: " . ($result['logistics_status'] ?? 'N/A') . "\\n";
        echo "  order_amount: " . json_encode($result['order_amount'] ?? 'N/A', JSON_UNESCAPED_UNICODE) . "\\n";
        echo "  child_order_ext_info_list: " . json_encode($result['child_order_ext_info_list'] ?? [], JSON_UNESCAPED_UNICODE) . "\\n";
        echo "  Full Result JSON: " . json_encode($result, JSON_UNESCAPED_UNICODE) . "\\n";
    } else {
        echo "Token invalid or missing!\\n";
    }
} catch (\\Throwable $e) {
    echo "API Error: " . $e->getMessage() . "\\n";
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/inspect_target_order.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 inspect_target_order.php && rm inspect_target_order.php")
print(f"OUT:\n{out}")

client.close()
