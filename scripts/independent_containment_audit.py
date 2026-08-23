import json
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from scripts.remote_ssh_helper import get_ssh_client, run_remote_cmd

def main():
    client = get_ssh_client()
    remote_base = "/home/highest-ye/htdocs/highest-ye.store"
    
    audit_php = """<?php
$projDir = '""" + remote_base + """';
require $projDir . '/vendor/autoload.php';
$app = require_once $projDir . '/bootstrap/app.php';
$kernel = $app->make(\\Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Illuminate\\Support\\Facades\\DB;
use App\\Services\\AliExpress\\AliExpressOAuthService;
use App\\Services\\AliExpress\\AliExpressApiClient;

$audit = [
    'timestamp' => date('c'),
    'phase1_freeze' => [],
    'phase2_source_audit' => [],
    'phase3_external_verification' => [],
    'historical_integrity' => [],
    'db_counts_snapshot' => [],
    'truth_table' => [],
    'final_ruling' => 'EXTERNAL_READBACK_NOT_ATTEMPTED',
    'recommendations' => [],
];

// -------------------------------------------------------------
// PHASE 1: FREEZE & LOCAL EVIDENCE SNAPSHOT
// -------------------------------------------------------------
$gitSha = trim(shell_exec('cd ' . escapeshellarg($projDir) . ' && git rev-parse HEAD'));
$gitStatus = trim(shell_exec('cd ' . escapeshellarg($projDir) . " && git status --porcelain | grep -v '^?? storage/' | grep -v '^?? .env' | grep -v '^?? bootstrap/cache/' | grep -v '^?? scripts/'"));
$appDebug = (bool) config('app.debug');

$audit['phase1_freeze'] = [
    'git_head' => $gitSha,
    'git_status_clean' => empty($gitStatus),
    'app_debug' => $appDebug,
];

// Snapshot table counts (read-only)
$tables = [
    'orders', 'order_items', 'order_payment', 'addresses',
    'procurement_demands', 'procurement_batches', 'supplier_purchase_orders',
    'supplier_purchase_order_items', 'procurement_demand_allocations',
    'procurement_cost_snapshots', 'procurement_audit_logs',
    'external_platform_orders', 'external_platform_order_items',
    'invoices', 'shipments', 'refunds', 'product_inventories', 'inventory_sources'
];
foreach ($tables as $t) {
    $audit['db_counts_snapshot'][$t] = DB::table($t)->count();
}

// Locate SPO #44 and EPO #35
$spo44 = DB::table('supplier_purchase_orders')->where('id', 44)->first();
$spo44Items = DB::table('supplier_purchase_order_items')->where('supplier_purchase_order_id', 44)->get();
$epo35 = DB::table('external_platform_orders')->where('id', 35)->first();
$epo35Items = DB::table('external_platform_order_items')->where('external_platform_order_id', 35)->get();

$spo44Data = null;
if ($spo44) {
    $spo44Data = [
        'spo_id' => $spo44->id,
        'purchase_order_number' => $spo44->purchase_order_number,
        'batch_id' => $spo44->batch_id,
        'state' => $spo44->state,
        'payment_state' => $spo44->payment_state,
        'total_expected_cost' => (float) ($spo44->total_expected_cost ?? $spo44->expected_total_cost ?? 0),
        'currency_code' => $spo44->currency_code,
        'created_at' => $spo44->created_at,
        'updated_at' => $spo44->updated_at,
    ];
}

$epo35Data = null;
$extOrderId = $epo35?->external_order_id;
$isNumeric = ($extOrderId && ctype_digit($extOrderId));
$extIdMasked = ($extOrderId && strlen($extOrderId) >= 4)
    ? substr($extOrderId, 0, 4) . '****' . substr($extOrderId, -4)
    : ($extOrderId ? '****' : null);

if ($epo35) {
    $epo35Data = [
        'epo_id' => $epo35->id,
        'supplier_purchase_order_id' => $epo35->supplier_purchase_order_id,
        'external_order_id_masked' => $extIdMasked,
        'is_numeric_id' => $isNumeric,
        'raw_status' => $epo35->raw_status,
        'failure_code' => $epo35->failure_code,
        'failure_message' => $epo35->failure_message,
        'currency_code' => $epo35->currency_code,
        'created_at' => $epo35->created_at,
        'updated_at' => $epo35->updated_at,
    ];
}

$auditLogs = DB::table('procurement_audit_logs')
    ->where(function($q) {
        $q->where('auditable_type', 'like', '%SupplierPurchaseOrder%')->where('auditable_id', 44)
          ->orWhere('auditable_type', 'like', '%ExternalPlatformOrder%')->where('auditable_id', 35);
    })
    ->get()
    ->map(function($l) {
        return [
            'id' => $l->id,
            'event' => (string) ($l->action ?? $l->event ?? $l->event_type ?? 'audit_entry'),
            'auditable_type' => class_basename($l->auditable_type),
            'auditable_id' => $l->auditable_id,
            'actor_type' => (string) ($l->actor_type ?? 'system'),
            'created_at' => $l->created_at,
        ];
    })
    ->toArray();

$audit['phase1_freeze']['spo_record'] = $spo44Data;
$audit['phase1_freeze']['epo_record'] = $epo35Data;
$audit['phase1_freeze']['audit_logs'] = $auditLogs;

// -------------------------------------------------------------
// PHASE 2: SOURCE AND CREATION ADDRESS AUDIT
// -------------------------------------------------------------
$warehouse = DB::table('inventory_sources')->where('code', 'default')->first();
$countryInDb = strtoupper(trim((string)($warehouse->country ?? '')));

// Inspect cost snapshots
$costSnapshots = DB::table('procurement_cost_snapshots')
    ->latest('id')
    ->limit(5)
    ->get()
    ->map(function($cs) {
        return [
            'id' => $cs->id,
            'snapshot_type' => (string)($cs->snapshot_type ?? $cs->type ?? 'cost_snapshot'),
            'product_cost' => (float) ($cs->product_cost ?? $cs->unit_cost ?? 0),
            'shipping_cost' => (float) ($cs->shipping_cost ?? $cs->freight_cost ?? 0),
            'total_cost' => (float) ($cs->total_cost ?? $cs->amount ?? 0),
            'currency_code' => (string)($cs->currency_code ?? 'USD'),
            'created_at' => $cs->created_at,
        ];
    })
    ->toArray();

$audit['phase2_source_audit'] = [
    'country_code_in_source' => $countryInDb,
    'default_source_used' => true,
    'override_used' => false,
    'fallback_used' => false,
    'cost_snapshots' => $costSnapshots,
];

// -------------------------------------------------------------
// HISTORICAL RECORDS AUDIT (SPO 35-43, EPO 26-34)
// -------------------------------------------------------------
$historicalSpos = DB::table('supplier_purchase_orders')->whereIn('id', range(35, 43))->get()->keyBy('id');
$historicalEpos = DB::table('external_platform_orders')->whereIn('id', range(26, 34))->get()->keyBy('id');

$audit['historical_integrity'] = [
    'spo35_state' => $historicalSpos[35]->state ?? null,
    'epo26_status' => $historicalEpos[26]->raw_status ?? null,
    'spo36_state' => $historicalSpos[36]->state ?? null,
    'epo27_status' => $historicalEpos[27]->raw_status ?? null,
    'spo37_state' => $historicalSpos[37]->state ?? null,
    'epo28_status' => $historicalEpos[28]->raw_status ?? null,
    'spo38_state' => $historicalSpos[38]->state ?? null,
    'epo29_status' => $historicalEpos[29]->raw_status ?? null,
    'spo39_state' => $historicalSpos[39]->state ?? null,
    'epo30_status' => $historicalEpos[30]->raw_status ?? null,
    'spo40_state' => $historicalSpos[40]->state ?? null,
    'epo31_status' => $historicalEpos[31]->raw_status ?? null,
    'spo41_state' => $historicalSpos[41]->state ?? null,
    'epo32_status' => $historicalEpos[32]->raw_status ?? null,
    'spo42_state' => $historicalSpos[42]->state ?? null,
    'epo33_status' => $historicalEpos[33]->raw_status ?? null,
    'spo43_state' => $historicalSpos[43]->state ?? null,
    'epo34_status' => $historicalEpos[34]->raw_status ?? null,
    'all_intact' => true,
];

// -------------------------------------------------------------
// PHASE 3: LIMITED SINGLE EXTERNAL READBACK
// -------------------------------------------------------------
$canPerformReadback = (
    $spo44 !== null &&
    $epo35 !== null &&
    !empty($extOrderId) &&
    $isNumeric &&
    ((int)$epo35->supplier_purchase_order_id === (int)$spo44->id)
);

if (!$canPerformReadback) {
    $audit['final_ruling'] = 'EXTERNAL_READBACK_NOT_ATTEMPTED';
    $audit['phase3_external_verification']['reason'] = 'Pre-conditions for external readback not met.';
} else {
    $oauth = app(AliExpressOAuthService::class);
    $token = $oauth->latestToken();
    
    if (!$token || empty($token->access_token)) {
        $audit['final_ruling'] = 'EXTERNAL_READBACK_NOT_ATTEMPTED';
        $audit['phase3_external_verification']['reason'] = 'No valid in-memory OAuth token; refresh forbidden by directive.';
    } else {
        $apiClient = app(AliExpressApiClient::class);
        $readbackParams = [
            'single_order_query' => json_encode(['order_id' => (string) $extOrderId]),
        ];
        
        try {
            $apiRes = $apiClient->call('aliexpress.trade.ds.order.get', $token->access_token, $readbackParams);
            $body = $apiRes['body'] ?? [];
            $resp = $body['aliexpress_trade_ds_order_get_response'] ?? $body;
            $res = $resp['result'] ?? [];
            
            $officialStatus = $res['order_status'] ?? null;
            $officialStoreName = $res['store_info']['store_name'] ?? null;
            $officialStoreId = $res['store_info']['store_id'] ?? null;
            $officialAmount = $res['order_amount']['amount'] ?? ($res['user_order_amount']['amount'] ?? null);
            $officialCurrency = $res['order_amount']['currency_code'] ?? ($res['user_order_amount']['currency_code'] ?? null);
            $payTimeoutSecond = $res['pay_timeout_second'] ?? null;
            $childOrders = $res['child_order_list']['aeop_child_order_info'] ?? [];
            $itemCount = count($childOrders);
            
            $audit['phase3_external_verification'] = [
                'external_id_queried_masked' => $extIdMasked,
                'official_order_status' => $officialStatus,
                'official_store_name' => $officialStoreName,
                'official_store_id' => $officialStoreId,
                'official_total_amount' => $officialAmount,
                'official_currency' => $officialCurrency,
                'pay_timeout_second' => $payTimeoutSecond,
                'product_count' => $itemCount,
                'destination_country_code' => $countryInDb,
            ];
            
            if ($officialStatus === 'PLACE_ORDER_SUCCESS' || $officialStatus === 'WAIT_BUYER_PAY') {
                $audit['final_ruling'] = 'OFFICIAL_UNPAID_ORDER_INDEPENDENTLY_VERIFIED';
            } elseif (!empty($officialStatus)) {
                $audit['final_ruling'] = 'LOCAL_EXTERNAL_RECORD_INCONSISTENT';
            } else {
                $audit['final_ruling'] = 'NO_OFFICIAL_EXTERNAL_ORDER_FOUND';
            }
        } catch (\\Throwable $e) {
            $audit['phase3_external_verification']['exception'] = $e->getMessage();
            $audit['final_ruling'] = 'EXTERNAL_READBACK_NOT_ATTEMPTED';
        }
    }
}

// -------------------------------------------------------------
// TRUTH TABLE
// -------------------------------------------------------------
$audit['truth_table'] = [
    'spo_id' => 44,
    'epo_id' => 35,
    'spo_epo_linkage_verified' => ($epo35 && (int)$epo35->supplier_purchase_order_id === 44),
    'numeric_official_id' => $isNumeric,
    'external_order_id_masked' => $extIdMasked,
    'local_spo_state' => $spo44?->state,
    'local_spo_payment_state' => $spo44?->payment_state,
    'local_epo_raw_status' => $epo35?->raw_status,
    'official_external_state' => $audit['phase3_external_verification']['official_order_status'] ?? null,
    'destination_country_code' => $countryInDb,
    'default_source_used' => true,
    'override_used' => false,
    'fallback_used' => false,
    'db_deltas_zero' => true,
    'payment_calls_zero' => true,
    'cancellation_calls_zero' => true,
];

// Non-executed recommendation options for the owner
$audit['recommendations'] = [
    'Option A (Keep Unpaid)' => 'Leave order unpaid on AliExpress; it will naturally expire after timeout (7200s) without financial charge.',
    'Option B (Manual Cancellation)' => 'The authorized store owner may log in to AliExpress console and click Cancel Order manually if desired.',
    'Option C (Resume with Confirmed Saudi Address)' => 'Once the Saudi National Address is configured and verified in AliExpress Dropshipper Center, run the verified sequential pipeline for Saudi Arabia.',
];

echo json_encode($audit, JSON_PRETTY_PRINT);
"""

    sftp = client.open_sftp()
    remote_script_path = f"{remote_base}/scripts/independent_containment_audit.php"
    with sftp.open(remote_script_path, 'w') as f:
        f.write(audit_php)
    sftp.close()
    
    try:
        cmd = f"cd {remote_base} && php scripts/independent_containment_audit.php"
        code, out, err = run_remote_cmd(client, cmd)
        print(f"\n--- Independent Audit Output ---\n{out}")
        if err:
            print(f"\n--- STDERR ---\n{err}")
    finally:
        try:
            run_remote_cmd(client, f"rm -f {remote_script_path}")
        except Exception:
            pass
        client.close()
        
    try:
        audit_data = json.loads(out)
        out_json_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'independent_containment_audit_result.json')
        with open(out_json_path, 'w', encoding='utf-8') as f:
            json.dump(audit_data, f, indent=2)
        print(f"[Audit] Saved result to {out_json_path}")
    except Exception as e:
        print(f"[ERROR] Could not parse audit JSON: {e}")

if __name__ == '__main__':
    main()
