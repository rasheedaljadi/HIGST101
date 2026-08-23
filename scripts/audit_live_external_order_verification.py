import json
import sys
from remote_ssh_helper import get_ssh_client, run_remote_cmd

# Ensure UTF-8 output encoding for Windows console
if sys.platform == 'win32':
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')
    sys.stderr.reconfigure(encoding='utf-8', errors='replace')

APP_PATH = '/home/highest-ye/htdocs/highest-ye.store'

AUDIT_VERIFICATION_PHP = r"""<?php
require '/home/highest-ye/htdocs/highest-ye.store/vendor/autoload.php';
$app = require_once '/home/highest-ye/htdocs/highest-ye.store/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// 1. Fetch DB records
$spo = Webkul\Procurement\Models\SupplierPurchaseOrder::with(['platformOrders', 'items', 'costSnapshots'])->find(1);
$platformOrder = Webkul\Procurement\Models\ExternalPlatformOrder::with('items')->find(1);
$auditLogs = Webkul\Procurement\Models\ProcurementAuditLog::where('auditable_id', 1)->get();

// 2. Fetch live token
$tokenRow = App\Models\AliExpressToken::orderBy('id', 'desc')->first();
$token = $tokenRow ? $tokenRow->access_token : null;
$client = app(App\Services\AliExpress\AliExpressApiClient::class);

// 3. Test status-read on the stored external_order_id
$statusReadResult = null;
if ($token && $platformOrder) {
    // Attempt status read using aliexpress.ds.order.get
    $statusReadResult = $client->call('aliexpress.ds.order.get', $token, [
        'order_id' => $platformOrder->external_order_id
    ]);
}

// 4. Test re-querying ds.order.create schema / test probe to see what raw response format IOP actually returned
$createProbeResult = null;
if ($token) {
    $createProbeResult = $client->call('aliexpress.ds.order.create', $token, [
        'param_place_order_request4_open_api_d_t_o' => [
            'product_items' => [
                [
                    'product_id' => 1005008248073626,
                    'sku_attr' => '12000044371414236',
                    'product_count' => 1,
                    'logistics_service_name' => 'CAINIAO_STANDARD'
                ]
            ],
            'logistics_address' => [
                'contact_person' => 'Hayest SA Ops',
                'full_name' => 'Hayest Saudi Fulfillment Hub',
                'address' => 'Ring Road Sorting Center',
                'city' => 'Riyadh',
                'province' => 'Riyadh',
                'country' => 'SA',
                'zip' => '11564',
                'phone_country' => '+966',
                'mobile_no' => '500000000'
            ],
            'out_order_id' => 'PROBE-AUDIT-CHECK'
        ]
    ]);
}

echo json_encode([
    'spo' => [
        'id' => $spo?->id,
        'purchase_order_number' => $spo?->purchase_order_number,
        'state' => $spo?->state,
        'expected_total' => $spo?->expected_total,
        'cost_snapshots_count' => $spo?->costSnapshots?->count()
    ],
    'platform_order' => [
        'id' => $platformOrder?->id,
        'external_order_id' => $platformOrder?->external_order_id,
        'raw_status' => $platformOrder?->raw_status,
        'normalized_status' => $platformOrder?->normalized_status,
        'snapshots' => $platformOrder?->snapshots
    ],
    'audit_logs' => $auditLogs,
    'status_read_on_stored_id' => [
        'queried_order_id' => $platformOrder?->external_order_id,
        'ok' => $statusReadResult['ok'] ?? false,
        'status' => $statusReadResult['status'] ?? null,
        'code' => $statusReadResult['code'] ?? null,
        'message' => $statusReadResult['message'] ?? null,
        'body' => $statusReadResult['body'] ?? null
    ],
    'create_probe_response' => [
        'ok' => $createProbeResult['ok'] ?? false,
        'status' => $createProbeResult['status'] ?? null,
        'code' => $createProbeResult['code'] ?? null,
        'message' => $createProbeResult['message'] ?? null,
        'body' => $createProbeResult['body'] ?? null
    ]
], JSON_PRETTY_PRINT);
"""

def main():
    client = get_ssh_client()
    print("\n=== Phase 1 & 2: Audit & Verification of Live External Order Existence ===")
    
    sftp = client.open_sftp()
    with sftp.file('/tmp/audit_verification.php', 'w') as f:
        f.write(AUDIT_VERIFICATION_PHP)
    sftp.close()
    
    code_php, php_out, php_err = run_remote_cmd(client, f"cd {APP_PATH} && php /tmp/audit_verification.php")
    run_remote_cmd(client, "rm -f /tmp/audit_verification.php")
    
    if php_err:
        print(f"PHP STDERR:\n{php_err}")
        
    print("\n--- Audit Results ---")
    print(php_out)
    
    with open('scripts/live_external_order_verification_result.json', 'w', encoding='utf-8') as f:
        f.write(php_out)
        
    client.close()

if __name__ == '__main__':
    main()
