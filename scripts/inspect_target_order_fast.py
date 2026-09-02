import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Illuminate\\Support\\Facades\\DB;
use App\\Services\\AliExpress\\AliExpressOAuthService;
use App\\Services\\AliExpress\\AliExpressApiClient;

$targetOrderId = '1122571315031333';

echo "=========================================================\\n";
echo "1. CALLING LIVE ALIEXPRESS API FOR ORDER {$targetOrderId}\\n";
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
        
        echo "Live AliExpress Order Details:\\n";
        echo "  order_status: " . ($result['order_status'] ?? 'N/A') . "\\n";
        echo "  biz_type: " . ($result['biz_type'] ?? 'N/A') . "\\n";
        echo "  gmt_create: " . ($result['gmt_create'] ?? 'N/A') . "\\n";
        echo "  gmt_pay_time: " . ($result['gmt_pay_time'] ?? 'N/A') . "\\n";
        echo "  logistics_status: " . ($result['logistics_status'] ?? 'N/A') . "\\n";
        echo "  order_amount: " . json_encode($result['order_amount'] ?? 'N/A', JSON_UNESCAPED_UNICODE) . "\\n";
        echo "  logistics_info_list: " . json_encode($result['logistics_info_list'] ?? [], JSON_UNESCAPED_UNICODE) . "\\n";
        echo "  child_order_ext_info_list: " . json_encode($result['child_order_ext_info_list'] ?? [], JSON_UNESCAPED_UNICODE) . "\\n";
    } else {
        echo "Token invalid or missing!\\n";
    }
} catch (\\Throwable $e) {
    echo "API Error: " . $e->getMessage() . "\\n";
}

echo "\\n=========================================================\\n";
echo "2. SEARCHING SPECIFIC LOCAL TABLES\\n";
echo "=========================================================\\n";

$pos = DB::table('purchase_orders')
    ->where('external_order_id', $targetOrderId)
    ->orWhere('id', $targetOrderId)
    ->get();
echo "purchase_orders matches: " . $pos->count() . "\\n";
foreach ($pos as $p) {
    echo "  PO #{$p->id}: Order #{$p->order_id}, state: {$p->state}, payment_state: " . ($p->payment_state ?? 'N/A') . ", external_order_id: {$p->external_order_id}\\n";
}

$batches = DB::table('procurement_batches')
    ->where('external_batch_id', $targetOrderId)
    ->orWhere('external_order_id', $targetOrderId)
    ->orWhere('supplier_order_id', $targetOrderId)
    ->get();
echo "procurement_batches direct matches: " . $batches->count() . "\\n";
foreach ($batches as $b) {
    echo "  Batch #{$b->id}: state: {$b->state}, external_batch_id: {$b->external_batch_id}\\n";
}

// Check if it exists in purchase_order_items or procurement_batch_items
if ($pos->isEmpty() && $batches->isEmpty()) {
    $allBatches = DB::table('procurement_batches')->get();
    foreach ($allBatches as $b) {
        $raw = json_encode($b);
        if (str_contains($raw, $targetOrderId)) {
            echo "  FOUND IN procurement_batches #{$b->id} (state: {$b->state})!\\n";
        }
    }
    
    $allPos = DB::table('purchase_orders')->get();
    foreach ($allPos as $p) {
        $raw = json_encode($p);
        if (str_contains($raw, $targetOrderId)) {
            echo "  FOUND IN purchase_orders #{$p->id} (state: {$p->state})!\\n";
        }
    }
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/inspect_target_order_fast.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 inspect_target_order_fast.php && rm inspect_target_order_fast.php")
print(f"OUT:\n{out}")

client.close()
