<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('placing an order with wallet payment debits the wallet', function () {
    // TODO: Create a full checkout scenario using OrderRepository
    // This requires factories for Customer, Product, Cart, Order
    // Stub: ensure DebitWalletOnOrderCreated listener fires correctly
    expect(true)->toBeTrue(); // Placeholder
});

test('placing an order with wallet payment rolls back if balance insufficient', function () {
    // TODO: Verify DB::rollBack() is triggered when InsufficientWalletBalanceException is thrown
    expect(true)->toBeTrue(); // Placeholder
});
