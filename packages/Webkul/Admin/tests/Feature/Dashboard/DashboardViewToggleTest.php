<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Currency;
use Webkul\Core\Models\Locale;
use Webkul\User\Models\Role;

beforeEach(function () {
    if (Schema::hasTable('admins') && ! Schema::hasColumn('admins', 'dashboard_view')) {
        Schema::table('admins', function (Blueprint $table) {
            $table->string('dashboard_view')->default('simple');
        });
    }

    if (Schema::hasTable('categories')) {
        DB::table('categories')->insertOrIgnore([
            'id' => 1,
            'position' => 1,
            'status' => 1,
            '_lft' => 1,
            '_rgt' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        if (Schema::hasTable('category_translations')) {
            DB::table('category_translations')->insertOrIgnore([
                'category_id' => 1,
                'locale' => 'ar',
                'name' => 'Root Category',
                'slug' => 'root-category',
            ]);
        }
    }

    $locale = Locale::first();
    if (! $locale) {
        $locale = Locale::factory()->create(['code' => 'ar', 'direction' => 'rtl']);
    }

    $currency = Currency::first();
    if (! $currency) {
        $currency = Currency::factory()->create(['code' => 'USD']);
    }

    if (! Role::find(1)) {
        Role::factory()->create([
            'id' => 1,
            'name' => 'Administrator',
            'permission_type' => 'all',
        ]);
    }

    if (! Channel::first()) {
        Channel::factory()->create([
            'root_category_id' => 1,
            'default_locale_id' => $locale->id,
            'base_currency_id' => $currency->id,
        ]);
    }
});

it('defaults to simple dashboard view for authenticated admin users', function () {
    $admin = $this->loginAsAdmin();

    $response = $this->get(route('admin.dashboard.index'));

    $response->assertStatus(200)
        ->assertSeeText('بسيط (Simple)')
        ->assertSeeText('متقدم (Advanced)')
        ->assertSeeText(trans('admin::app.dashboard.index.overall-details'));
});

it('allows switching to advanced dashboard view and persists user preference in database', function () {
    $admin = $this->loginAsAdmin();

    $response = $this->post(
        route('admin.dashboard.toggle_view'),
        ['view' => 'advanced'],
        ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json']
    );

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'view_mode' => 'advanced',
        ]);

    expect($admin->fresh()->dashboard_view)->toBe('advanced');

    // Fetch dashboard index and verify Advanced View content
    $indexResponse = $this->get(route('admin.dashboard.index'));
    $indexResponse->assertStatus(200)
        ->assertSeeText('لوحة هايست المتقدمة الشاملة')
        ->assertSeeText('مسار دورة حياة الطلبات والتوريد (Order Lifecycle Pipeline)')
        ->assertSeeText('(Legacy / External)')
        ->assertSeeText('(Virtual Projection)');
});

it('allows switching back to simple dashboard view', function () {
    $admin = $this->loginAsAdmin();

    $response = $this->post(
        route('admin.dashboard.toggle_view'),
        ['view' => 'simple'],
        ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json']
    );

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'view_mode' => 'simple',
        ]);

    expect($admin->fresh()->dashboard_view)->toBe('simple');
});
