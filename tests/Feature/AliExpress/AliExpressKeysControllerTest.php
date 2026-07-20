<?php

use App\Models\AliExpressSetting;
use Tests\TestCase;
use Webkul\Admin\Tests\Concerns\AdminTestBench;
use Illuminate\Support\Facades\DB;

uses(TestCase::class, AdminTestBench::class);

beforeEach(function () {
    // Ensure default warehouse exists
    DB::table('inventory_sources')->updateOrInsert(
        ['code' => 'default'],
        [
            'name' => 'Default Warehouse',
            'contact_name' => 'John Doe',
            'contact_number' => '123456',
            'contact_email' => 'john@example.com',
            'street' => '123 Main St',
            'city' => 'Riyadh',
            'state' => 'Riyadh',
            'country' => 'SA',
            'postcode' => '12345',
            'status' => 1,
        ]
    );
});

test('admin can save keys section only', function () {
    $this->loginAsAdmin();

    $response = $this->post(route('admin.dropshipping.keys.store'), [
        'section' => 'keys',
        'app_key' => 'new-app-key',
        'app_secret' => 'new-app-secret',
        'authorize_url' => 'https://example.com/auth',
    ]);

    $response->assertRedirect(route('admin.dropshipping.keys.index'));
    $response->assertSessionHas('success', 'تم حفظ مفاتيح التطبيق وعناوين الاتصال بنجاح.');

    $settings = AliExpressSetting::current();
    expect($settings->app_key)->toBe('new-app-key');
    expect($settings->app_secret)->toBe('new-app-secret');
    expect($settings->authorize_url)->toBe('https://example.com/auth');
});

test('admin can save sync section only', function () {
    $this->loginAsAdmin();

    $response = $this->post(route('admin.dropshipping.keys.store'), [
        'section' => 'sync',
        'sync_enabled' => '1',
        'sync_schedule' => 'hourly',
    ]);

    $response->assertRedirect(route('admin.dropshipping.keys.index'));
    $response->assertSessionHas('success', 'تم حفظ إعدادات المزامنة المجدولة بنجاح.');

    $settings = AliExpressSetting::current();
    expect($settings->sync_enabled)->toBeTrue();
    expect($settings->sync_schedule)->toBe('hourly');
});

test('admin can save shipping section only', function () {
    $this->loginAsAdmin();

    $response = $this->post(route('admin.dropshipping.keys.store'), [
        'section' => 'shipping',
        'shipping_margin' => '12.50',
        'shipping_extra_days' => '5',
        'shipping_enabled' => '1',
    ]);

    $response->assertRedirect(route('admin.dropshipping.keys.index'));
    $response->assertSessionHas('success', 'تم حفظ خيارات الشحن بنجاح.');

    $settings = AliExpressSetting::current();
    expect((float)$settings->shipping_margin)->toBe(12.50);
    expect($settings->shipping_extra_days)->toBe(5);
    expect($settings->shipping_enabled)->toBeTrue();
});

test('admin can save warehouse section only', function () {
    $this->loginAsAdmin();

    $response = $this->post(route('admin.dropshipping.keys.store'), [
        'section' => 'warehouse',
        'warehouse_contact_name'   => 'Mostafa Mohammed',
        'warehouse_contact_number' => '0572124578',
        'warehouse_contact_email'  => 'mostafa@example.com',
        'warehouse_street'         => 'Southern Ring Road',
        'warehouse_city'           => 'Riyadh',
        'warehouse_state'          => 'Riyadh',
        'warehouse_country'        => 'SA',
        'warehouse_postcode'       => 'RMAD8016',
    ]);

    $response->assertRedirect(route('admin.dropshipping.keys.index'));
    $response->assertSessionHas('success', 'تم حفظ عنوان مستودع هايست وعناوين الشحن بنجاح.');

    $warehouse = DB::table('inventory_sources')->where('code', 'default')->first();
    expect($warehouse->contact_name)->toBe('Mostafa Mohammed');
    expect($warehouse->contact_number)->toBe('0572124578');
    expect($warehouse->contact_email)->toBe('mostafa@example.com');
    expect($warehouse->street)->toBe('Southern Ring Road');
    expect($warehouse->postcode)->toBe('RMAD8016');
});
