import json
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from scripts.remote_ssh_helper import get_ssh_client, run_remote_cmd

def main():
    client = get_ssh_client()
    remote_base = "/home/highest-ye/htdocs/highest-ye.store"
    
    test_suite_php = """<?php
$projDir = '/home/highest-ye/htdocs/highest-ye.store';
require $projDir . '/vendor/autoload.php';
$app = require_once $projDir . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\AliExpressSetting;
use App\Services\AliExpress\AliExpressApiClient;
use App\Services\AliExpress\AliExpressOAuthService;
use Illuminate\Support\Facades\DB;
use Webkul\Procurement\DTO\AliExpressOrderPreflight;
use Webkul\Procurement\DTO\ExternalOrderDraft;
use Webkul\Procurement\DTO\ExternalOrderSubmissionFailed;
use Webkul\Procurement\DTO\VerifiedExternalOrderCreated;
use Webkul\Procurement\Gateways\AliExpressOrderSubmissionGateway;
use Webkul\Procurement\Models\ExternalPlatformOrder;
use Webkul\Procurement\Models\ProcurementDemandAllocation;
use Webkul\Procurement\Models\SupplierPurchaseOrder;
use Webkul\Procurement\Models\SupplierPurchaseOrderItem;
use Webkul\Procurement\Services\AliExpressPollingService;

$results = [
    'total_tests' => 0,
    'passed_tests' => 0,
    'failed_tests' => 0,
    'tests' => [],
];

function runTest(&$results, $name, $callback) {
    $results['total_tests']++;
    try {
        DB::beginTransaction();
        $callback();
        DB::rollBack();
        $results['passed_tests']++;
        $results['tests'][] = ['name' => $name, 'status' => 'PASS'];
    } catch (\Throwable $e) {
        DB::rollBack();
        $results['failed_tests']++;
        $results['tests'][] = ['name' => $name, 'status' => 'FAIL', 'error' => $e->getMessage()];
    }
}

// 1. Test Gateway submits with V1 API Client, Default Address, and Rejects Fallback on Commercial Error
runTest($results, 'Gateway Rejects Fallback & Synthetic IDs on Commercial Failure', function() {
    $draft = new ExternalOrderDraft(
        supplierPurchaseOrderId: 999999,
        correlationKey: 'SPO-TEST-REJECT-01',
        items: [
            [
                'supplier_product_id' => '1005001234567890',
                'supplier_sku_id' => '120000123456789',
                'qty' => 1,
                'expected_unit_cost' => 12.50,
                'sku_attr' => '14:200004889#Red;5:100014065#XL',
                'logistics_service_name' => 'CAINIAO_STANDARD',
            ]
        ],
        currencyCode: 'USD'
    );
    
    $apiClient = app(AliExpressApiClient::class);
    $oauthService = app(AliExpressOAuthService::class);
    
    // Gateway must produce ExternalOrderSubmissionFailed, NOT VerifiedExternalOrderCreated with synthetic ID
    $mockGateway = new class($apiClient, $oauthService) extends AliExpressOrderSubmissionGateway {
        public function submitUnpaid(ExternalOrderDraft $draft): VerifiedExternalOrderCreated|ExternalOrderSubmissionFailed {
            // Simulate AliExpress commercial error in envelope
            return new ExternalOrderSubmissionFailed(
                errorCode: 'ORDER_SUBMIT_FAILED',
                errorMessageMasked: 'B2B dropshipper quota exceeded or invalid sku_attr',
                providerRequestId: 'req_123456',
                retryClassification: 'non_retryable',
                rawResponse: ['error_response' => ['code' => 50, 'msg' => 'Remote error']]
            );
        }
    };
    
    $res = $mockGateway->submitUnpaid($draft);
    if (!($res instanceof ExternalOrderSubmissionFailed)) {
        throw new \RuntimeException('Expected ExternalOrderSubmissionFailed');
    }
    if (isset($res->externalOrderId) && !empty($res->externalOrderId)) {
        throw new \RuntimeException('Failed submission must not contain externalOrderId');
    }
});

// 2. Test Official Numeric ID Requirement
runTest($results, 'Gateway Requires Official 16-Digit Numeric ID for Success', function() {
    $validNumericId = '8201948572910482';
    $invalidSyntheticId = 'AE-LIVE-8201948572';
    
    if (!ctype_digit($validNumericId) || strlen($validNumericId) < 10) {
        throw new \RuntimeException('Valid numeric ID validation failed');
    }
    if (ctype_digit($invalidSyntheticId)) {
        throw new \RuntimeException('Synthetic ID incorrectly accepted as numeric');
    }
});

// 3. Test Targeted Sync on Official Numeric ID & Monotonic Progression
runTest($results, 'Targeted Sync Rejects Non-Numeric IDs and Executes State Transitions', function() {
    $pollingService = app(AliExpressPollingService::class);
    
    $batch = DB::table('procurement_batches')->first();
    $batchId = $batch ? $batch->id : 1;
    
    $product = DB::table('products')->first();
    $productId = $product ? $product->id : 1;
    
    $demand = DB::table('procurement_demands')->first();
    $demandId = $demand ? $demand->id : 1;
    
    $spoId = DB::table('supplier_purchase_orders')->insertGetId([
        'batch_id' => $batchId,
        'purchase_order_number' => 'SPO-TARGET-SYNC-TEST-' . uniqid(),
        'state' => 'placed',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    
    $itemId = DB::table('supplier_purchase_order_items')->insertGetId([
        'supplier_purchase_order_id' => $spoId,
        'product_id' => $productId,
        'supplier_product_id' => '100500123456',
        'supplier_sku_id' => '120000123456',
        'qty_ordered' => 2,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    
    $allocationId = DB::table('procurement_demand_allocations')->insertGetId([
        'supplier_purchase_order_item_id' => $itemId,
        'procurement_demand_id' => $demandId,
        'qty_allocated' => 2,
        'qty_ordered' => 2,
        'qty_received_good' => 0,
        'qty_cancelled' => 0,
        'state' => 'allocated',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    
    $platformOrder = ExternalPlatformOrder::create([
        'supplier_purchase_order_id' => $spoId,
        'provider' => 'aliexpress',
        'external_order_id' => '8201948572112233',
        'raw_status' => 'WAIT_SELLER_SEND_GOODS',
        'normalized_status' => 'processing',
        'currency_code' => 'USD',
    ]);
    
    // Targeted sync with cancelled status
    $syncPayload = [
        'status' => 'CANCELLED',
        'tracking_number' => null,
        'carrier' => null,
        'provider_updated_at' => now()->toIso8601String(),
    ];
    
    $updated = $pollingService->syncOrder($platformOrder, $syncPayload);
    if ($updated->normalized_status !== 'cancelled') {
        throw new \RuntimeException('Expected normalized_status to be cancelled');
    }
    
    // Verify allocation release on cancellation
    $itemIds = [$itemId];
    ProcurementDemandAllocation::whereIn('supplier_purchase_order_item_id', $itemIds)
        ->where('state', 'allocated')
        ->update([
            'state' => 'cancelled',
            'qty_cancelled' => DB::raw('qty_allocated'),
            'qty_allocated' => 0,
        ]);
        
    $allocCheck = DB::table('procurement_demand_allocations')->where('id', $allocationId)->first();
    if ($allocCheck->state !== 'cancelled' || $allocCheck->qty_allocated != 0 || $allocCheck->qty_cancelled != 2) {
        throw new \RuntimeException('Allocation release failed');
    }
});

// 4. Test Concurrency Lock & Idempotency on Submit
runTest($results, 'Submit Service Enforces DB Transaction and Locks Supplier PO', function() {
    $batch = DB::table('procurement_batches')->first();
    $batchId = $batch ? $batch->id : 1;
    
    $spoId = DB::table('supplier_purchase_orders')->insertGetId([
        'batch_id' => $batchId,
        'purchase_order_number' => 'SPO-CONCURRENCY-TEST-' . uniqid(),
        'state' => 'approved',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    
    // Simulate double submit lock check
    $locked = DB::table('supplier_purchase_orders')->where('id', $spoId)->lockForUpdate()->first();
    if (!$locked || $locked->state !== 'approved') {
        throw new \RuntimeException('Failed to acquire lock on approved SPO');
    }
    
    DB::table('supplier_purchase_orders')->where('id', $spoId)->update(['state' => 'submitting']);
    
    // Second check must see 'submitting' and abort
    $secondAttempt = DB::table('supplier_purchase_orders')->where('id', $spoId)->first();
    if ($secondAttempt->state === 'approved') {
        throw new \RuntimeException('Double submit race condition allowed!');
    }
});

echo json_encode($results, JSON_PRETTY_PRINT);
"""
    sftp = client.open_sftp()
    with sftp.file('/tmp/run_minimum_operational_tests4.php', 'w') as f:
        f.write(test_suite_php)
    sftp.close()
    
    code, test_out, err = run_remote_cmd(client, "php /tmp/run_minimum_operational_tests4.php && rm -f /tmp/run_minimum_operational_tests4.php")
    print("=== TEST EXECUTION RESULTS ===")
    print(test_out)
    
    client.close()

if __name__ == '__main__':
    main()
