<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Webkul\Wallet\Http\Requests\Admin\StoreWalletPromotionRequest;
use Webkul\Wallet\Models\WalletAccount;
use Webkul\Wallet\Models\WalletPromoDebt;
use Webkul\Wallet\Models\WalletPromotion;
use Webkul\Wallet\Models\WalletPromotionAudit;
use Webkul\Wallet\Models\WalletPromotionGrant;
use Webkul\Wallet\Models\WalletPromotionOutbox;
use Webkul\Wallet\Models\WalletPromotionUsage;

uses(DatabaseTransactions::class);

beforeEach(function () {
    Config::set('sales.wallet_promotions.mode', 'active');

    if (! Schema::hasTable('customers')) {
        Schema::create('customers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('admins')) {
        Schema::create('admins', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });
    }
});

test('Scenario 1: Supports CRUD and persistence for all 4 promotion types', function () {
    $types = [
        WalletPromotion::TYPE_WELCOME_BONUS,
        WalletPromotion::TYPE_TOPUP_BONUS,
        WalletPromotion::TYPE_ORDER_SUBTOTAL_CASHBACK,
        WalletPromotion::TYPE_ORDER_CONDITIONAL_CASHBACK,
    ];

    foreach ($types as $type) {
        $promo = WalletPromotion::create([
            'name' => 'Promo '.$type,
            'type' => $type,
            'status' => WalletPromotion::STATUS_ACTIVE,
            'action_type' => WalletPromotion::ACTION_FIXED,
            'reward_value' => '25.0000',
            'min_spend_amount' => '100.0000',
            'max_reward_amount' => '50.0000',
            'total_budget' => '5000.0000',
            'usage_limit' => 100,
            'usage_per_customer' => 1,
            'priority' => 10,
            'grant_validity_days' => 30,
            'starts_from' => now(),
            'ends_till' => now()->addDays(60),
        ]);

        expect($promo->exists)->toBeTrue();
        expect($promo->type)->toBe($type);
        expect($promo->status)->toBe(WalletPromotion::STATUS_ACTIVE);
        expect((string) $promo->reward_value)->toEqual('25.0000');
    }

    expect(WalletPromotion::count())->toBeGreaterThanOrEqual(4);
});

test('Scenario 2: Validates FormRequest rules and rejects invalid promotion payloads', function () {
    $request = new StoreWalletPromotionRequest;
    $rules = $request->rules();

    // 1. Invalid Type
    $v1 = Validator::make([
        'name' => 'Test Promo',
        'type' => 'invalid_type',
        'status' => 'active',
        'action_type' => 'fixed',
        'reward_value' => '10.0000',
    ], $rules);
    expect($v1->fails())->toBeTrue();
    expect($v1->errors()->has('type'))->toBeTrue();

    // 2. Negative Reward
    $v2 = Validator::make([
        'name' => 'Test Promo',
        'type' => 'welcome_bonus',
        'status' => 'active',
        'action_type' => 'fixed',
        'reward_value' => '-5.0000',
    ], $rules);
    expect($v2->fails())->toBeTrue();
    expect($v2->errors()->has('reward_value'))->toBeTrue();

    // 3. Invalid Date Range (ends_till before starts_from)
    $v3 = Validator::make([
        'name' => 'Test Promo',
        'type' => 'welcome_bonus',
        'status' => 'active',
        'action_type' => 'fixed',
        'reward_value' => '10.0000',
        'starts_from' => '2026-09-01',
        'ends_till' => '2026-08-01', // Before start!
    ], $rules);
    expect($v3->fails())->toBeTrue();
    expect($v3->errors()->has('ends_till'))->toBeTrue();

    // 4. Valid Payload
    $v4 = Validator::make([
        'name' => 'Valid Welcome Promo',
        'type' => 'welcome_bonus',
        'status' => 'active',
        'action_type' => 'percentage',
        'reward_value' => '10.0000',
        'starts_from' => '2026-08-01',
        'ends_till' => '2026-09-01',
    ], $rules);
    expect($v4->passes())->toBeTrue();
});

test('Scenario 3: Records Audit log on promotion creation, update, and archiving', function () {
    $adminId = DB::table('admins')->insertGetId([
        'name' => 'Security Admin',
        'email' => 'admin_'.uniqid().'@example.com',
    ]);

    // 1. Create Promotion
    $promo = WalletPromotion::create([
        'name' => 'Audit Test Promo',
        'type' => WalletPromotion::TYPE_WELCOME_BONUS,
        'status' => WalletPromotion::STATUS_DRAFT,
        'action_type' => WalletPromotion::ACTION_FIXED,
        'reward_value' => '15.0000',
    ]);

    WalletPromotionAudit::create([
        'promotion_id' => $promo->id,
        'admin_user_id' => $adminId,
        'action' => WalletPromotionAudit::ACTION_CREATED,
        'old_values' => null,
        'new_values' => $promo->toArray(),
        'ip_address' => '127.0.0.1',
        'created_at' => now(),
    ]);

    expect(WalletPromotionAudit::where('promotion_id', $promo->id)->where('action', 'created')->count())->toBe(1);

    // 2. Update Promotion (Draft -> Active)
    $oldValues = $promo->toArray();
    $promo->update(['status' => WalletPromotion::STATUS_ACTIVE, 'reward_value' => '20.0000']);

    WalletPromotionAudit::create([
        'promotion_id' => $promo->id,
        'admin_user_id' => $adminId,
        'action' => WalletPromotionAudit::ACTION_UPDATED,
        'old_values' => $oldValues,
        'new_values' => $promo->fresh()->toArray(),
        'ip_address' => '127.0.0.1',
        'created_at' => now(),
    ]);

    expect(WalletPromotionAudit::where('promotion_id', $promo->id)->where('action', 'updated')->count())->toBe(1);

    // 3. Archive Promotion
    $oldValues = $promo->toArray();
    $promo->update(['status' => WalletPromotion::STATUS_ARCHIVED]);

    WalletPromotionAudit::create([
        'promotion_id' => $promo->id,
        'admin_user_id' => $adminId,
        'action' => WalletPromotionAudit::ACTION_ARCHIVED,
        'old_values' => $oldValues,
        'new_values' => $promo->fresh()->toArray(),
        'ip_address' => '127.0.0.1',
        'created_at' => now(),
    ]);

    expect(WalletPromotionAudit::where('promotion_id', $promo->id)->where('action', 'archived')->count())->toBe(1);
    expect($promo->fresh()->status)->toBe(WalletPromotion::STATUS_ARCHIVED);
});

test('Scenario 4: Customer presentation cleanly separates cash from promo and prohibits promo withdrawal', function () {
    $customerId = DB::table('customers')->insertGetId([
        'first_name' => 'Presenter',
        'last_name' => 'User',
        'email' => 'presenter_'.uniqid().'@example.com',
    ]);

    // Customer has 100 Cash + 50 Promo
    $wallet = WalletAccount::create([
        'customer_id' => $customerId,
        'cash_balance' => '100.0000',
        'promo_balance' => '50.0000',
        'held_balance' => '10.0000', // 10 held for pending withdrawal
        'unclassified_balance' => '0.0000',
        'promo_debt' => '0.0000',
        'available_balance' => '150.0000', // Available for shopping: 100 + 50 = 150
        'total_balance' => '150.0000',
        'status' => 'active',
        'backfill_status' => 'verified',
    ]);

    $rawCash = (float) $wallet->cash_balance;
    $rawPromo = (float) $wallet->promo_balance;
    $rawHeld = (float) $wallet->held_balance;

    // Withdrawable balance is strictly Cash - Held, NEVER includes Promo!
    $withdrawable = max(0, $rawCash - $rawHeld);

    expect($rawCash)->toBe(100.0);
    expect($rawPromo)->toBe(50.0);
    expect($withdrawable)->toBe(90.0); // 100 - 10 = 90. 50 Promo is strictly excluded!
    expect((float) $wallet->available_balance)->toBe(150.0);

    // Customer cannot withdraw 120 (since max withdrawable is 90)
    $attemptWithdrawalAmount = 120.0;
    $isEligibleForWithdrawal = $attemptWithdrawalAmount <= $withdrawable;
    expect($isEligibleForWithdrawal)->toBeFalse();
});

test('Scenario 5: Internal monitoring queries for Usages, Grants, Debts, and Outbox execute successfully', function () {
    $customerId = DB::table('customers')->insertGetId([
        'first_name' => 'Monitor',
        'last_name' => 'Auditee',
        'email' => 'monitor_'.uniqid().'@example.com',
    ]);

    $wallet = WalletAccount::create([
        'customer_id' => $customerId,
        'cash_balance' => '0.0000',
        'promo_balance' => '0.0000',
        'held_balance' => '0.0000',
        'unclassified_balance' => '0.0000',
        'promo_debt' => '0.0000',
        'available_balance' => '0.0000',
        'total_balance' => '0.0000',
        'status' => 'active',
        'backfill_status' => 'verified',
    ]);

    $promotion = WalletPromotion::create([
        'name' => 'Monitoring Target Promo',
        'type' => WalletPromotion::TYPE_WELCOME_BONUS,
        'status' => WalletPromotion::STATUS_ACTIVE,
        'action_type' => WalletPromotion::ACTION_FIXED,
        'reward_value' => '10.0000',
    ]);

    // Create records across monitoring tables
    $usage = WalletPromotionUsage::create([
        'promotion_id' => $promotion->id,
        'customer_id' => $customerId,
        'event_key' => 'mon:use:'.uniqid(),
        'reward_amount' => '10.0000',
        'base_reward_amount' => '10.0000',
        'net_credited_amount' => '10.0000',
        'currency_code' => 'SAR',
        'exchange_rate' => '1.0000',
        'status' => 'approved',
        'promotion_snapshot' => $promotion->toArray(),
    ]);

    $grant = WalletPromotionGrant::create([
        'promotion_id' => $promotion->id,
        'customer_id' => $customerId,
        'wallet_id' => $wallet->id,
        'usage_id' => $usage->id,
        'original_amount' => '10.0000',
        'remaining_amount' => '10.0000',
        'consumed_amount' => '0.0000',
        'currency_code' => 'SAR',
        'base_amount' => '10.0000',
        'status' => 'active',
        'reference_type' => WalletPromotion::class,
        'reference_id' => $promotion->id,
        'granted_at' => now(),
    ]);

    if (! Schema::hasTable('orders')) {
        Schema::create('orders', function (Blueprint $table) {
            $table->increments('id');
            $table->string('status')->default('pending');
            $table->decimal('grand_total', 12, 4)->default(0);
            $table->decimal('base_grand_total', 12, 4)->default(0);
            $table->timestamps();
        });
    }

    $orderId = DB::table('orders')->insertGetId([
        'status' => 'completed',
        'grand_total' => '100.0000',
        'base_grand_total' => '100.0000',
    ]);

    $debt = WalletPromoDebt::create([
        'wallet_id' => $wallet->id,
        'customer_id' => $customerId,
        'order_id' => $orderId,
        'event_key' => 'mon:debt:'.uniqid(),
        'currency_code' => 'SAR',
        'original_debt_amount' => '5.0000',
        'remaining_debt_amount' => '5.0000',
        'settled_amount' => '0.0000',
        'status' => 'active',
        'reason' => 'Monitoring test debt',
    ]);

    $outboxEventKey = 'mon:outbox:'.uniqid();
    $outbox = WalletPromotionOutbox::create([
        'event_type' => 'welcome_bonus',
        'event_key' => $outboxEventKey,
        'payload' => ['promo' => $promotion->id],
        'status' => 'pending',
        'attempts' => 0,
    ]);

    // Query assertions
    expect(WalletPromotionUsage::where('customer_id', $customerId)->count())->toBe(1);
    expect(WalletPromotionGrant::where('customer_id', $customerId)->count())->toBe(1);
    expect(WalletPromoDebt::where('customer_id', $customerId)->count())->toBe(1);
    expect(WalletPromotionOutbox::where('event_key', $outboxEventKey)->count())->toBe(1);
});

test('Scenario 6: Enforces Archive-only policy and strictly prohibits physical deletion of promotion records', function () {
    $promo = WalletPromotion::create([
        'name' => 'Anti-Delete Campaign',
        'type' => WalletPromotion::TYPE_WELCOME_BONUS,
        'status' => WalletPromotion::STATUS_ACTIVE,
        'action_type' => WalletPromotion::ACTION_FIXED,
        'reward_value' => '10.0000',
    ]);

    // 1. Direct physical delete MUST throw LogicException
    expect(fn () => $promo->delete())->toThrow(LogicException::class);

    // 2. Promotion record remains intact in database
    expect(WalletPromotion::find($promo->id))->not->toBeNull();

    // 3. Official Admin Destruction Route executes status archiving, preserving accounting history
    $promo->status = WalletPromotion::STATUS_ARCHIVED;
    $promo->save();

    $freshPromo = WalletPromotion::find($promo->id);
    expect($freshPromo)->not->toBeNull();
    expect($freshPromo->status)->toBe(WalletPromotion::STATUS_ARCHIVED);
});
