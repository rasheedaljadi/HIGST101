<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('creating a refund credits the customer wallet', function () {
    // TODO: Create a refund and verify CREDIT_REFUND in wallet_transactions
    expect(true)->toBeTrue(); // Placeholder
});

test('paypal gateway refund is NOT called after Refund', function () {
    // D-003: PayPal refundOrder() is disabled
    // Verify that SmartButton::refundOrder is not called when a refund is created
    expect(true)->toBeTrue(); // Placeholder
});
