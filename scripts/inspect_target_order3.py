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
echo "1. ALIEXPRESS API RAW RESPONSE FOR ORDER {$targetOrderId}\\n";
echo "=========================================================\\n";

$oauth = app(AliExpressOAuthService::class);
$apiClient = app(AliExpressApiClient::class);
$token = $oauth->latestToken();

$res = $apiClient->call('aliexpress.trade.ds.order.get', $token->access_token, [
    'order_id' => $targetOrderId,
]);

echo "Raw API Response:\\n";
print_r($res);

echo "\\n=========================================================\\n";
echo "2. COLUMNS OF procurement_batches\\n";
echo "=========================================================\\n";
print_r(Schema::getColumnListing('procurement_batches'));

echo "\\n=========================================================\\n";
echo "3. SEARCHING procurement_batches FOR {$targetOrderId}\\n";
echo "=========================================================\\n";
$allBatches = DB::table('procurement_batches')->get();
foreach ($allBatches as $b) {
    $raw = json_encode($b, JSON_UNESCAPED_UNICODE);
    if (str_contains($raw, $targetOrderId)) {
        echo "MATCH IN BATCH #{$b->id}:\\n" . $raw . "\\n";
    }
}

echo "\\n=========================================================\\n";
echo "4. SEARCHING purchase_orders FOR {$targetOrderId}\\n";
echo "=========================================================\\n";
$allPos = DB::table('purchase_orders')->get();
foreach ($allPos as $p) {
    $raw = json_encode($p, JSON_UNESCAPED_UNICODE);
    if (str_contains($raw, $targetOrderId)) {
        echo "MATCH IN PO #{$p->id}:\\n" . $raw . "\\n";
    }
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/inspect_target_order3.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 inspect_target_order3.php && rm inspect_target_order3.php")
print(f"OUT:\n{out}")

client.close()
