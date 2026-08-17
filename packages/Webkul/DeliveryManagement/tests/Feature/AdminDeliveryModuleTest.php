<?php

namespace Webkul\DeliveryManagement\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Webkul\DeliveryManagement\Models\DeliveryAssignment;
use Webkul\DeliveryManagement\Models\DeliveryGovernorateRule;
use Webkul\DeliveryManagement\Models\DeliveryPoint;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

class AdminDeliveryModuleTest extends TestCase
{
    protected Admin $admin;

    protected Admin $courier;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(
            ['name' => 'Administrator'],
            ['permission_type' => 'all', 'permissions' => ['all']]
        );

        $this->admin = Admin::firstOrCreate(
            ['email' => 'admin_test_module@example.com'],
            ['name' => 'Admin Test Module', 'password' => bcrypt('secret123'), 'role_id' => $adminRole->id, 'status' => 1]
        );

        $courierRole = Role::firstOrCreate(
            ['name' => 'Courier'],
            ['permission_type' => 'custom', 'permissions' => ['delivery']]
        );

        $this->courier = Admin::firstOrCreate(
            ['email' => 'courier_test_module@example.com'],
            ['name' => 'Courier Test Module', 'password' => bcrypt('secret123'), 'role_id' => $courierRole->id, 'status' => 1]
        );
    }

    public function test_guest_is_redirected_from_all_delivery_admin_routes(): void
    {
        $routes = [
            route('admin.delivery.dashboard.index'),
            route('admin.delivery.assignments.index'),
            route('admin.delivery.couriers.index'),
            route('admin.delivery.points.index'),
            route('admin.delivery.rules.index'),
            route('admin.delivery.failures.index'),
            route('admin.delivery.settlements.index'),
            route('admin.delivery.audit_logs.index'),
        ];

        foreach ($routes as $url) {
            $response = $this->get($url);
            $response->assertRedirect(route('admin.session.create'));
        }
    }

    public function test_admin_can_access_delivery_dashboard_with_kpis(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->get(route('admin.delivery.dashboard.index'));
        $response->assertOk();
        $response->assertSee('لوحة متابعة عمليات التسليم');
        $response->assertSee('جاهز للإسناد');
    }

    public function test_admin_can_access_assignments_list_and_detail(): void
    {
        // Create sample order and assignment
        $orderId = DB::table('orders')->insertGetId([
            'status' => 'processing',
            'grand_total' => 15000,
            'base_grand_total' => 15000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $assignment = DeliveryAssignment::create([
            'order_id' => $orderId,
            'delivery_type' => 'home_delivery',
            'state_code' => 'SAN',
            'payment_method' => 'cashondelivery',
            'status' => 'ready_for_assignment',
            'cod_amount_yer' => 15000,
            'attempt_count' => 0,
            'delivery_boy_id' => $this->courier->id,
        ]);

        $response = $this->actingAs($this->admin, 'admin')->get(route('admin.delivery.assignments.index'));
        $response->assertOk();

        $detailResponse = $this->actingAs($this->admin, 'admin')->get(route('admin.delivery.assignments.show', $assignment->id));
        $detailResponse->assertOk();
        $detailResponse->assertSee((string) $assignment->id);
    }

    public function test_admin_can_manage_couriers_crud_and_toggle(): void
    {
        $listResponse = $this->actingAs($this->admin, 'admin')->get(route('admin.delivery.couriers.index'));
        $listResponse->assertOk();

        $createResponse = $this->actingAs($this->admin, 'admin')->get(route('admin.delivery.couriers.create'));
        $createResponse->assertOk();

        // Store new courier
        $storeResponse = $this->actingAs($this->admin, 'admin')->post(route('admin.delivery.couriers.store'), [
            'name' => 'New Test Courier',
            'email' => 'new_test_courier_'.uniqid().'@example.com',
            'password' => 'secret12345',
        ]);
        $storeResponse->assertRedirect(route('admin.delivery.couriers.index'));

        // Toggle status
        $toggleResponse = $this->actingAs($this->admin, 'admin')->postJson(route('admin.delivery.couriers.toggle', $this->courier->id));
        $toggleResponse->assertOk();
        $toggleResponse->assertJson(['success' => true]);
    }

    public function test_admin_can_manage_delivery_points(): void
    {
        $listResponse = $this->actingAs($this->admin, 'admin')->get(route('admin.delivery.points.index'));
        $listResponse->assertOk();

        // Create point
        $pointCode = 'PNT-TEST-'.strtoupper(uniqid());
        $storeResponse = $this->actingAs($this->admin, 'admin')->post(route('admin.delivery.points.store'), [
            'code' => $pointCode,
            'name' => 'نقطة اختبار السبعين',
            'state_code' => 'SAN',
            'city' => 'Sanaa',
            'address' => 'شارع السبعين، صنعاء',
            'contact_name' => 'علي الاختبار',
            'contact_phone' => '770000000',
            'max_capacity' => 50,
            'is_active' => 1,
        ]);
        $storeResponse->assertRedirect(route('admin.delivery.points.index'));

        $point = DeliveryPoint::where('code', $pointCode)->firstOrFail();

        // Toggle point
        $toggleResponse = $this->actingAs($this->admin, 'admin')->postJson(route('admin.delivery.points.toggle', $point->id));
        $toggleResponse->assertOk();
        $toggleResponse->assertJson(['success' => true]);
    }

    public function test_admin_can_update_governorate_rules_with_audit_trail(): void
    {
        $rule = DeliveryGovernorateRule::firstOrCreate(
            ['state_code' => 'SAN', 'delivery_type' => 'home_delivery'],
            [
                'is_enabled' => true,
                'allowed_payment_methods' => ['cashondelivery', 'moneytransfer'],
                'delivery_fee' => 1500,
                'min_order_amount' => 0,
            ]
        );

        $editResponse = $this->actingAs($this->admin, 'admin')->get(route('admin.delivery.rules.edit', $rule->id));
        $editResponse->assertOk();

        $updateResponse = $this->actingAs($this->admin, 'admin')->put(route('admin.delivery.rules.update', $rule->id), [
            'delivery_fee' => 2000,
            'min_order_amount' => 0,
            'is_enabled' => 1,
            'allowed_payment_methods' => ['cashondelivery', 'moneytransfer'],
        ]);
        $updateResponse->assertRedirect(route('admin.delivery.rules.index'));

        $rule->refresh();
        $this->assertEquals(2000, (float) $rule->delivery_fee);

        // Verify audit log entry
        $this->assertDatabaseHas('delivery_audit_logs', [
            'action' => 'rule_updated',
            'entity_type' => 'rule',
            'entity_id' => $rule->id,
        ]);
    }

    public function test_admin_can_view_failures_settlements_and_audit_logs(): void
    {
        $failuresResponse = $this->actingAs($this->admin, 'admin')->get(route('admin.delivery.failures.index'));
        $failuresResponse->assertOk();

        $settlementsResponse = $this->actingAs($this->admin, 'admin')->get(route('admin.delivery.settlements.index'));
        $settlementsResponse->assertOk();

        $auditLogsResponse = $this->actingAs($this->admin, 'admin')->get(route('admin.delivery.audit_logs.index'));
        $auditLogsResponse->assertOk();
    }
}
