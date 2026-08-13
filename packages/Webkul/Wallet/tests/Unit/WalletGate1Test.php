<?php

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkul\Wallet\Exceptions\AccountUnderAuditException;
use Webkul\Wallet\Models\WalletAccount;
use Webkul\Wallet\Models\WalletPromoDebt;
use Webkul\Wallet\Models\WalletPromoDebtSettlement;
use Webkul\Wallet\Models\WalletPromotion;
use Webkul\Wallet\Models\WalletPromotionGrant;
use Webkul\Wallet\Models\WalletPromotionUsage;
use Webkul\Wallet\Models\WalletTransaction;
use Webkul\Wallet\Services\WalletService;

uses(DatabaseTransactions::class);

beforeEach(function () {
    // Ensure Customers table exists in MySQL test database
    if (! Schema::hasTable('customers')) {
        Schema::create('customers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });
    }

    // Ensure Gate 1 tables exist in MySQL test database
    if (! Schema::hasTable('wallet_accounts')) {
        Schema::create('wallet_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('customer_id');
            $table->decimal('total_balance', 12, 4)->default(0.0000);
            $table->decimal('available_balance', 12, 4)->default(0.0000);
            $table->decimal('held_balance', 12, 4)->default(0.0000);
            $table->decimal('promo_balance', 12, 4)->default(0.0000);
            $table->decimal('cash_balance', 12, 4)->default(0.0000);
            $table->decimal('unclassified_balance', 12, 4)->default(0.0000);
            $table->decimal('promo_debt', 12, 4)->default(0.0000);
            $table->string('backfill_status', 30)->default('verified');
            $table->string('currency_code', 3)->default('SAR');
            $table->string('status', 30)->default('active');
            $table->timestamps();
        });
    } else {
        if (! Schema::hasColumn('wallet_accounts', 'promo_balance')) {
            Schema::table('wallet_accounts', function (Blueprint $table) {
                $table->decimal('promo_balance', 12, 4)->unsigned()->default(0.0000)->after('available_balance');
                $table->decimal('cash_balance', 12, 4)->unsigned()->default(0.0000)->after('promo_balance');
                $table->decimal('unclassified_balance', 12, 4)->unsigned()->default(0.0000)->after('cash_balance');
                $table->decimal('promo_debt', 12, 4)->unsigned()->default(0.0000)->after('unclassified_balance');
                $table->string('backfill_status', 30)->default('verified')->after('promo_debt');
            });
        }
    }

    if (! Schema::hasTable('wallet_transactions')) {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wallet_id');
            $table->string('type', 50);
            $table->string('direction', 20);
            $table->decimal('amount', 12, 4);
            $table->decimal('running_balance', 12, 4);
            $table->string('description', 500)->nullable();
            $table->string('reference_type', 100)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->unsignedBigInteger('reference_transaction_id')->nullable();
            $table->string('created_by_type', 100)->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('wallet_promotions')) {
        Schema::create('wallet_promotions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type', 50);
            $table->string('status', 30)->default('draft');
            $table->string('action_type', 30)->default('percentage');
            $table->decimal('reward_value', 12, 4);
            $table->decimal('max_reward_amount', 12, 4)->nullable();
            $table->decimal('min_spend_amount', 12, 4)->nullable();
            $table->unsignedInteger('grant_validity_days')->nullable();
            $table->decimal('total_budget', 12, 4)->nullable();
            $table->decimal('total_allocated', 12, 4)->default(0.0000);
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('usage_per_customer')->nullable();
            $table->unsignedInteger('times_used')->default(0);
            $table->dateTime('starts_from')->nullable();
            $table->dateTime('ends_till')->nullable();
            $table->json('conditions')->nullable();
            $table->integer('priority')->default(0);
            $table->boolean('end_other_promotions')->default(false);
            $table->unsignedInteger('created_by_admin_id')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('wallet_promotion_usages')) {
        Schema::create('wallet_promotion_usages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('promotion_id');
            $table->unsignedInteger('customer_id');
            $table->string('event_key', 191);
            $table->decimal('reward_amount', 12, 4);
            $table->decimal('base_reward_amount', 12, 4);
            $table->decimal('net_credited_amount', 12, 4)->default(0.0000);
            $table->char('currency_code', 3)->default('SAR');
            $table->decimal('exchange_rate', 12, 4)->default(1.0000);
            $table->string('status', 30)->default('pending');
            $table->json('promotion_snapshot')->nullable();
            $table->json('decision_meta')->nullable();
            $table->timestamps();
            $table->unique(['promotion_id', 'event_key'], 'unique_usage_event');
        });
    }

    if (! Schema::hasTable('wallet_promotion_grants')) {
        Schema::create('wallet_promotion_grants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('promotion_id');
            $table->unsignedInteger('customer_id');
            $table->unsignedBigInteger('wallet_id');
            $table->unsignedBigInteger('usage_id');
            $table->unsignedBigInteger('wallet_transaction_id')->nullable();
            $table->decimal('original_amount', 12, 4);
            $table->decimal('remaining_amount', 12, 4);
            $table->decimal('consumed_amount', 12, 4)->default(0.0000);
            $table->char('currency_code', 3)->default('SAR');
            $table->decimal('base_amount', 12, 4);
            $table->string('status', 30)->default('active');
            $table->string('reference_type', 100)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->dateTime('granted_at');
            $table->dateTime('expires_at')->nullable();
            $table->timestamps();
            $table->unique('usage_id', 'unique_grant_usage');
        });
    }

    if (! Schema::hasTable('wallet_promo_debts')) {
        Schema::create('wallet_promo_debts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wallet_id');
            $table->unsignedInteger('customer_id');
            $table->unsignedInteger('order_id');
            $table->unsignedInteger('source_refund_id')->nullable();
            $table->string('event_key', 191);
            $table->char('currency_code', 3)->default('SAR');
            $table->decimal('original_debt_amount', 12, 4);
            $table->decimal('remaining_debt_amount', 12, 4);
            $table->decimal('settled_amount', 12, 4)->default(0.0000);
            $table->string('status', 30)->default('active');
            $table->string('reason', 255)->nullable();
            $table->timestamps();
            $table->dateTime('settled_at')->nullable();
            $table->unique('event_key', 'unique_debt_event');
        });
    }

    if (! Schema::hasTable('wallet_promo_debt_settlements')) {
        Schema::create('wallet_promo_debt_settlements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('debt_id');
            $table->unsignedBigInteger('wallet_id');
            $table->unsignedInteger('customer_id');
            $table->unsignedBigInteger('grant_id');
            $table->decimal('settlement_amount', 12, 4);
            $table->decimal('base_settlement_amount', 12, 4);
            $table->char('currency_code', 3)->default('SAR');
            $table->unsignedBigInteger('wallet_transaction_id')->nullable();
            $table->string('event_key', 191);
            $table->timestamp('created_at')->useCurrent();
            $table->unique('event_key', 'unique_debt_settlement');
        });
    }
});

test('creditPromotion increments promo_balance, available_balance and total_balance without touching cash_balance', function () {
    $customerId = DB::table('customers')->insertGetId([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john_'.uniqid().'@example.com',
    ]);

    $wallet = WalletAccount::create([
        'customer_id' => $customerId,
        'cash_balance' => '100.0000',
        'promo_balance' => '0.0000',
        'held_balance' => '0.0000',
        'unclassified_balance' => '0.0000',
        'promo_debt' => '0.0000',
        'available_balance' => '100.0000',
        'total_balance' => '100.0000',
        'status' => 'active',
        'backfill_status' => 'verified',
    ]);

    $service = app(WalletService::class);

    $txn = $service->creditPromotion(
        wallet: $wallet,
        amountStr: '25.0000',
        description: 'Test Promotional Reward',
        referenceType: WalletPromotion::class,
        referenceId: 1
    );

    $fresh = $wallet->fresh();

    expect((string) $fresh->promo_balance)->toEqual('25.0000');
    expect((string) $fresh->cash_balance)->toEqual('100.0000'); // Untouched
    expect((string) $fresh->held_balance)->toEqual('0.0000'); // Untouched
    expect((string) $fresh->available_balance)->toEqual('125.0000');
    expect((string) $fresh->total_balance)->toEqual('125.0000');
    expect($txn->type)->toBe(WalletTransaction::TYPE_CREDIT_PROMOTION);
    expect($txn->direction)->toBe('credit');
    expect((string) $txn->amount)->toEqual('25.0000');
    expect((string) $txn->running_balance)->toEqual('125.0000');
});

test('creditPromotion throws AccountUnderAuditException for accounts pending review', function () {
    $customerId = DB::table('customers')->insertGetId([
        'first_name' => 'Audit',
        'last_name' => 'User',
        'email' => 'audit_'.uniqid().'@example.com',
    ]);

    $wallet = WalletAccount::create([
        'customer_id' => $customerId,
        'cash_balance' => '0.0000',
        'promo_balance' => '0.0000',
        'held_balance' => '0.0000',
        'unclassified_balance' => '50.0000',
        'promo_debt' => '0.0000',
        'available_balance' => '0.0000',
        'total_balance' => '50.0000',
        'status' => 'active',
        'backfill_status' => 'pending_review',
    ]);

    $service = app(WalletService::class);

    expect(fn () => $service->creditPromotion(
        wallet: $wallet,
        amountStr: '15.0000',
        description: 'Should fail for audited account'
    ))->toThrow(AccountUnderAuditException::class);
});

test('creditPromotion rejects non-positive amounts and empty descriptions', function () {
    $customerId = DB::table('customers')->insertGetId([
        'first_name' => 'Zero',
        'last_name' => 'Amount',
        'email' => 'zero_'.uniqid().'@example.com',
    ]);

    $wallet = WalletAccount::create([
        'customer_id' => $customerId,
        'cash_balance' => '50.0000',
        'promo_balance' => '0.0000',
        'held_balance' => '0.0000',
        'unclassified_balance' => '0.0000',
        'promo_debt' => '0.0000',
        'available_balance' => '50.0000',
        'total_balance' => '50.0000',
        'status' => 'active',
        'backfill_status' => 'verified',
    ]);

    $service = app(WalletService::class);

    expect(fn () => $service->creditPromotion(
        wallet: $wallet,
        amountStr: '0.0000',
        description: 'Zero amount test'
    ))->toThrow(InvalidArgumentException::class);

    expect(fn () => $service->creditPromotion(
        wallet: $wallet,
        amountStr: '-10.0000',
        description: 'Negative amount test'
    ))->toThrow(InvalidArgumentException::class);

    expect(fn () => $service->creditPromotion(
        wallet: $wallet,
        amountStr: '10.0000',
        description: ''
    ))->toThrow(InvalidArgumentException::class);
});

test('T-21 exact numerical reconciliation: Grant=30, Debt=20 results in Net=10 credit and zero debt', function () {
    $customerId = DB::table('customers')->insertGetId([
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane_'.uniqid().'@example.com',
    ]);

    $orderId = DB::table('orders')->insertGetId([
        'status' => 'completed',
        'grand_total' => '100.0000',
        'base_grand_total' => '100.0000',
    ]);

    $wallet = WalletAccount::create([
        'customer_id' => $customerId,
        'cash_balance' => '100.0000',
        'promo_balance' => '0.0000',
        'held_balance' => '0.0000',
        'unclassified_balance' => '0.0000',
        'promo_debt' => '20.0000',
        'available_balance' => '100.0000',
        'total_balance' => '100.0000',
        'status' => 'active',
        'backfill_status' => 'verified',
    ]);

    $debt = WalletPromoDebt::create([
        'wallet_id' => $wallet->id,
        'customer_id' => $customerId,
        'order_id' => $orderId,
        'event_key' => 'refund:'.$orderId.':debt:reversal:'.uniqid(),
        'currency_code' => 'SAR',
        'original_debt_amount' => '20.0000',
        'remaining_debt_amount' => '20.0000',
        'settled_amount' => '0.0000',
        'status' => 'active',
        'reason' => 'Initial promo reversal deficit',
    ]);

    $promotion = WalletPromotion::create([
        'name' => 'Cashback Campaign 10%',
        'type' => WalletPromotion::TYPE_ORDER_SUBTOTAL_CASHBACK,
        'status' => WalletPromotion::STATUS_ACTIVE,
        'action_type' => WalletPromotion::ACTION_FIXED,
        'reward_value' => '30.0000',
        'grant_validity_days' => 30,
    ]);

    $service = app(WalletService::class);

    // Atomic Orchestrated Grant with Debt Settlement
    $result = DB::transaction(function () use ($wallet, $debt, $promotion, $service) {
        $lockedWallet = WalletAccount::lockForUpdate()->findOrFail($wallet->id);
        $lockedDebt = WalletPromoDebt::lockForUpdate()->findOrFail($debt->id);

        $grantAmountStr = '30.0000';
        $eventKey = 'order:101:invoice:45:promo:'.$promotion->id.':'.uniqid();

        // 1. Create Usage
        $usage = WalletPromotionUsage::create([
            'promotion_id' => $promotion->id,
            'customer_id' => $lockedWallet->customer_id,
            'event_key' => $eventKey,
            'reward_amount' => $grantAmountStr,
            'base_reward_amount' => $grantAmountStr,
            'net_credited_amount' => '10.0000',
            'currency_code' => 'SAR',
            'exchange_rate' => '1.0000',
            'status' => 'approved',
            'promotion_snapshot' => $promotion->toArray(),
        ]);

        // 2. Calculate Debt Settlement
        $settlementAmount = '20.0000';
        $netToCredit = '10.0000';

        $lockedDebt->remaining_debt_amount = '0.0000';
        $lockedDebt->settled_amount = '20.0000';
        $lockedDebt->status = 'settled';
        $lockedDebt->settled_at = now();
        $lockedDebt->save();

        // 3. Create Grant
        $grant = WalletPromotionGrant::create([
            'promotion_id' => $promotion->id,
            'customer_id' => $lockedWallet->customer_id,
            'wallet_id' => $lockedWallet->id,
            'usage_id' => $usage->id,
            'original_amount' => '30.0000',
            'remaining_amount' => '10.0000',
            'consumed_amount' => '20.0000',
            'currency_code' => 'SAR',
            'base_amount' => '30.0000',
            'status' => 'partially_consumed',
            'reference_type' => WalletPromotion::class,
            'reference_id' => $promotion->id,
            'granted_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        // 4. Record Settlement
        WalletPromoDebtSettlement::create([
            'debt_id' => $lockedDebt->id,
            'wallet_id' => $lockedWallet->id,
            'customer_id' => $lockedWallet->customer_id,
            'grant_id' => $grant->id,
            'settlement_amount' => $settlementAmount,
            'base_settlement_amount' => $settlementAmount,
            'currency_code' => 'SAR',
            'event_key' => "debt:{$lockedDebt->id}:grant:{$grant->id}:settle:".uniqid(),
        ]);

        // 5. Update promo_debt on Wallet
        $lockedWallet->promo_debt = '0.0000';
        $lockedWallet->save();

        // 6. Credit ONLY NET amount via WalletService
        $txn = $service->creditPromotion(
            wallet: $lockedWallet,
            amountStr: $netToCredit,
            description: 'Order Cashback #101 (Net credited: 10.0000, Settled debt: 20.0000)',
            referenceType: WalletPromotionGrant::class,
            referenceId: $grant->id
        );

        return ['grant' => $grant, 'txn' => $txn];
    });

    $freshWallet = $wallet->fresh();
    $freshDebt = $debt->fresh();
    $freshGrant = $result['grant']->fresh();

    // 1. Debt assertions
    expect((string) $freshDebt->remaining_debt_amount)->toEqual('0.0000');
    expect((string) $freshDebt->settled_amount)->toEqual('20.0000');
    expect($freshDebt->status)->toBe(WalletPromoDebt::STATUS_SETTLED);

    // 2. Grant assertions
    expect((string) $freshGrant->original_amount)->toEqual('30.0000');
    expect((string) $freshGrant->remaining_amount)->toEqual('10.0000');
    expect((string) $freshGrant->consumed_amount)->toEqual('20.0000');
    expect($freshGrant->status)->toBe(WalletPromotionGrant::STATUS_PARTIALLY_CONSUMED);

    // Invariant: original == remaining + consumed
    $expectedGrantOriginal = bcadd((string) $freshGrant->remaining_amount, (string) $freshGrant->consumed_amount, 4);
    expect((string) $freshGrant->original_amount)->toEqual($expectedGrantOriginal);

    // 3. Wallet assertions
    expect((string) $freshWallet->promo_debt)->toEqual('0.0000');
    expect((string) $freshWallet->cash_balance)->toEqual('100.0000'); // Untouched
    expect((string) $freshWallet->held_balance)->toEqual('0.0000'); // Untouched
    expect((string) $freshWallet->promo_balance)->toEqual('10.0000'); // EXACTLY +10 (no doubling!)
    expect((string) $freshWallet->available_balance)->toEqual('110.0000');
    expect((string) $freshWallet->total_balance)->toEqual('110.0000');

    // 4. Ledger assertions
    $promoTxns = WalletTransaction::where('wallet_id', $wallet->id)
        ->where('type', WalletTransaction::TYPE_CREDIT_PROMOTION)
        ->get();

    expect($promoTxns->count())->toBe(1); // EXACTLY 1 ledger entry
    expect((string) $promoTxns->first()->amount)->toEqual('10.0000');
    expect((string) $promoTxns->first()->running_balance)->toEqual('110.0000');
});

test('concurrent idempotency with duplicate key exception recovery', function () {
    $customerId = DB::table('customers')->insertGetId([
        'first_name' => 'Alice',
        'last_name' => 'Smith',
        'email' => 'alice_'.uniqid().'@example.com',
    ]);

    $wallet = WalletAccount::create([
        'customer_id' => $customerId,
        'cash_balance' => '50.0000',
        'promo_balance' => '0.0000',
        'held_balance' => '0.0000',
        'unclassified_balance' => '0.0000',
        'promo_debt' => '0.0000',
        'available_balance' => '50.0000',
        'total_balance' => '50.0000',
        'status' => 'active',
        'backfill_status' => 'verified',
    ]);

    $promotion = WalletPromotion::create([
        'name' => 'Welcome Reward 15 SAR',
        'type' => WalletPromotion::TYPE_WELCOME_BONUS,
        'status' => WalletPromotion::STATUS_ACTIVE,
        'action_type' => WalletPromotion::ACTION_FIXED,
        'reward_value' => '15.0000',
    ]);

    $service = app(WalletService::class);
    $eventKey = 'welcome:customer:'.$customerId;

    $executeGrant = function () use ($wallet, $promotion, $eventKey, $service) {
        try {
            return DB::transaction(function () use ($wallet, $promotion, $eventKey, $service) {
                $lockedWallet = WalletAccount::lockForUpdate()->findOrFail($wallet->id);

                $existingUsage = WalletPromotionUsage::where('promotion_id', $promotion->id)
                    ->where('event_key', $eventKey)
                    ->first();

                if ($existingUsage) {
                    $grant = WalletPromotionGrant::where('usage_id', $existingUsage->id)->firstOrFail();

                    return [
                        'grant' => $grant,
                        'is_idempotent' => true,
                    ];
                }

                $usage = WalletPromotionUsage::create([
                    'promotion_id' => $promotion->id,
                    'customer_id' => $lockedWallet->customer_id,
                    'event_key' => $eventKey,
                    'reward_amount' => '15.0000',
                    'base_reward_amount' => '15.0000',
                    'net_credited_amount' => '15.0000',
                    'currency_code' => 'SAR',
                    'status' => 'approved',
                    'promotion_snapshot' => $promotion->toArray(),
                ]);

                $grant = WalletPromotionGrant::create([
                    'promotion_id' => $promotion->id,
                    'customer_id' => $lockedWallet->customer_id,
                    'wallet_id' => $lockedWallet->id,
                    'usage_id' => $usage->id,
                    'original_amount' => '15.0000',
                    'remaining_amount' => '15.0000',
                    'consumed_amount' => '0.0000',
                    'currency_code' => 'SAR',
                    'base_amount' => '15.0000',
                    'status' => 'active',
                    'reference_type' => WalletPromotion::class,
                    'reference_id' => $promotion->id,
                    'granted_at' => now(),
                ]);

                $service->creditPromotion(
                    wallet: $lockedWallet,
                    amountStr: '15.0000',
                    description: 'Welcome Bonus',
                    referenceType: WalletPromotionGrant::class,
                    referenceId: $grant->id
                );

                return ['grant' => $grant, 'is_idempotent' => false];
            });
        } catch (QueryException $e) {
            // Duplicate entry key collision (Simulates race condition)
            if ($e->errorInfo[1] == 1062 || str_contains($e->getMessage(), 'Duplicate')) {
                $existingUsage = WalletPromotionUsage::where('promotion_id', $promotion->id)
                    ->where('event_key', $eventKey)
                    ->firstOrFail();

                $grant = WalletPromotionGrant::where('usage_id', $existingUsage->id)->firstOrFail();

                return ['grant' => $grant, 'is_idempotent' => true];
            }
            throw $e;
        }
    };

    // First attempt
    $res1 = $executeGrant();
    expect($res1['is_idempotent'])->toBeFalse();
    expect((string) $wallet->fresh()->promo_balance)->toEqual('15.0000');
    expect((string) $wallet->fresh()->available_balance)->toEqual('65.0000');

    // Second duplicate attempt
    $res2 = $executeGrant();
    expect($res2['is_idempotent'])->toBeTrue();
    expect($res2['grant']->id)->toBe($res1['grant']->id);
    expect((string) $wallet->fresh()->promo_balance)->toEqual('15.0000'); // Still 15, NOT 30!
    expect((string) $wallet->fresh()->available_balance)->toEqual('65.0000');

    // Total Grants & Usages count in DB for this customer
    expect(WalletPromotionUsage::where('customer_id', $customerId)->count())->toBe(1);
    expect(WalletPromotionGrant::where('customer_id', $customerId)->count())->toBe(1);
});
