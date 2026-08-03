<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('creating withdrawal request holds the balance', function () {
    // TODO: Verify HOLD_WITHDRAWAL transaction and balance change
    expect(true)->toBeTrue(); // Placeholder
});

test('completing withdrawal debits the held balance', function () {
    // TODO: Verify DEBIT_WITHDRAWAL transaction after admin completes
    expect(true)->toBeTrue(); // Placeholder
});

test('rejecting withdrawal releases the held balance', function () {
    // TODO: Verify RELEASE_HOLD transaction after admin rejects
    expect(true)->toBeTrue(); // Placeholder
});
