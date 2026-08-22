<?php

use Webkul\Core\Menu;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

beforeEach(function () {
    config(['procurement.v2_enabled' => true]);
});

afterEach(function () {
    config(['procurement.v2_enabled' => false]);
});

test('procurement v2 is registered as an independent top-level admin menu module with correct localization', function () {
    app()->setLocale('ar');

    $menu = new Menu;
    $admin = Admin::first();
    auth()->guard('admin')->setUser($admin);

    $items = $menu->getItems('admin');

    // 1. Must be a top-level parent (not a child of dropshipping)
    $procV2 = $items->firstWhere('key', 'procurement_v2');

    expect($procV2)->not->toBeNull()
        ->and($procV2->getName())->toBe('إدارة الشراء')
        ->and($procV2->getUrl())->toBe(route('admin.procurement.demands.index'))
        ->and($procV2->getIcon())->toBe('icon-cart');

    // 2. Dropshipping must exist separately and must NOT contain procurement_v2
    $dropshipping = $items->firstWhere('key', 'dropshipping');
    expect($dropshipping)->not->toBeNull();
    $procInsideDropship = $dropshipping->getChildren()->firstWhere('key', 'dropshipping.procurement_v2');
    expect($procInsideDropship)->toBeNull();

    // 3. Switch to English and test translation
    app()->setLocale('en');
    $menuEn = new Menu;
    $itemsEn = $menuEn->getItems('admin');
    $procV2En = $itemsEn->firstWhere('key', 'procurement_v2');

    expect($procV2En)->not->toBeNull()
        ->and($procV2En->getName())->toBe('Purchase Management');
});

test('procurement v2 children are registered as level 2 items with valid routes', function () {
    app()->setLocale('ar');

    $menu = new Menu;
    $admin = Admin::first();
    auth()->guard('admin')->setUser($admin);

    $procV2 = $menu->getItems('admin')->firstWhere('key', 'procurement_v2');
    expect($procV2)->not->toBeNull();

    $children = $procV2->getChildren();
    $childKeys = $children->pluck('key')->all();

    // 8 valid real routes
    expect($childKeys)->toBe([
        'procurement_v2.demands',
        'procurement_v2.batches',
        'procurement_v2.supplier_orders',
        'procurement_v2.platform_orders',
        'procurement_v2.manual_payments',
        'procurement_v2.cost_variances',
        'procurement_v2.exceptions',
        'procurement_v2.reports',
    ]);

    // Ensure all routes resolve to valid URLs
    expect($children->firstWhere('key', 'procurement_v2.demands')->getUrl())->toBe(route('admin.procurement.demands.index'));
    expect($children->firstWhere('key', 'procurement_v2.batches')->getUrl())->toBe(route('admin.procurement.batches.index'));
    expect($children->firstWhere('key', 'procurement_v2.supplier_orders')->getUrl())->toBe(route('admin.procurement.supplier_orders.index'));
    expect($children->firstWhere('key', 'procurement_v2.platform_orders')->getUrl())->toBe(route('admin.procurement.platform_orders.index'));
    expect($children->firstWhere('key', 'procurement_v2.manual_payments')->getUrl())->toBe(route('admin.procurement.manual_payments.index'));
    expect($children->firstWhere('key', 'procurement_v2.cost_variances')->getUrl())->toBe(route('admin.procurement.cost_variances.index'));
    expect($children->firstWhere('key', 'procurement_v2.exceptions')->getUrl())->toBe(route('admin.procurement.exceptions.index'));
    expect($children->firstWhere('key', 'procurement_v2.reports')->getUrl())->toBe(route('admin.procurement.reports.index'));
});

test('procurement v2 submenus are strictly filtered based on role acl permissions', function () {
    app()->setLocale('ar');

    // 1. Viewer Role (view only)
    $viewerRole = Role::create([
        'name' => 'Test Nav Viewer Role '.uniqid(),
        'permission_type' => 'custom',
        'permissions' => ['dropshipping.procurement_v2.view'],
    ]);
    $viewer = Admin::factory()->create(['role_id' => $viewerRole->id]);

    auth()->guard('admin')->setUser($viewer);
    $viewerMenu = new Menu;
    $procItem = $viewerMenu->getItems('admin')->firstWhere('key', 'procurement_v2');

    expect($procItem)->not->toBeNull();
    $subKeys = $procItem->getChildren()->pluck('key')->all();

    expect($subKeys)->toContain('procurement_v2.demands')
        ->and($subKeys)->toContain('procurement_v2.batches')
        ->and($subKeys)->toContain('procurement_v2.supplier_orders')
        ->and($subKeys)->toContain('procurement_v2.platform_orders')
        ->and($subKeys)->not->toContain('procurement_v2.manual_payments')
        ->and($subKeys)->not->toContain('procurement_v2.cost_variances')
        ->and($subKeys)->not->toContain('procurement_v2.exceptions')
        ->and($subKeys)->not->toContain('procurement_v2.reports');

    // 2. Approver Role (view, cost_view, batch_approve, variance_approve)
    $approverRole = Role::create([
        'name' => 'Test Nav Approver Role '.uniqid(),
        'permission_type' => 'custom',
        'permissions' => [
            'dropshipping.procurement_v2.view',
            'dropshipping.procurement_v2.cost_view',
            'dropshipping.procurement_v2.batch_approve',
            'dropshipping.procurement_v2.variance_approve',
        ],
    ]);
    $approver = Admin::factory()->create(['role_id' => $approverRole->id]);

    auth()->guard('admin')->setUser($approver);
    $appMenu = new Menu;
    $appProcItem = $appMenu->getItems('admin')->firstWhere('key', 'procurement_v2');

    $appSubKeys = $appProcItem->getChildren()->pluck('key')->all();
    expect($appSubKeys)->toContain('procurement_v2.cost_variances')
        ->and($appSubKeys)->not->toContain('procurement_v2.manual_payments')
        ->and($appSubKeys)->not->toContain('procurement_v2.exceptions')
        ->and($appSubKeys)->not->toContain('procurement_v2.reports');

    // 3. Finance Role (view, cost_view, payment_confirm, reports_view)
    $financeRole = Role::create([
        'name' => 'Test Nav Finance Role '.uniqid(),
        'permission_type' => 'custom',
        'permissions' => [
            'dropshipping.procurement_v2.view',
            'dropshipping.procurement_v2.cost_view',
            'dropshipping.procurement_v2.payment_confirm',
            'dropshipping.procurement_v2.reports_view',
        ],
    ]);
    $finance = Admin::factory()->create(['role_id' => $financeRole->id]);

    auth()->guard('admin')->setUser($finance);
    $finMenu = new Menu;
    $finProcItem = $finMenu->getItems('admin')->firstWhere('key', 'procurement_v2');

    $finSubKeys = $finProcItem->getChildren()->pluck('key')->all();
    expect($finSubKeys)->toContain('procurement_v2.manual_payments')
        ->and($finSubKeys)->toContain('procurement_v2.cost_variances')
        ->and($finSubKeys)->toContain('procurement_v2.reports')
        ->and($finSubKeys)->not->toContain('procurement_v2.exceptions');

    // 4. Receiver Role (view, exception_handle)
    $recRole = Role::create([
        'name' => 'Test Nav Receiver Role '.uniqid(),
        'permission_type' => 'custom',
        'permissions' => [
            'dropshipping.procurement_v2.view',
            'dropshipping.procurement_v2.exception_handle',
        ],
    ]);
    $receiver = Admin::factory()->create(['role_id' => $recRole->id]);

    auth()->guard('admin')->setUser($receiver);
    $recMenu = new Menu;
    $recProcItem = $recMenu->getItems('admin')->firstWhere('key', 'procurement_v2');

    $recSubKeys = $recProcItem->getChildren()->pluck('key')->all();
    expect($recSubKeys)->toContain('procurement_v2.exceptions')
        ->and($recSubKeys)->not->toContain('procurement_v2.manual_payments')
        ->and($recSubKeys)->not->toContain('procurement_v2.cost_variances')
        ->and($recSubKeys)->not->toContain('procurement_v2.reports');
});

test('unauthorized admin without procurement permissions cannot see procurement top level menu', function () {
    $salesRole = Role::create([
        'name' => 'Test Nav Sales Role '.uniqid(),
        'permission_type' => 'custom',
        'permissions' => ['sales', 'sales.orders'],
    ]);
    $salesUser = Admin::factory()->create(['role_id' => $salesRole->id]);

    auth()->guard('admin')->setUser($salesUser);
    $menu = new Menu;
    $items = $menu->getItems('admin');

    $procV2 = $items->firstWhere('key', 'procurement_v2');
    expect($procV2)->toBeNull();
});

test('core navigation modules are unaffected and remain at top level', function () {
    $admin = Admin::first();
    auth()->guard('admin')->setUser($admin);

    $menu = new Menu;
    $items = $menu->getItems('admin');
    $keys = $items->pluck('key')->all();

    expect($keys)->toContain('dashboard')
        ->and($keys)->toContain('sales')
        ->and($keys)->toContain('catalog')
        ->and($keys)->toContain('customers')
        ->and($keys)->toContain('inventory')
        ->and($keys)->toContain('delivery_management')
        ->and($keys)->toContain('dropshipping')
        ->and($keys)->toContain('procurement_v2');
});
