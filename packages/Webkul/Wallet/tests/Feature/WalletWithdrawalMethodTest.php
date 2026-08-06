<?php

use Webkul\User\Models\Admin;
use Webkul\Wallet\Models\WalletWithdrawalMethod;

test('admin can list withdrawal methods', function () {
    $admin = Admin::factory()->create();

    $this->actingAs($admin, 'admin')
        ->getJson(route('admin.wallet.withdrawal_methods.index'))
        ->assertOk()
        ->assertJsonStructure(['methods']);
});

test('admin can create a new withdrawal method', function () {
    $admin = Admin::factory()->create();

    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.wallet.withdrawal_methods.store'), [
            'name' => 'مصرف النجم الإسباني',
        ])
        ->assertOk()
        ->assertJsonFragment(['name' => 'مصرف النجم الإسباني']);

    $this->assertDatabaseHas('wallet_withdrawal_methods', [
        'name' => 'مصرف النجم الإسباني',
        'status' => true,
    ]);
});

test('admin can update withdrawal method name', function () {
    $admin = Admin::factory()->create();
    $method = WalletWithdrawalMethod::create([
        'name' => 'طريقة قديمة',
        'status' => true,
        'sort_order' => 1,
    ]);

    $this->actingAs($admin, 'admin')
        ->putJson(route('admin.wallet.withdrawal_methods.update', $method->id), [
            'name' => 'طريقة جديدة مضافة',
        ])
        ->assertOk()
        ->assertJsonFragment(['name' => 'طريقة جديدة مضافة']);

    $this->assertDatabaseHas('wallet_withdrawal_methods', [
        'id' => $method->id,
        'name' => 'طريقة جديدة مضافة',
    ]);
});

test('admin can toggle withdrawal method status', function () {
    $admin = Admin::factory()->create();
    $method = WalletWithdrawalMethod::create([
        'name' => 'طريقة للتجربة',
        'status' => true,
        'sort_order' => 1,
    ]);

    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.wallet.withdrawal_methods.toggle', $method->id))
        ->assertOk();

    expect($method->fresh()->status)->toBeFalse();
});

test('admin can delete a withdrawal method', function () {
    $admin = Admin::factory()->create();
    $method = WalletWithdrawalMethod::create([
        'name' => 'طريقة للحذف',
        'status' => true,
        'sort_order' => 1,
    ]);

    $this->actingAs($admin, 'admin')
        ->deleteJson(route('admin.wallet.withdrawal_methods.destroy', $method->id))
        ->assertOk();

    $this->assertDatabaseMissing('wallet_withdrawal_methods', [
        'id' => $method->id,
    ]);
});
