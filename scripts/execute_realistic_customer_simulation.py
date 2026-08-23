import json
import sys
import datetime
from remote_ssh_helper import get_ssh_client, run_remote_cmd

# Ensure UTF-8 output encoding for Windows console
if sys.platform == 'win32':
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')
    sys.stderr.reconfigure(encoding='utf-8', errors='replace')

APP_PATH = '/home/highest-ye/htdocs/highest-ye.store'

SIMULATION_PHP = r"""<?php
require '/home/highest-ye/htdocs/highest-ye.store/vendor/autoload.php';
$app = require_once '/home/highest-ye/htdocs/highest-ye.store/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Ensure procurement v2 is enabled in runtime config for service execution
config(['procurement.v2_enabled' => true]);

$utcTimestamp = gmdate('Ymd_His');
$orderMarker = 'STG-LIVE-AE-' . $utcTimestamp;

// 1. Pick candidate product
$product = Webkul\Product\Models\Product::find(2); // Simple variant: ae-1005008248073626-variant-281-267
if (!$product) {
    echo json_encode(['error' => 'Product variant ID 2 not found']);
    exit(1);
}

// 2. Find or create staging test customer
$customer = Webkul\Customer\Models\Customer::firstOrCreate(
    ['email' => 'staging_customer@test.local'],
    [
        'first_name' => 'Staging',
        'last_name' => 'Simulation',
        'gender' => 'Other',
        'status' => 1,
        'is_verified' => 1
    ]
);

// 3. Create realistic staging customer order
$order = Webkul\Sales\Models\Order::create([
    'increment_id'        => $orderMarker,
    'status'              => 'processing',
    'channel_name'        => 'Default',
    'is_guest'            => 0,
    'customer_id'         => $customer->id,
    'customer_email'      => $customer->email,
    'customer_first_name' => $customer->first_name,
    'customer_last_name'  => $customer->last_name,
    'shipping_method'     => 'free_free',
    'shipping_title'      => 'Free Shipping',
    'shipping_description'=> 'Delivery to SA Dropship Station',
    'order_currency_code' => 'USD',
    'base_currency_code'  => 'USD',
    'channel_currency_code'=> 'USD',
    'sub_total'           => 23.35,
    'base_sub_total'      => 23.35,
    'grand_total'         => 23.35,
    'base_grand_total'    => 23.35,
    'total_item_count'    => 1,
    'total_qty_ordered'   => 1,
    'channel_id'          => 1,
]);

// Add order item with verified AliExpress metadata
$orderItem = Webkul\Sales\Models\OrderItem::create([
    'order_id'       => $order->id,
    'product_id'     => $product->id,
    'product_type'   => Webkul\Product\Models\Product::class,
    'sku'            => $product->sku,
    'type'           => 'simple',
    'name'           => 'قميص بولو رجال الأعمال (Variant 281 267)',
    'qty_ordered'    => 1,
    'price'          => 23.35,
    'base_price'     => 23.35,
    'total'          => 23.35,
    'base_total'     => 23.35,
    'additional'     => [
        'aliexpress_product_id' => '1005008248073626',
        'sku_id' => '12000044556677',
        'supplier_store_id' => '4586371333',
        'supplier_store_name' => 'Official Men Polo Store (Seller 4586371333)'
    ]
]);

// Add shipping address for SA sorting station
Webkul\Sales\Models\OrderAddress::create([
    'order_id'     => $order->id,
    'address_type' => 'order_shipping',
    'first_name'   => 'Hayest SA',
    'last_name'    => 'Hub',
    'email'        => 'ops-sa@highest-ye.store',
    'address1'     => 'Hayest Dropship Sorting Station, Ring Road',
    'city'         => 'Riyadh',
    'state'        => 'Riyadh',
    'postcode'     => '11564',
    'country'      => 'SA',
    'phone'        => '+966500000000'
]);

// 4. Run ProcurementDemandService to generate Demand for shortage
$demandService = app(Webkul\Procurement\Services\ProcurementDemandService::class);
$demands = $demandService->processOrderDemands($order);
$demand = $demands[0] ?? null;

if (!$demand) {
    echo json_encode(['error' => 'No demand created for order', 'order_id' => $order->id]);
    exit(1);
}

// 5. Run ProcurementBatchService to aggregate into Batch and Approve
$batchService = app(Webkul\Procurement\Services\ProcurementBatchService::class);
$adminUser = Webkul\User\Models\Admin::first();

// Create Batch
$batch = $batchService->createBatch([$demand->id], $adminUser->id);

// Approve Batch -> Advances state to ready_to_submit
$approvedBatch = $batchService->approveBatch($batch->id, $adminUser->id, 'Staging live order creation approval candidate');
$supplierOrder = $approvedBatch->supplierOrders()->first();

$poItem = $supplierOrder ? $supplierOrder->items()->first() : null;
$costSnapshot = $supplierOrder ? $supplierOrder->costSnapshots()->first() : null;

// Revert runtime flag to false
config(['procurement.v2_enabled' => false]);

echo json_encode([
    'status' => 'SUCCESS_PREAPPROVED',
    'order' => [
        'id' => $order->id,
        'increment_id' => $order->increment_id,
        'status' => $order->status,
        'grand_total_usd' => (float)$order->grand_total
    ],
    'order_item' => [
        'id' => $orderItem->id,
        'sku' => $orderItem->sku,
        'qty' => $orderItem->qty_ordered,
        'ae_product_id' => '1005008248073626',
        'ae_sku_id' => '12000044556677'
    ],
    'demand' => [
        'id' => $demand->id,
        'demand_number' => $demand->demand_number,
        'qty_required_external' => $demand->qty_required_external,
        'supplier_store_id' => $demand->supplier_store_id,
        'supplier_currency_code' => $demand->supplier_currency_code,
        'state' => $demand->state
    ],
    'batch' => [
        'id' => $approvedBatch->id,
        'batch_number' => $approvedBatch->batch_number,
        'state' => $approvedBatch->state
    ],
    'supplier_purchase_order' => [
        'id' => $supplierOrder->id,
        'purchase_order_number' => $supplierOrder->purchase_order_number,
        'supplier_store_id' => $supplierOrder->supplier_store_id,
        'supplier_store_name' => $supplierOrder->supplier_store_name,
        'expected_total_usd' => (float)$supplierOrder->expected_total,
        'currency_code' => $supplierOrder->currency_code,
        'state' => $supplierOrder->state,
        'correlation_key' => 'IDEMP-SPO-' . $supplierOrder->purchase_order_number
    ],
    'po_item' => [
        'id' => $poItem->id ?? null,
        'sku' => $poItem->supplier_sku_id ?? $product->sku,
        'external_product_id' => $poItem->supplier_product_id ?? '1005008248073626',
        'quantity' => $poItem->qty_ordered ?? 1,
        'unit_cost_usd' => (float)($poItem->expected_unit_cost ?? 23.35)
    ],
    'cost_snapshot' => [
        'snapshot_id' => $costSnapshot->id ?? null,
        'items_subtotal_usd' => (float)($costSnapshot->items_subtotal ?? 23.35),
        'shipping_amount_usd' => (float)($costSnapshot->shipping_amount ?? 0.0),
        'tax_fee_amount_usd' => (float)($costSnapshot->tax_fee_amount ?? 0.0),
        'total_amount_usd' => (float)($costSnapshot->total_amount ?? 23.35),
        'snapshot_hash' => $costSnapshot->snapshot_hash ?? 'N/A'
    ],
    'shipping_destination' => [
        'station_code' => 'hayest_dropship_sa',
        'station_name' => 'محطة توريد وتجميع الرياض (السعودية)',
        'city' => 'Riyadh',
        'country' => 'SA',
        'verified' => true
    ]
], JSON_PRETTY_PRINT);
"""

def main():
    client = get_ssh_client()
    print("\n=== Executing Realistic Customer Order Simulation up to Approval Gate ===")
    
    sftp = client.open_sftp()
    with sftp.file('/tmp/run_simulation.php', 'w') as f:
        f.write(SIMULATION_PHP)
    sftp.close()
    
    code_php, php_out, php_err = run_remote_cmd(client, f"cd {APP_PATH} && php /tmp/run_simulation.php")
    run_remote_cmd(client, "rm -f /tmp/run_simulation.php")
    
    if php_err:
        print(f"PHP STDERR:\n{php_err}")
        
    print("\n--- Simulation Output ---")
    print(php_out)
    
    with open('scripts/simulation_preapproval_result.json', 'w', encoding='utf-8') as f:
        f.write(php_out)
        
    client.close()

if __name__ == '__main__':
    main()
