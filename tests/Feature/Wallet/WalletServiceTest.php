<?php

use Webkul\Customer\Models\Customer;
use Webkul\Wallet\Exceptions\InsufficientWalletBalanceException;
use Webkul\Wallet\Exceptions\WalletSuspendedException;
use Webkul\Wallet\Models\WalletAccount;
use Webkul\Wallet\Models\WalletTransaction;
use Webkul\Wallet\Services\WalletService;

beforeEach(function () {
    $this->walletService = app(WalletService::class);
    $this->customer = Customer::factory()->create();
    $this->wallet = WalletAccount::factory()->create([
        'customer_id' => $this->customer->id,
        'total_balance' => 500.00,
        'available_balance' => 500.00,
        'held_balance' => 0.00,
        'status' => 'active',
    ]);
});

/*
|--------------------------------------------------------------------------
| Group 1: Credit (الإيداع)
|--------------------------------------------------------------------------
*/

it('can credit a wallet successfully', function () {
    $txn = $this->walletService->credit(
        wallet: $this->wallet,
        amount: 150.00,
        type: WalletTransaction::TYPE_CREDIT_TOPUP,
        description: 'Test Top-up Credit'
    );

    $freshWallet = $this->wallet->fresh();

    expect($freshWallet->available_balance)->toEqual(650.00);
    expect($freshWallet->total_balance)->toEqual(650.00);
    expect($freshWallet->held_balance)->toEqual(0.00);
    expect($txn->direction)->toBe('credit');
    expect((float) $txn->amount)->toEqual(150.00);
    expect((float) $txn->running_balance)->toEqual(650.00);
});

it('fails to credit a suspended wallet', function () {
    $this->wallet->update(['status' => WalletAccount::STATUS_SUSPENDED]);

    expect(fn () => $this->walletService->credit(
        wallet: $this->wallet,
        amount: 100.00,
        type: WalletTransaction::TYPE_CREDIT_TOPUP,
        description: 'Credit to suspended wallet'
    ))->toThrow(WalletSuspendedException::class);
});

/*
|--------------------------------------------------------------------------
| Group 2: Debit (الخصم)
|--------------------------------------------------------------------------
*/

it('can debit a wallet successfully', function () {
    $txn = $this->walletService->debit(
        wallet: $this->wallet,
        amount: 200.00,
        type: WalletTransaction::TYPE_DEBIT_PAYMENT,
        description: 'Order Payment Debit'
    );

    $freshWallet = $this->wallet->fresh();

    expect($freshWallet->available_balance)->toEqual(300.00);
    expect($freshWallet->total_balance)->toEqual(300.00);
    expect($freshWallet->held_balance)->toEqual(0.00);
    expect($txn->direction)->toBe('debit');
    expect((float) $txn->amount)->toEqual(200.00);
    expect((float) $txn->running_balance)->toEqual(300.00);
});

it('throws insufficient balance exception on debit', function () {
    expect(fn () => $this->walletService->debit(
        wallet: $this->wallet,
        amount: 1000.00,
        type: WalletTransaction::TYPE_DEBIT_PAYMENT,
        description: 'Debit exceeding balance'
    ))->toThrow(InsufficientWalletBalanceException::class);
});

/*
|--------------------------------------------------------------------------
| Group 3: Hold Funds (حجز الرصيد)
|--------------------------------------------------------------------------
*/

it('can hold funds correctly', function () {
    $txn = $this->walletService->hold(
        wallet: $this->wallet,
        amount: 150.00,
        type: WalletTransaction::TYPE_HOLD_WITHDRAWAL,
        description: 'Withdrawal Hold'
    );

    $freshWallet = $this->wallet->fresh();

    expect($freshWallet->available_balance)->toEqual(350.00);
    expect($freshWallet->held_balance)->toEqual(150.00);
    expect($freshWallet->total_balance)->toEqual(500.00); // Total remains unchanged during hold
    expect($txn->direction)->toBe('debit');
    expect((float) $txn->amount)->toEqual(150.00);
});

it('fails to hold funds if insufficient available balance', function () {
    expect(fn () => $this->walletService->hold(
        wallet: $this->wallet,
        amount: 600.00,
        type: WalletTransaction::TYPE_HOLD_WITHDRAWAL,
        description: 'Hold exceeding available'
    ))->toThrow(InsufficientWalletBalanceException::class);
});

/*
|--------------------------------------------------------------------------
| Group 4: Release & Complete (إطلاق وإتمام السحب)
|--------------------------------------------------------------------------
*/

it('can release held funds', function () {
    // First hold funds
    $this->walletService->hold(
        wallet: $this->wallet,
        amount: 200.00,
        type: WalletTransaction::TYPE_HOLD_WITHDRAWAL,
        description: 'Withdrawal Hold'
    );

    // Then release held funds
    $txn = $this->walletService->release(
        wallet: $this->wallet->fresh(),
        amount: 200.00,
        type: WalletTransaction::TYPE_RELEASE_HOLD,
        description: 'Withdrawal Rejected - Release Hold'
    );

    $freshWallet = $this->wallet->fresh();

    expect($freshWallet->available_balance)->toEqual(500.00);
    expect($freshWallet->held_balance)->toEqual(0.00);
    expect($freshWallet->total_balance)->toEqual(500.00);
    expect($txn->direction)->toBe('credit');
});

it('can complete withdrawal from held funds', function () {
    // First hold funds ($200 held, $300 available, $500 total)
    $this->walletService->hold(
        wallet: $this->wallet,
        amount: 200.00,
        type: WalletTransaction::TYPE_HOLD_WITHDRAWAL,
        description: 'Withdrawal Hold'
    );

    // Complete withdrawal (deducts held and total)
    $txn = $this->walletService->completeWithdrawal(
        wallet: $this->wallet->fresh(),
        amount: 200.00,
        description: 'Bank Payout Complete'
    );

    $freshWallet = $this->wallet->fresh();

    expect($freshWallet->available_balance)->toEqual(300.00); // Unchanged from hold state
    expect($freshWallet->held_balance)->toEqual(0.00);      // Held deducted to 0
    expect($freshWallet->total_balance)->toEqual(300.00);     // Total deducted by $200
    expect($txn->direction)->toBe('debit');
    expect((float) $txn->amount)->toEqual(200.00);
});
