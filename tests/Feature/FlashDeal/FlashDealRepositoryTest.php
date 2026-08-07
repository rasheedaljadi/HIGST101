<?php

use Tests\TestCase;
use Webkul\FlashDeal\Models\FlashDeal;
use Webkul\FlashDeal\Repositories\FlashDealRepository;

uses(TestCase::class);

it('returns only active deals from getActiveDeals repository method', function () {
    $repository = app(FlashDealRepository::class);

    // 1. Expired deal
    $expiredDeal = FlashDeal::factory()->expired()->create(['title' => 'Expired Deal']);

    // 2. Future deal
    $futureDeal = FlashDeal::factory()->future()->create(['title' => 'Future Deal']);

    // 3. Active deal
    $activeDeal = FlashDeal::factory()->active()->create(['title' => 'Active Deal']);

    $activeDeals = $repository->getActiveDeals();

    expect($activeDeals)->toHaveCount(1);
    expect($activeDeals->first()->id)->toBe($activeDeal->id);
    expect($activeDeals->first()->title)->toBe('Active Deal');
});

it('does not return disabled deals even if within valid date window', function () {
    $repository = app(FlashDealRepository::class);

    // Active dates but status = 0
    FlashDeal::factory()->active()->inactive()->create(['title' => 'Disabled Active Date Deal']);

    $activeDeals = $repository->getActiveDeals();

    expect($activeDeals)->toBeEmpty();
});
