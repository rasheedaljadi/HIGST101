<?php

namespace Database\Seeders;

use App\Models\AliExpressProductImport;
use App\Models\HigestSourceOffer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Webkul\Inventory\Models\InventorySource;
use Webkul\Procurement\Models\ExternalPlatformOrder;
use Webkul\Procurement\Models\ProcurementBatch;
use Webkul\Procurement\Models\ProcurementCostSnapshot;
use Webkul\Procurement\Models\ProcurementDemand;
use Webkul\Procurement\Models\ProcurementDemandAllocation;
use Webkul\Procurement\Models\SupplierPurchaseOrder;
use Webkul\Procurement\Models\SupplierPurchaseOrderItem;
use Webkul\Procurement\Services\AliExpressPollingService;
use Webkul\Procurement\Services\ProcurementBatchService;
use Webkul\Procurement\Services\ProcurementDemandService;
use Webkul\Procurement\Services\ProcurementManualPaymentService;
use Webkul\Procurement\Services\ProcurementSubmitService;
use Webkul\Product\Models\Product;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderItem;
use Webkul\Sales\Models\OrderPayment;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

class UatProcurementV2BrowserSeeder extends Seeder
{
    public function run(): void
    {
        config(['procurement.v2_enabled' => true]);

        $this->seedInventorySources();
        $this->seedRolesAndAdmins();
        $this->seedOperationalFixtures();
    }

    protected function seedInventorySources(): void
    {
        InventorySource::firstOrCreate(
            ['code' => 'hayest_internal_ye'],
            ['name' => 'Hayest Internal Yemen', 'country' => 'YE', 'is_salable' => 1, 'is_delivery_source' => 1, 'status' => 1]
        );

        InventorySource::firstOrCreate(
            ['code' => 'hayest_dropship_ye'],
            ['name' => 'Hayest Dropship Yemen', 'country' => 'YE', 'is_salable' => 1, 'is_delivery_source' => 1, 'status' => 1]
        );

        InventorySource::firstOrCreate(
            ['code' => 'hayest_dropship_sa'],
            ['name' => 'Hayest Dropship Saudi Hub', 'country' => 'SA', 'is_salable' => 0, 'is_delivery_source' => 0, 'status' => 1]
        );

        InventorySource::firstOrCreate(
            ['code' => 'hayest_quarantine_sa'],
            ['name' => 'Hayest Quarantine SA', 'country' => 'SA', 'is_salable' => 0, 'is_delivery_source' => 0, 'status' => 1]
        );

        InventorySource::firstOrCreate(
            ['code' => 'hayest_quarantine_ye'],
            ['name' => 'Hayest Quarantine YE', 'country' => 'YE', 'is_salable' => 0, 'is_delivery_source' => 0, 'status' => 1]
        );
    }

    protected function seedRolesAndAdmins(): void
    {
        // 1. Operator: view, batch_create, submit
        $opRole = Role::updateOrCreate(
            ['name' => 'UAT Procurement Operator Role'],
            ['permission_type' => 'custom', 'permissions' => [
                'dropshipping.procurement_v2.view',
                'dropshipping.procurement_v2.batch_create',
                'dropshipping.procurement_v2.submit',
            ]]
        );
        Admin::updateOrCreate(
            ['email' => 'uat_procurement_operator@test.local'],
            ['name' => 'مسؤول المشتريات (Operator)', 'password' => bcrypt('uat_password123'), 'role_id' => $opRole->id, 'status' => 1]
        );

        // 2. Approver: view, cost_view, batch_approve, variance_approve
        $appRole = Role::updateOrCreate(
            ['name' => 'UAT Procurement Approver Role'],
            ['permission_type' => 'custom', 'permissions' => [
                'dropshipping.procurement_v2.view',
                'dropshipping.procurement_v2.cost_view',
                'dropshipping.procurement_v2.batch_approve',
                'dropshipping.procurement_v2.variance_approve',
            ]]
        );
        Admin::updateOrCreate(
            ['email' => 'uat_procurement_approver@test.local'],
            ['name' => 'معتمد المشتريات (Approver)', 'password' => bcrypt('uat_password123'), 'role_id' => $appRole->id, 'status' => 1]
        );

        // 3. Finance: view, cost_view, payment_confirm, reports_view
        $finRole = Role::updateOrCreate(
            ['name' => 'UAT Procurement Finance Role'],
            ['permission_type' => 'custom', 'permissions' => [
                'dropshipping.procurement_v2.view',
                'dropshipping.procurement_v2.cost_view',
                'dropshipping.procurement_v2.payment_confirm',
                'dropshipping.procurement_v2.reports_view',
            ]]
        );
        Admin::updateOrCreate(
            ['email' => 'uat_procurement_finance@test.local'],
            ['name' => 'المشرف المالي (Finance)', 'password' => bcrypt('uat_password123'), 'role_id' => $finRole->id, 'status' => 1]
        );

        // 4. Receiver: view, exception_handle
        $recRole = Role::updateOrCreate(
            ['name' => 'UAT Procurement Receiver Role'],
            ['permission_type' => 'custom', 'permissions' => [
                'dropshipping.procurement_v2.view',
                'dropshipping.procurement_v2.exception_handle',
            ]]
        );
        Admin::updateOrCreate(
            ['email' => 'uat_procurement_receiver@test.local'],
            ['name' => 'أمين المستودع والاستلام (Receiver)', 'password' => bcrypt('uat_password123'), 'role_id' => $recRole->id, 'status' => 1]
        );

        // 5. Viewer: view only
        $viewRole = Role::updateOrCreate(
            ['name' => 'UAT Procurement Viewer Role'],
            ['permission_type' => 'custom', 'permissions' => [
                'dropshipping.procurement_v2.view',
            ]]
        );
        Admin::updateOrCreate(
            ['email' => 'uat_procurement_viewer@test.local'],
            ['name' => 'مستعرض التقارير (Viewer)', 'password' => bcrypt('uat_password123'), 'role_id' => $viewRole->id, 'status' => 1]
        );
    }

    protected function seedOperationalFixtures(): void
    {
        // Clean previous UAT data
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        ProcurementDemandAllocation::truncate();
        ProcurementCostSnapshot::truncate();
        ExternalPlatformOrder::truncate();
        SupplierPurchaseOrderItem::truncate();
        SupplierPurchaseOrder::truncate();
        ProcurementBatch::truncate();
        ProcurementDemand::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $demandService = app(ProcurementDemandService::class);
        $batchService = app(ProcurementBatchService::class);
        $submitService = app(ProcurementSubmitService::class);
        $manualPaymentService = app(ProcurementManualPaymentService::class);

        $operatorAdmin = Admin::where('email', 'uat_procurement_operator@test.local')->first();
        $approverAdmin = Admin::where('email', 'uat_procurement_approver@test.local')->first();
        $financeAdmin = Admin::where('email', 'uat_procurement_finance@test.local')->first();

        // 1. Create Products
        $pAlpha1 = $this->createUatProduct('UAT-POV2-SKU-ALP-01', 'store_uat_alpha', 10.0);
        $pAlpha2 = $this->createUatProduct('UAT-POV2-SKU-ALP-02', 'store_uat_alpha', 15.0);
        $pBeta1 = $this->createUatProduct('UAT-POV2-SKU-BET-01', 'store_uat_beta', 20.0);
        $pMissing = $this->createUatProduct('UAT-POV2-SKU-MIS-07', null, 12.0);
        $pConflict = $this->createUatProduct('UAT-POV2-SKU-CNF-08', 'store_uat_alpha', 18.0);

        // 2. Create Orders & Demands for Open Batching
        $ord1 = $this->createUatOrder('UAT-POV2-ORD-01', $pAlpha1, 2);
        $ord2 = $this->createUatOrder('UAT-POV2-ORD-02', $pAlpha2, 1);
        $ord3 = $this->createUatOrder('UAT-POV2-ORD-03', $pBeta1, 3);
        $ord4 = $this->createUatOrder('UAT-POV2-ORD-04', $pMissing, 1);
        $ord5 = $this->createUatOrder('UAT-POV2-ORD-05', $pConflict, 1, ['supplier_store_id' => 'store_uat_beta']);

        $demands1 = $demandService->processOrderDemands($ord1);
        $demands2 = $demandService->processOrderDemands($ord2);
        $demands3 = $demandService->processOrderDemands($ord3);
        $demands4 = $demandService->processOrderDemands($ord4);
        $demands5 = $demandService->processOrderDemands($ord5);

        // 3. Create another set of orders to build a Batch & SPOs in Ready for Review / Approved
        $pAlphaBatch = $this->createUatProduct('UAT-POV2-SKU-ALP-BAT', 'store_uat_alpha', 12.0);
        $pBetaBatch = $this->createUatProduct('UAT-POV2-SKU-BET-BAT', 'store_uat_beta', 20.0);

        $ordB1 = $this->createUatOrder('UAT-POV2-ORD-BAT-01', $pAlphaBatch, 2);
        $ordB2 = $this->createUatOrder('UAT-POV2-ORD-BAT-02', $pBetaBatch, 1);

        $demandsB1 = $demandService->processOrderDemands($ordB1);
        $demandsB2 = $demandService->processOrderDemands($ordB2);

        $batch = $batchService->createBatch([$demandsB1[0]->id, $demandsB2[0]->id], $operatorAdmin->id);

        // 4. Create an Order in Awaiting Manual Payment with ExternalPlatformOrder & Cost Variance
        $pVar = $this->createUatProduct('UAT-POV2-SKU-VAR-09', 'store_uat_var', 20.0);
        $ordVar = $this->createUatOrder('UAT-POV2-ORD-VAR-09', $pVar, 1);
        $demandsVar = $demandService->processOrderDemands($ordVar);

        $batchVar = $batchService->createBatch([$demandsVar[0]->id], $operatorAdmin->id);
        $batchService->approveBatch($batchVar->id, $approverAdmin->id);
        $submitService->submitBatch($batchVar->id, $operatorAdmin->id);

        $spoVar = SupplierPurchaseOrder::where('batch_id', $batchVar->id)->first();
        $poVar = ExternalPlatformOrder::where('supplier_purchase_order_id', $spoVar->id)->first();

        // Simulate AliExpress polling status change to WAIT_SELLER_SEND_GOODS with actual cost = $25.00
        $pollingService = app(AliExpressPollingService::class);
        $pollingService->syncOrder($poVar, [
            'status' => 'WAIT_SELLER_SEND_GOODS',
            'actual_total' => 25.0,
        ]);
    }

    protected function createUatProduct(string $sku, ?string $storeId, float $cost): Product
    {
        $product = Product::firstOrCreate(['sku' => $sku], ['type' => 'simple', 'attribute_family_id' => 1]);

        $payload = [];
        if ($storeId !== null) {
            $payload = [
                'store_id' => $storeId,
                'store_name' => 'متجر '.$storeId,
                'store_info' => ['store_id' => $storeId, 'store_name' => 'متجر '.$storeId],
            ];
        }

        AliExpressProductImport::updateOrCreate(
            ['product_id' => $product->id],
            [
                'aliexpress_product_id' => 'ae_'.$product->id,
                'status' => 'success',
                'payload_snapshot' => $payload,
                'shipping_currency' => 'USD',
            ]
        );

        HigestSourceOffer::updateOrCreate(
            ['product_id' => $product->id],
            [
                'variant_id' => $product->id,
                'source_provider' => 'aliexpress',
                'source_sku_id' => 'sku_'.$product->id,
                'acquisition_cost' => $cost,
                'source_currency' => 'USD',
                'captured_at' => now(),
            ]
        );

        return $product;
    }

    protected function createUatOrder(string $incrementId, Product $product, int $qty, array $additional = []): Order
    {
        $order = Order::firstOrCreate(
            ['increment_id' => $incrementId],
            [
                'status' => 'processing',
                'channel_name' => 'Default',
                'is_guest' => 0,
                'customer_email' => 'uat_buyer@test.local',
                'customer_first_name' => 'عميل',
                'customer_last_name' => 'تجريبي',
                'grand_total' => 100.0,
                'base_grand_total' => 100.0,
                'sub_total' => 100.0,
                'base_sub_total' => 100.0,
                'order_currency_code' => 'USD',
                'base_currency_code' => 'USD',
            ]
        );

        OrderPayment::firstOrCreate(
            ['order_id' => $order->id],
            [
                'method' => 'cashondelivery',
                'method_title' => 'الدفع عند الاستلام (COD)',
            ]
        );

        OrderItem::firstOrCreate(
            ['order_id' => $order->id, 'product_id' => $product->id],
            [
                'product_type' => Product::class,
                'name' => 'بند '.$product->sku,
                'sku' => $product->sku,
                'qty_ordered' => $qty,
                'price' => 20.0,
                'base_price' => 20.0,
                'total' => 20.0 * $qty,
                'base_total' => 20.0 * $qty,
                'additional' => $additional,
            ]
        );

        return $order;
    }
}
