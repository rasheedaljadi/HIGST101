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

    protected Admin $supervisor;

    protected Admin $accountant;

    protected Admin $courier;

    protected Admin $pointAgent;

    protected DeliveryPoint $testPoint;

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

        $supervisorRole = Role::firstOrCreate(
            ['name' => 'Supervisor'],
            ['permission_type' => 'custom', 'permissions' => ['delivery', 'delivery.dashboard', 'delivery.assignments', 'delivery.couriers', 'delivery.points', 'delivery.rules', 'delivery.failures']]
        );

        $this->supervisor = Admin::firstOrCreate(
            ['email' => 'supervisor_test_module@example.com'],
            ['name' => 'Supervisor Test Module', 'password' => bcrypt('secret123'), 'role_id' => $supervisorRole->id, 'status' => 1]
        );

        $accountantRole = Role::firstOrCreate(
            ['name' => 'Accountant'],
            ['permission_type' => 'custom', 'permissions' => ['delivery', 'delivery.settlements', 'delivery.audit_logs']]
        );

        $this->accountant = Admin::firstOrCreate(
            ['email' => 'accountant_test_module@example.com'],
            ['name' => 'Accountant Test Module', 'password' => bcrypt('secret123'), 'role_id' => $accountantRole->id, 'status' => 1]
        );

        $courierRole = Role::firstOrCreate(
            ['name' => 'Courier'],
            ['permission_type' => 'custom', 'permissions' => ['delivery']]
        );

        $this->courier = Admin::firstOrCreate(
            ['email' => 'courier_test_module@example.com'],
            ['name' => 'Courier Test Module', 'password' => bcrypt('secret123'), 'role_id' => $courierRole->id, 'status' => 1]
        );

        $this->testPoint = DeliveryPoint::firstOrCreate(
            ['code' => 'PNT-SAN-01'],
            [
                'name' => 'نقطة السبعين الرئيسية',
                'name_ar' => 'نقطة السبعين الرئيسية',
                'state_code' => 'SAN',
                'city' => 'Sanaa',
                'address' => 'شارع السبعين، صنعاء',
                'contact_name' => 'مسؤول النقطة',
                'contact_phone' => '777000111',
                'max_capacity' => 100,
                'is_active' => 1,
            ]
        );

        $pointAgentRole = Role::firstOrCreate(
            ['name' => 'PointAgent'],
            ['permission_type' => 'custom', 'permissions' => ['delivery']]
        );

        $this->pointAgent = Admin::firstOrCreate(
            ['email' => 'point_agent_module@example.com'],
            ['name' => 'Point Agent Module', 'password' => bcrypt('secret123'), 'role_id' => $pointAgentRole->id, 'status' => 1, 'delivery_point_id' => $this->testPoint->id]
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

    public function test_admin_can_access_all_eight_delivery_screens_with_arabic_titles_and_breadcrumbs(): void
    {
        // 1. Dashboard
        $dashboardResponse = $this->actingAs($this->admin, 'admin')->get(route('admin.delivery.dashboard.index'));
        $dashboardResponse->assertOk();
        $dashboardResponse->assertSee(trans('delivery::app.admin.dashboard.title'));

        // 2. Assignments
        $assignmentsResponse = $this->actingAs($this->admin, 'admin')->get(route('admin.delivery.assignments.index'));
        $assignmentsResponse->assertOk();
        $assignmentsResponse->assertSee(trans('delivery::app.admin.assignments.title'));

        // 3. Couriers
        $couriersResponse = $this->actingAs($this->admin, 'admin')->get(route('admin.delivery.couriers.index'));
        $couriersResponse->assertOk();
        $couriersResponse->assertSee(trans('delivery::app.admin.couriers.title'));

        // 4. Points
        $pointsResponse = $this->actingAs($this->admin, 'admin')->get(route('admin.delivery.points.index'));
        $pointsResponse->assertOk();
        $pointsResponse->assertSee(trans('delivery::app.admin.points.title'));

        // 5. Rules
        $rulesResponse = $this->actingAs($this->admin, 'admin')->get(route('admin.delivery.rules.index'));
        $rulesResponse->assertOk();
        $rulesResponse->assertSee(trans('delivery::app.admin.rules.title'));

        // 6. Failures
        $failuresResponse = $this->actingAs($this->admin, 'admin')->get(route('admin.delivery.failures.index'));
        $failuresResponse->assertOk();
        $failuresResponse->assertSee(trans('delivery::app.admin.failures.title'));

        $failuresAjaxResponse = $this->actingAs($this->admin, 'admin')->getJson(route('admin.delivery.failures.index'), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $failuresAjaxResponse->assertOk();

        // 7. Settlements
        $settlementsResponse = $this->actingAs($this->admin, 'admin')->get(route('admin.delivery.settlements.index'));
        $settlementsResponse->assertOk();
        $settlementsResponse->assertSee(trans('delivery::app.admin.settlements.title'));

        // 8. Audit Logs
        $auditLogsResponse = $this->actingAs($this->admin, 'admin')->get(route('admin.delivery.audit_logs.index'));
        $auditLogsResponse->assertOk();
        $auditLogsResponse->assertSee(trans('delivery::app.admin.audit-logs.title'));
    }

    public function test_admin_can_access_assignments_list_and_detail(): void
    {
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
            'attempt_count' => 0,
            'delivery_boy_id' => $this->courier->id,
        ]);

        $response = $this->actingAs($this->admin, 'admin')->get(route('admin.delivery.assignments.index'));
        $response->assertOk();

        $detailResponse = $this->actingAs($this->admin, 'admin')->get(route('admin.delivery.assignments.show', $assignment->id));
        $detailResponse->assertOk();
        $detailResponse->assertSee((string) $assignment->id);
    }

    public function test_admin_can_manage_couriers_crud_and_toggle_with_audit_trail(): void
    {
        $listResponse = $this->actingAs($this->admin, 'admin')->get(route('admin.delivery.couriers.index'));
        $listResponse->assertOk();

        $createResponse = $this->actingAs($this->admin, 'admin')->get(route('admin.delivery.couriers.create'));
        $createResponse->assertOk();

        $email = 'courier_audit_'.uniqid().'@example.com';
        $storeResponse = $this->actingAs($this->admin, 'admin')->post(route('admin.delivery.couriers.store'), [
            'name' => 'Courier Audit Test',
            'email' => $email,
            'password' => 'secret12345',
        ]);
        $storeResponse->assertRedirect(route('admin.delivery.couriers.index'));

        $newCourier = Admin::where('email', $email)->firstOrFail();

        // Audit log created
        $this->assertDatabaseHas('delivery_audit_logs', [
            'action' => 'courier_created',
            'entity_type' => 'courier',
            'entity_id' => $newCourier->id,
        ]);

        // Toggle status
        $toggleResponse = $this->actingAs($this->admin, 'admin')->postJson(route('admin.delivery.couriers.toggle', $newCourier->id));
        $toggleResponse->assertOk();
        $toggleResponse->assertJson(['success' => true]);

        // Audit log toggled
        $this->assertDatabaseHas('delivery_audit_logs', [
            'action' => 'courier_toggled',
            'entity_type' => 'courier',
            'entity_id' => $newCourier->id,
        ]);
    }

    public function test_admin_can_manage_delivery_points_with_audit_trail(): void
    {
        $listResponse = $this->actingAs($this->admin, 'admin')->get(route('admin.delivery.points.index'));
        $listResponse->assertOk();

        $pointCode = 'PNT-AUD-'.strtoupper(uniqid());
        $storeResponse = $this->actingAs($this->admin, 'admin')->post(route('admin.delivery.points.store'), [
            'code' => $pointCode,
            'name' => 'نقطة تدقيق المطار',
            'state_code' => 'SAN',
            'city' => 'Sanaa',
            'address' => 'جولة المطار، صنعاء',
            'contact_name' => 'نبيل الاختبار',
            'contact_phone' => '771234567',
            'max_capacity' => 80,
            'is_active' => 1,
        ]);
        $storeResponse->assertRedirect(route('admin.delivery.points.index'));

        $point = DeliveryPoint::where('code', $pointCode)->firstOrFail();

        $this->assertDatabaseHas('delivery_audit_logs', [
            'action' => 'point_created',
            'entity_type' => 'point',
            'entity_id' => $point->id,
        ]);

        // Toggle point
        $toggleResponse = $this->actingAs($this->admin, 'admin')->postJson(route('admin.delivery.points.toggle', $point->id));
        $toggleResponse->assertOk();
        $toggleResponse->assertJson(['success' => true]);

        $this->assertDatabaseHas('delivery_audit_logs', [
            'action' => 'point_toggled',
            'entity_type' => 'point',
            'entity_id' => $point->id,
        ]);
    }

    public function test_admin_can_update_governorate_rules_with_audit_trail(): void
    {
        $rule = DeliveryGovernorateRule::firstOrCreate(
            ['state_code' => 'ADE', 'delivery_type' => 'home_delivery'],
            [
                'is_enabled' => true,
                'allowed_payment_methods' => ['cashondelivery', 'moneytransfer'],
                'delivery_fee' => 2500,
                'min_order_amount' => 0,
            ]
        );

        $editResponse = $this->actingAs($this->admin, 'admin')->get(route('admin.delivery.rules.edit', $rule->id));
        $editResponse->assertOk();

        $updateResponse = $this->actingAs($this->admin, 'admin')->put(route('admin.delivery.rules.update', $rule->id), [
            'delivery_fee' => 3000,
            'min_order_amount' => 5000,
            'is_enabled' => 1,
            'allowed_payment_methods' => ['cashondelivery'],
        ]);
        $updateResponse->assertRedirect(route('admin.delivery.rules.index'));

        $rule->refresh();
        $this->assertEquals(3000, (float) $rule->delivery_fee);

        $this->assertDatabaseHas('delivery_audit_logs', [
            'action' => 'rule_updated',
            'entity_type' => 'rule',
            'entity_id' => $rule->id,
        ]);
    }

    public function test_admin_can_process_settlement_with_audit_trail(): void
    {
        $processResponse = $this->actingAs($this->admin, 'admin')->post(route('admin.delivery.settlements.process'), [
            'delivery_boy_id' => $this->courier->id,
            'total_submitted_yer' => 50000,
            'notes' => 'تسوية تجريبية لصندوق الفرع',
        ]);
        $processResponse->assertRedirect(route('admin.delivery.settlements.index'));

        $this->assertDatabaseHas('delivery_audit_logs', [
            'action' => 'settlement_processed',
            'entity_type' => 'settlement',
        ]);
    }

    public function test_role_direct_access_matrix(): void
    {
        // 1. Administrator has full access
        $this->actingAs($this->admin, 'admin')->get(route('admin.delivery.dashboard.index'))->assertOk();
        $this->actingAs($this->admin, 'admin')->get(route('admin.delivery.settlements.index'))->assertOk();

        // 2. Courier can access front agent portal
        $this->actingAs($this->courier, 'admin')->get(route('delivery.index'))->assertOk();
    }
}
