<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Webkul\Wallet\Exceptions\InsufficientWalletBalanceException;
use Webkul\Wallet\Exceptions\WalletSuspendedException;
use Webkul\Wallet\Models\WalletAccount;
use Webkul\Wallet\Models\WalletTransaction;
use Webkul\Wallet\Services\WalletService;

uses(RefreshDatabase::class);

// Scenario 1: Credit increases available balance
test('credit increases available balance and creates transaction', function () {
    $wallet = WalletAccount::factory()->create([
        'available_balance' => 0,
        'total_balance' => 0,
        'status' => 'active',
    ]);

    $service = app(WalletService::class);

    $txn = $service->credit($wallet, 100.00, WalletTransaction::TYPE_CREDIT_TOPUP, 'Test credit');

    expect($wallet->fresh()->available_balance)->toEqual(100.00);
    expect($wallet->fresh()->total_balance)->toEqual(100.00);
    expect($txn->direction)->toBe('credit');
    expect($txn->amount)->toEqual(100.00);
    expect($txn->running_balance)->toEqual(100.00);
});

// Scenario 2: Debit decreases available balance
test('debit decreases available balance and creates transaction', function () {
    $wallet = WalletAccount::factory()->create([
        'available_balance' => 200.00,
        'total_balance' => 200.00,
        'status' => 'active',
    ]);

    $service = app(WalletService::class);

    $txn = $service->debit($wallet, 50.00, WalletTransaction::TYPE_DEBIT_PAYMENT, 'Test debit');

    expect($wallet->fresh()->available_balance)->toEqual(150.00);
    expect($wallet->fresh()->total_balance)->toEqual(150.00);
    expect($txn->direction)->toBe('debit');
});

// Scenario 3: InsufficientBalance exception
test('debit throws InsufficientWalletBalanceException when balance is insufficient', function () {
    $wallet = WalletAccount::factory()->create([
        'available_balance' => 10.00,
        'status' => 'active',
    ]);

    $service = app(WalletService::class);

    expect(fn () => $service->debit($wallet, 100.00, WalletTransaction::TYPE_DEBIT_PAYMENT, 'Should fail'))
        ->toThrow(InsufficientWalletBalanceException::class);
});

// Scenario 4: Hold moves amount from available to held
test('hold moves balance from available to held', function () {
    $wallet = WalletAccount::factory()->create([
        'available_balance' => 500.00,
        'held_balance' => 0,
        'total_balance' => 500.00,
        'status' => 'active',
    ]);

    $service = app(WalletService::class);
    $service->hold($wallet, 200.00, WalletTransaction::TYPE_HOLD_WITHDRAWAL, 'Withdrawal hold');

    expect($wallet->fresh()->available_balance)->toEqual(300.00);
    expect($wallet->fresh()->held_balance)->toEqual(200.00);
    expect($wallet->fresh()->total_balance)->toEqual(500.00);
});

// Scenario 5: Release returns held to available
test('release moves held balance back to available', function () {
    $wallet = WalletAccount::factory()->create([
        'available_balance' => 300.00,
        'held_balance' => 200.00,
        'total_balance' => 500.00,
        'status' => 'active',
    ]);

    $service = app(WalletService::class);
    $service->release($wallet, 200.00, WalletTransaction::TYPE_RELEASE_HOLD, 'Withdrawal rejected');

    expect($wallet->fresh()->available_balance)->toEqual(500.00);
    expect($wallet->fresh()->held_balance)->toEqual(0.0);
    expect($wallet->fresh()->total_balance)->toEqual(500.00);
});

// Scenario 6: Suspended wallet throws WalletSuspendedException
test('suspended wallet throws WalletSuspendedException', function () {
    $wallet = WalletAccount::factory()->create([
        'available_balance' => 500.00,
        'status' => 'suspended',
    ]);

    $service = app(WalletService::class);

    expect(fn () => $service->credit($wallet, 100.00, WalletTransaction::TYPE_CREDIT_TOPUP, 'Should fail'))
        ->toThrow(WalletSuspendedException::class);

    expect(fn () => $service->debit($wallet, 100.00, WalletTransaction::TYPE_DEBIT_PAYMENT, 'Should fail'))
        ->toThrow(WalletSuspendedException::class);
});

// Scenario 7: WalletTransaction is immutable — cannot be updated
test('WalletTransaction is immutable after creation', function () {
    $wallet = WalletAccount::factory()->create(['available_balance' => 100, 'status' => 'active']);
    $service = app(WalletService::class);
    $txn = $service->credit($wallet, 100, WalletTransaction::TYPE_CREDIT_TOPUP, 'test');

    expect(fn () => $txn->update(['amount' => 999]))
        ->toThrow(RuntimeException::class, 'immutable');
});

// Scenario 8: Adjust creates ADJUSTMENT transaction with reference
test('adjust creates ADJUSTMENT transaction with reference_transaction_id', function () {
    $wallet = WalletAccount::factory()->create(['available_balance' => 100, 'total_balance' => 100, 'status' => 'active']);
    $service = app(WalletService::class);

    $originalTxn = $service->credit($wallet, 100, WalletTransaction::TYPE_CREDIT_TOPUP, 'original');

    $adjustTxn = $service->adjust(
        wallet: $wallet->fresh(),
        amount: 50.00,
        direction: 'debit',
        reason: 'Correcting over-credit',
        adminUserId: 1,
        referenceTransactionId: $originalTxn->id
    );

    expect($adjustTxn->type)->toBe(WalletTransaction::TYPE_ADJUSTMENT);
    expect($adjustTxn->reference_transaction_id)->toBe($originalTxn->id);
    expect($wallet->fresh()->available_balance)->toEqual(150.00); // 100 original credit, 100+100-50 = 150
});
