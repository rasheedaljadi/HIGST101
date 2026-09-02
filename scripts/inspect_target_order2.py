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

$tables = DB::select('SHOW TABLES');
$dbName = DB::getDatabaseName();
$col = "Tables_in_" . $dbName;

foreach ($tables as $t) {
    $tName = $t->$col;
    $cols = Schema::getColumnListing($tName);
    foreach ($cols as $c) {
        try {
            $records = DB::table($tName)->where($c, $targetOrderId)->get();
            if ($records->isNotEmpty()) {
                echo ">>> MATCH FOUND IN TABLE [{$tName}] COLUMN [{$c}]:\\n";
                foreach ($records as $r) {
                    echo "  Row: " . json_encode($r, JSON_UNESCAPED_UNICODE) . "\\n";
                }
            }
        } catch (\\Throwable $e) {}
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
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/inspect_target_order2.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 inspect_target_order2.php && rm inspect_target_order2.php")
print(f"OUT:\n{out}")

client.close()
