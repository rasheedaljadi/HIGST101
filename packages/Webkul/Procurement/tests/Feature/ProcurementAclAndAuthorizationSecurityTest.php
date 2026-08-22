<?php

namespace Webkul\Procurement\Tests\Feature;

use App\Models\AliExpressProductImport;
use App\Models\HigestSourceOffer;
use DomainException;
use Tests\TestCase;
use Webkul\Inventory\Models\InventorySource;
use Webkul\Procurement\Models\ProcurementAuditLog;
use Webkul\Procurement\Models\ProcurementBatch;
use Webkul\Procurement\Models\ProcurementManualPaymentConfirmation;
use Webkul\Procurement\Services\ProcurementBatchService;
use Webkul\Procurement\Services\ProcurementDemandService;
use Webkul\Procurement\Services\ProcurementInboundReceiptService;
use Webkul\Procurement\Services\ProcurementManualPaymentService;
use Webkul\Procurement\Services\ProcurementSubmitService;
use Webkul\Procurement\Services\ProcurementVarianceApprovalService;
use Webkul\Product\Models\Product;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderItem;
use Webkul\Sales\Models\OrderPayment;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

class ProcurementAclAndAuthorizationSecurityTest extends TestCase
{
    protected InventorySource $saSource;

    protected InventorySource $yeSource;

    protected Role $viewOnlyRole;

    protected Role $batchCreateRole;

    protected Role $batchApproveRole;

    protected Role $submitRole;

    protected Role $paymentConfirmRole;

    protected Role $varianceApproveRole;

    protected Role $exceptionHandleRole;

    protected Role $costViewRole;

    protected Role $reportsViewRole;

    protected Admin $viewOnlyAdmin;

    protected Admin $fullAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        config(['procurement.v2_enabled' => true]);

        $this->saSource = InventorySource::firstOrCreate(
            ['code' => 'hayest_dropship_sa'],
            ['name' => 'Hayest Saudi Hub', 'country' => 'SA', 'is_salable' => 0, 'is_delivery_source' => 0, 'status' => 1]
        );

        $this->yeSource = InventorySource::firstOrCreate(
            ['code' => 'hayest_dropship_ye'],
            ['name' => 'Hayest Yemen Hub', 'country' => 'YE', 'is_salable' => 1, 'is_delivery_source' => 1, 'status' => 1]
        );

        // Define precise roles
        $this->viewOnlyRole = Role::firstOrCreate(
            ['name' => 'Procurement View Only'],
            ['permission_type' => 'custom', 'permissions' => ['dropshipping.procurement_v2.view']]
        );

        $this->batchCreateRole = Role::firstOrCreate(
            ['name' => 'Procurement Batch Creator'],
            ['permission_type' => 'custom', 'permissions' => ['dropshipping.procurement_v2.view', 'dropshipping.procurement_v2.batch_create']]
        );

        $this->batchApproveRole = Role::firstOrCreate(
            ['name' => 'Procurement Batch Approver'],
            ['permission_type' => 'custom', 'permissions' => ['dropshipping.procurement_v2.view', 'dropshipping.procurement_v2.batch_approve']]
        );

        $this->submitRole = Role::firstOrCreate(
            ['name' => 'Procurement Submitter'],
            ['permission_type' => 'custom', 'permissions' => ['dropshipping.procurement_v2.view', 'dropshipping.procurement_v2.submit']]
        );

        $this->paymentConfirmRole = Role::firstOrCreate(
            ['name' => 'Procurement Payer'],
            ['permission_type' => 'custom', 'permissions' => ['dropshipping.procurement_v2.view', 'dropshipping.procurement_v2.payment_confirm']]
        );

        $this->varianceApproveRole = Role::firstOrCreate(
            ['name' => 'Procurement Variance Manager'],
            ['permission_type' => 'custom', 'permissions' => ['dropshipping.procurement_v2.view', 'dropshipping.procurement_v2.variance_approve']]
        );

        $this->exceptionHandleRole = Role::firstOrCreate(
            ['name' => 'Procurement Exception Handler'],
            ['permission_type' => 'custom', 'permissions' => ['dropshipping.procurement_v2.view', 'dropshipping.procurement_v2.exception_handle']]
        );

        $this->costViewRole = Role::firstOrCreate(
            ['name' => 'Procurement Cost Viewer'],
            ['permission_type' => 'custom', 'permissions' => ['dropshipping.procurement_v2.view', 'dropshipping.procurement_v2.cost_view']]
        );

        $this->reportsViewRole = Role::firstOrCreate(
            ['name' => 'Procurement Reports Viewer'],
            ['permission_type' => 'custom', 'permissions' => ['dropshipping.procurement_v2.view', 'dropshipping.procurement_v2.reports_view']]
        );

        $superRole = Role::firstOrCreate(['name' => 'Procurement Super Admin'], ['permission_type' => 'all', 'permissions' => ['all']]);

        $this->viewOnlyAdmin = Admin::firstOrCreate(
            ['email' => 'view_only_admin@test.com'],
            ['name' => 'View Only Admin', 'password' => bcrypt('secret'), 'role_id' => $this->viewOnlyRole->id, 'status' => 1]
        );

        $this->fullAdmin = Admin::firstOrCreate(
            ['email' => 'full_proc_admin@test.com'],
            ['name' => 'Full Admin', 'password' => bcrypt('secret'), 'role_id' => $superRole->id, 'status' => 1]
        );
    }

    protected function createProductAndDemand(string $sku, string $storeId = 'store_acl_1'): array
    {
        $product = Product::create(['type' => 'simple', 'attribute_family_id' => 1, 'sku' => $sku]);

        AliExpressProductImport::create([
            'product_id' => $product->id,
            'aliexpress_product_id' => 'ae_prod_'.$product->id,
            'title' => 'Imported '.$sku,
            'status' => 'success',
            'payload_snapshot' => [
                'store_id' => $storeId,
                'store_name' => 'Store '.$storeId,
                'store_info' => ['store_id' => $storeId, 'store_name' => 'Store '.$storeId],
            ],
            'shipping_currency' => 'USD',
        ]);

        HigestSourceOffer::create([
            'product_id' => $product->id,
            'variant_id' => $product->id,
            'source_provider' => 'aliexpress',
            'source_sku_id' => 'ae_sku_'.$product->id,
            'acquisition_cost' => 20.0,
            'source_currency' => 'USD',
            'is_active' => 1,
        ]);

        $order = Order::create([
            'increment_id' => 'ORD-ACL-'.rand(1000, 9999),
            'status' => 'processing',
            'channel_name' => 'Default',
            'is_guest' => 0,
            'customer_email' => 'buyer@test.com',
            'customer_first_name' => 'A',
            'customer_last_name' => 'B',
            'grand_total' => 50.0,
            'base_grand_total' => 50.0,
            'sub_total' => 50.0,
            'base_sub_total' => 50.0,
            'order_currency_code' => 'USD',
            'base_currency_code' => 'USD',
        ]);

        OrderPayment::create(['order_id' => $order->id, 'method' => 'cashondelivery', 'method_title' => 'COD']);

        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_type' => Product::class,
            'name' => 'Item '.$sku,
            'sku' => $sku,
            'qty_ordered' => 2,
            'price' => 25.0,
            'base_price' => 25.0,
            'total' => 50.0,
            'base_total' => 50.0,
            'additional' => [],
        ]);

        $demandService = app(ProcurementDemandService::class);
        $demands = $demandService->processOrderDemands($order);

        return [$product, $order, $demands[0]];
    }

    /**
     * 1. View-only admin receives 403 on mutating endpoints, and zero state changes occur.
     */
    public function test_view_only_user_receives_403_on_mutating_actions_and_state_is_unchanged(): void
    {
        [$product, $order, $demand] = $this->createProductAndDemand('ACL-SKU-001', 'store_001');

        $initialBatchCount = ProcurementBatch::count();
        $initialAuditCount = ProcurementAuditLog::count();

        // 1. Attempt batch store with view-only admin
        $response = $this->actingAs($this->viewOnlyAdmin, 'admin')
            ->post(route('admin.procurement.batches.store'), [
                'demand_ids' => [$demand->id],
            ]);

        $response->assertStatus(403);
        $this->assertEquals($initialBatchCount, ProcurementBatch::count());
        $this->assertEquals($initialAuditCount, ProcurementAuditLog::count());

        // Create a batch as full admin to test approval rejection by view-only admin
        $batchService = app(ProcurementBatchService::class);
        $batch = $batchService->createBatch([$demand->id], $this->fullAdmin->id);

        $initialBatchState = $batch->state;

        // 2. Attempt approve batch with view-only admin
        $response = $this->actingAs($this->viewOnlyAdmin, 'admin')
            ->post(route('admin.procurement.batches.approve', $batch->id), ['notes' => 'Unauthorized approve']);
        $response->assertStatus(403);
        $this->assertEquals($initialBatchState, $batch->fresh()->state);

        // 3. Attempt reject batch with view-only admin
        $response = $this->actingAs($this->viewOnlyAdmin, 'admin')
            ->post(route('admin.procurement.batches.reject', $batch->id), ['reason' => 'Unauthorized reject']);
        $response->assertStatus(403);
        $this->assertEquals($initialBatchState, $batch->fresh()->state);

        // 4. Attempt submit batch with view-only admin
        $response = $this->actingAs($this->viewOnlyAdmin, 'admin')
            ->post(route('admin.procurement.batches.submit', $batch->id));
        $response->assertStatus(403);
        $this->assertEquals($initialBatchState, $batch->fresh()->state);

        // 5. Attempt manual payment declaration with view-only admin
        $spo = $batch->supplierOrders->first();
        $initialPaymentCount = ProcurementManualPaymentConfirmation::count();

        $response = $this->actingAs($this->viewOnlyAdmin, 'admin')
            ->post(route('admin.procurement.manual_payments.store'), [
                'supplier_purchase_order_id' => $spo->id,
                'external_reference' => 'UNAUTH-PAY-REF',
                'declared_total' => 40.0,
                'currency' => 'USD',
            ]);
        $response->assertStatus(403);
        $this->assertEquals($initialPaymentCount, ProcurementManualPaymentConfirmation::count());
    }

    /**
     * 2. User with precise permission succeeds on authorized action.
     */
    public function test_user_with_precise_permission_succeeds(): void
    {
        [$product, $order, $demand] = $this->createProductAndDemand('ACL-SKU-002', 'store_002');

        $batchCreator = Admin::create([
            'name' => 'Creator Admin',
            'email' => 'creator_admin@test.com',
            'password' => bcrypt('secret'),
            'role_id' => $this->batchCreateRole->id,
            'status' => 1,
        ]);

        $response = $this->actingAs($batchCreator, 'admin')
            ->post(route('admin.procurement.batches.store'), [
                'demand_ids' => [$demand->id],
            ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('procurement_batches', ['created_by' => $batchCreator->id]);
    }

    /**
     * 3. Cost view permission masks financial data on reports for non-permitted users.
     */
    public function test_reports_hide_financial_metrics_without_cost_view_permission(): void
    {
        $reportsViewerAdmin = Admin::create([
            'name' => 'Reports Viewer',
            'email' => 'reports_viewer@test.com',
            'password' => bcrypt('secret'),
            'role_id' => $this->reportsViewRole->id,
            'status' => 1,
        ]);

        $response = $this->actingAs($reportsViewerAdmin, 'admin')
            ->get(route('admin.procurement.reports.index'));

        $response->assertStatus(200);
        $response->assertViewHas('metrics', function (array $metrics) {
            return $metrics['total_expected_cost'] === null
                && $metrics['total_actual_cost'] === null
                && $metrics['total_cost_variance'] === null
                && $metrics['uncollected_cod_total'] === null
                && $metrics['cost_view_permitted'] === false;
        });

        // With cost_view permission
        $costViewerAdmin = Admin::create([
            'name' => 'Cost Viewer Admin',
            'email' => 'cost_viewer@test.com',
            'password' => bcrypt('secret'),
            'role_id' => Role::create([
                'name' => 'Combined Reports & Cost',
                'permission_type' => 'custom',
                'permissions' => ['dropshipping.procurement_v2.view', 'dropshipping.procurement_v2.reports_view', 'dropshipping.procurement_v2.cost_view'],
            ])->id,
            'status' => 1,
        ]);

        $responseCost = $this->actingAs($costViewerAdmin, 'admin')
            ->get(route('admin.procurement.reports.index'));

        $responseCost->assertStatus(200);
        $responseCost->assertViewHas('metrics', function (array $metrics) {
            return $metrics['cost_view_permitted'] === true
                && is_numeric($metrics['total_expected_cost']);
        });
    }

    /**
     * 4. Domain services enforce actor authorization when called directly.
     */
    public function test_domain_services_throw_exception_when_actor_lacks_permission(): void
    {
        [$product, $order, $demand] = $this->createProductAndDemand('ACL-SKU-003', 'store_003');

        $batchService = app(ProcurementBatchService::class);
        $submitService = app(ProcurementSubmitService::class);
        $paymentService = app(ProcurementManualPaymentService::class);
        $varianceService = app(ProcurementVarianceApprovalService::class);
        $receiptService = app(ProcurementInboundReceiptService::class);

        // 1. Missing actor for approveBatch throws DomainException
        $batch = $batchService->createBatch([$demand->id], $this->fullAdmin->id);

        $this->expectException(DomainException::class);
        $batchService->approveBatch($batch->id, $this->viewOnlyAdmin->id);
    }
}
