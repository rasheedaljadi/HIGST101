<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkul\Wallet\Exceptions\AccountUnderAuditException;
use Webkul\Wallet\Models\WalletAccount;
use Webkul\Wallet\Models\WalletPromoDebt;
use Webkul\Wallet\Models\WalletPromotion;
use Webkul\Wallet\Models\WalletPromotionGrant;
use Webkul\Wallet\Models\WalletPromotionOutbox;
use Webkul\Wallet\Models\WalletPromotionUsage;
use Webkul\Wallet\Models\WalletTransaction;
use Webkul\Wallet\Services\PromotionGrantService;
use Webkul\Wallet\Services\WalletPromotionOrchestrator;
use Webkul\Wallet\Services\WalletPromotionOutboxWorker;

uses(DatabaseTransactions::class);

beforeEach(function () {
    // Enable active mode for isolated Gate 2 test executions
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

    if (! Schema::hasTable('orders')) {
        Schema::create('orders', function (Blueprint $table) {
            $table->increments('id');
            $table->string('status')->default('pending');
            $table->decimal('grand_total', 12, 4)->default(0);
            $table->decimal('base_grand_total', 12, 4)->default(0);
            $table->timestamps();
        });
    }
});

test('PromotionGrantService calculates reward and creates usage and grant lots with invariant check', function () {
    $customerId = DB::table('customers')->insertGetId([
        'first_name' => 'Grant',
        'last_name' => 'User',
        'email' => 'grant_'.uniqid().'@example.com',
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

    $promotion = WalletPromotion::create([
        'name' => '10% Percentage Reward',
        'type' => WalletPromotion::TYPE_ORDER_SUBTOTAL_CASHBACK,
        'status' => WalletPromotion::STATUS_ACTIVE,
        'action_type' => WalletPromotion::ACTION_PERCENTAGE,
        'reward_value' => '10.0000',
        'max_reward_amount' => '50.0000',
        'grant_validity_days' => 14,
    ]);

    $grantService = app(PromotionGrantService::class);

    // Calculate reward on 200 SAR order = 20 SAR
    $reward = $grantService->calculateReward($promotion, '200.0000');
    expect($reward)->toEqual('20.0000');

    // Create grant lot
    $eventKey = 'order:999:promo:'.$promotion->id.':'.uniqid();
    $bundle = $grantService->createGrant(
        promotion: $promotion,
        wallet: $wallet,
        eventKey: $eventKey,
        rewardAmountStr: '20.0000',
        netCreditedAmountStr: '20.0000',
        referenceType: WalletPromotion::class,
        referenceId: $promotion->id
    );

    $usage = $bundle['usage'];
    $grant = $bundle['grant'];

    expect($usage->reward_amount)->toEqual('20.0000');
    expect($usage->net_credited_amount)->toEqual('20.0000');
    expect($grant->original_amount)->toEqual('20.0000');
    expect($grant->remaining_amount)->toEqual('20.0000');
    expect($grant->consumed_amount)->toEqual('0.0000');
    expect($grant->status)->toBe(WalletPromotionGrant::STATUS_ACTIVE);
    expect($grant->expires_at)->not->toBeNull();

    // Invariant: original == remaining + consumed
    expect((string) $grant->original_amount)->toEqual(
        bcadd((string) $grant->remaining_amount, (string) $grant->consumed_amount, 4)
    );
});

test('WalletPromotionOrchestrator settles debt and credits net amount (T-21 end-to-end flow)', function () {
    $customerId = DB::table('customers')->insertGetId([
        'first_name' => 'T21',
        'last_name' => 'Orchestrated',
        'email' => 't21_orch_'.uniqid().'@example.com',
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
        'event_key' => "refund:{$orderId}:debt:deficit:".uniqid(),
        'currency_code' => 'SAR',
        'original_debt_amount' => '20.0000',
        'remaining_debt_amount' => '20.0000',
        'settled_amount' => '0.0000',
        'status' => WalletPromoDebt::STATUS_ACTIVE,
        'reason' => 'Deficit reversal',
    ]);

    $promotion = WalletPromotion::create([
        'name' => 'Cashback 30 SAR',
        'type' => WalletPromotion::TYPE_ORDER_SUBTOTAL_CASHBACK,
        'status' => WalletPromotion::STATUS_ACTIVE,
        'action_type' => WalletPromotion::ACTION_FIXED,
        'reward_value' => '30.0000',
        'grant_validity_days' => 30,
    ]);

    $orchestrator = app(WalletPromotionOrchestrator::class);
    $eventKey = "order:{$orderId}:invoice:77:promo:{$promotion->id}:".uniqid();

    $result = $orchestrator->applyPromotionGrant(
        promotion: $promotion,
        walletId: $wallet->id,
        eventKey: $eventKey,
        eligibleAmountStr: '100.0000',
        referenceType: WalletPromotion::class,
        referenceId: $promotion->id
    );

    expect($result['applied'])->toBeTrue();
    expect($result['is_idempotent'])->toBeFalse();
    expect($result['reward_amount'])->toEqual('30.0000');
    expect($result['total_settled'])->toEqual('20.0000');
    expect($result['net_credited'])->toEqual('10.0000');

    $freshWallet = $wallet->fresh();
    $freshDebt = $debt->fresh();
    $freshGrant = $result['grant']->fresh();

    // Assertions
    expect((string) $freshDebt->remaining_debt_amount)->toEqual('0.0000');
    expect((string) $freshDebt->settled_amount)->toEqual('20.0000');
    expect($freshDebt->status)->toBe(WalletPromoDebt::STATUS_SETTLED);

    expect((string) $freshGrant->original_amount)->toEqual('30.0000');
    expect((string) $freshGrant->remaining_amount)->toEqual('10.0000');
    expect((string) $freshGrant->consumed_amount)->toEqual('20.0000');

    expect((string) $freshWallet->cash_balance)->toEqual('100.0000');
    expect((string) $freshWallet->promo_balance)->toEqual('10.0000');
    expect((string) $freshWallet->promo_debt)->toEqual('0.0000');
    expect((string) $freshWallet->available_balance)->toEqual('110.0000');
    expect((string) $freshWallet->total_balance)->toEqual('110.0000');

    // Ledger check
    $promoTxns = WalletTransaction::where('wallet_id', $wallet->id)
        ->where('type', WalletTransaction::TYPE_CREDIT_PROMOTION)
        ->get();
    expect($promoTxns->count())->toBe(1);
    expect((string) $promoTxns->first()->amount)->toEqual('10.0000');
});

test('WalletPromotionOrchestrator handles idempotency and prevents double credit under re-execution', function () {
    $customerId = DB::table('customers')->insertGetId([
        'first_name' => 'Idempotent',
        'last_name' => 'Client',
        'email' => 'idem_'.uniqid().'@example.com',
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
        'name' => 'Welcome 25 SAR',
        'type' => WalletPromotion::TYPE_WELCOME_BONUS,
        'status' => WalletPromotion::STATUS_ACTIVE,
        'action_type' => WalletPromotion::ACTION_FIXED,
        'reward_value' => '25.0000',
    ]);

    $orchestrator = app(WalletPromotionOrchestrator::class);
    $eventKey = 'welcome:user:'.$customerId;

    // Execution 1
    $res1 = $orchestrator->applyPromotionGrant(
        promotion: $promotion,
        walletId: $wallet->id,
        eventKey: $eventKey,
        eligibleAmountStr: '0.0000',
        referenceType: WalletPromotion::class,
        referenceId: $promotion->id
    );

    expect($res1['applied'])->toBeTrue();
    expect($res1['is_idempotent'])->toBeFalse();
    expect((string) $wallet->fresh()->promo_balance)->toEqual('25.0000');

    // Execution 2 (Same event key)
    $res2 = $orchestrator->applyPromotionGrant(
        promotion: $promotion,
        walletId: $wallet->id,
        eventKey: $eventKey,
        eligibleAmountStr: '0.0000',
        referenceType: WalletPromotion::class,
        referenceId: $promotion->id
    );

    expect($res2['applied'])->toBeTrue();
    expect($res2['is_idempotent'])->toBeTrue();
    expect($res2['grant']->id)->toBe($res1['grant']->id);
    expect((string) $wallet->fresh()->promo_balance)->toEqual('25.0000'); // Still 25, NOT 50!

    // Verify exactly 1 grant, 1 usage, 1 ledger record for this customer
    expect(WalletPromotionUsage::where('customer_id', $customerId)->count())->toBe(1);
    expect(WalletPromotionGrant::where('customer_id', $customerId)->count())->toBe(1);
    expect(WalletTransaction::where('wallet_id', $wallet->id)->where('type', WalletTransaction::TYPE_CREDIT_PROMOTION)->count())->toBe(1);
});

test('WalletPromotionOutboxWorker claims pending and expired lease jobs and processes them', function () {
    $customerId = DB::table('customers')->insertGetId([
        'first_name' => 'Worker',
        'last_name' => 'Test',
        'email' => 'worker_'.uniqid().'@example.com',
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

    $promotion = WalletPromotion::create([
        'name' => 'Outbox Cashback 15',
        'type' => WalletPromotion::TYPE_ORDER_SUBTOTAL_CASHBACK,
        'status' => WalletPromotion::STATUS_ACTIVE,
        'action_type' => WalletPromotion::ACTION_FIXED,
        'reward_value' => '15.0000',
    ]);

    $eventKey1 = 'outbox:order:1001:'.uniqid();
    $eventKey2 = 'outbox:order:1002:'.uniqid();

    // 1. Pending job
    $job1 = WalletPromotionOutbox::create([
        'event_type' => 'order_subtotal_cashback',
        'event_key' => $eventKey1,
        'aggregate_type' => 'order',
        'aggregate_id' => 1001,
        'payload' => [
            'promotion_id' => $promotion->id,
            'wallet_id' => $wallet->id,
            'eligible_amount' => '100.0000',
        ],
        'status' => WalletPromotionOutbox::STATUS_PENDING,
        'attempts' => 0,
    ]);

    // 2. Processing job whose lease has expired
    $job2 = WalletPromotionOutbox::create([
        'event_type' => 'order_subtotal_cashback',
        'event_key' => $eventKey2,
        'aggregate_type' => 'order',
        'aggregate_id' => 1002,
        'payload' => [
            'promotion_id' => $promotion->id,
            'wallet_id' => $wallet->id,
            'eligible_amount' => '100.0000',
        ],
        'status' => WalletPromotionOutbox::STATUS_PROCESSING,
        'locked_at' => now()->subMinutes(10),
        'locked_by' => 'crashed-worker',
        'lease_expires_at' => now()->subMinutes(5), // Expired!
        'attempts' => 1,
    ]);

    $worker = app(WalletPromotionOutboxWorker::class);

    // Run one pass of worker
    $processedCount = $worker->runOnce(batchSize: 10, leaseSeconds: 60, workerId: 'test-runner-1');

    expect($processedCount)->toBe(2);

    $freshJob1 = $job1->fresh();
    $freshJob2 = $job2->fresh();

    expect($freshJob1->status)->toBe(WalletPromotionOutbox::STATUS_COMPLETED);
    expect($freshJob1->processed_at)->not->toBeNull();

    expect($freshJob2->status)->toBe(WalletPromotionOutbox::STATUS_COMPLETED);
    expect($freshJob2->processed_at)->not->toBeNull();
    expect($freshJob2->attempts)->toBe(2); // Incremented from 1 to 2

    // Balance received exactly 15 + 15 = 30
    expect((string) $wallet->fresh()->promo_balance)->toEqual('30.0000');
});

test('WalletPromotionOrchestrator rejects accounts under audit (pending_review)', function () {
    $customerId = DB::table('customers')->insertGetId([
        'first_name' => 'Audit',
        'last_name' => 'Guard2',
        'email' => 'audit2_'.uniqid().'@example.com',
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

    $promotion = WalletPromotion::create([
        'name' => 'Bonus',
        'type' => WalletPromotion::TYPE_WELCOME_BONUS,
        'status' => WalletPromotion::STATUS_ACTIVE,
        'action_type' => WalletPromotion::ACTION_FIXED,
        'reward_value' => '20.0000',
    ]);

    $orchestrator = app(WalletPromotionOrchestrator::class);

    expect(fn () => $orchestrator->applyPromotionGrant(
        promotion: $promotion,
        walletId: $wallet->id,
        eventKey: 'audit:test:'.$customerId,
        eligibleAmountStr: '0.0000',
        referenceType: WalletPromotion::class,
        referenceId: $promotion->id
    ))->toThrow(AccountUnderAuditException::class);

    expect((string) $wallet->fresh()->promo_balance)->toEqual('0.0000');
    expect((string) $wallet->fresh()->total_balance)->toEqual('50.0000');
    expect(WalletPromotionGrant::where('wallet_id', $wallet->id)->count())->toBe(0);
});

test('PromotionGrantService reverses grant lot without altering cash balance and flags deficit', function () {
    $customerId = DB::table('customers')->insertGetId([
        'first_name' => 'Reverse',
        'last_name' => 'Lot',
        'email' => 'rev_'.uniqid().'@example.com',
    ]);

    $wallet = WalletAccount::create([
        'customer_id' => $customerId,
        'cash_balance' => '100.0000',
        'promo_balance' => '20.0000',
        'held_balance' => '0.0000',
        'unclassified_balance' => '0.0000',
        'promo_debt' => '0.0000',
        'available_balance' => '120.0000',
        'total_balance' => '120.0000',
        'status' => 'active',
        'backfill_status' => 'verified',
    ]);

    $promotion = WalletPromotion::create([
        'name' => 'Grant for Reversal',
        'type' => WalletPromotion::TYPE_ORDER_SUBTOTAL_CASHBACK,
        'status' => WalletPromotion::STATUS_ACTIVE,
        'action_type' => WalletPromotion::ACTION_FIXED,
        'reward_value' => '20.0000',
        'grant_validity_days' => 30,
    ]);

    $grantService = app(PromotionGrantService::class);

    $bundle = $grantService->createGrant(
        promotion: $promotion,
        wallet: $wallet,
        eventKey: 'rev:grant:'.uniqid(),
        rewardAmountStr: '20.0000',
        netCreditedAmountStr: '20.0000',
        referenceType: WalletPromotion::class,
        referenceId: $promotion->id
    );

    $grant = $bundle['grant'];

    // Partial reversal: reverse 5 SAR (Remaining: 15, Deficit: 0)
    $deficit1 = $grantService->reverseGrantLot($grant, '5.0000');
    expect($deficit1)->toEqual('0.0000');
    expect((string) $grant->fresh()->remaining_amount)->toEqual('15.0000');

    // Excessive reversal: attempt to reverse 25 SAR (Remaining was 15 -> becomes 0, Deficit: 10)
    $deficit2 = $grantService->reverseGrantLot($grant, '25.0000');
    expect($deficit2)->toEqual('10.0000');
    expect((string) $grant->fresh()->remaining_amount)->toEqual('0.0000');
    expect($grant->fresh()->status)->toBe(WalletPromotionGrant::STATUS_FULLY_CONSUMED);

    // Cash balance is strictly unaffected
    expect((string) $wallet->fresh()->cash_balance)->toEqual('100.0000');
});

test('WalletPromotionOutboxWorker rolls back cleanly on exception during job processing', function () {
    $customerId = DB::table('customers')->insertGetId([
        'first_name' => 'Rollback',
        'last_name' => 'Worker',
        'email' => 'rb_worker_'.uniqid().'@example.com',
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
        'backfill_status' => 'pending_review', // Causes AccountUnderAuditException!
    ]);

    $promotion = WalletPromotion::create([
        'name' => 'Will Fail Due to Audit',
        'type' => WalletPromotion::TYPE_WELCOME_BONUS,
        'status' => WalletPromotion::STATUS_ACTIVE,
        'action_type' => WalletPromotion::ACTION_FIXED,
        'reward_value' => '50.0000',
    ]);

    $job = WalletPromotionOutbox::create([
        'event_type' => 'welcome_bonus',
        'event_key' => 'job:fail:'.uniqid(),
        'aggregate_type' => 'customer',
        'aggregate_id' => $customerId,
        'payload' => [
            'promotion_id' => $promotion->id,
            'wallet_id' => $wallet->id,
        ],
        'status' => WalletPromotionOutbox::STATUS_PENDING,
        'attempts' => 0,
    ]);

    $worker = app(WalletPromotionOutboxWorker::class);
    $worker->runOnce(batchSize: 10, leaseSeconds: 60, workerId: 'test-fail-worker');

    $freshJob = $job->fresh();
    // Failed attempt recorded
    expect($freshJob->last_error)->toContain('under audit review');
    expect($freshJob->attempts)->toBe(1);

    // Database state completely rolled back
    expect((string) $wallet->fresh()->promo_balance)->toEqual('0.0000');
    expect(WalletPromotionGrant::where('wallet_id', $wallet->id)->count())->toBe(0);
    expect(WalletTransaction::where('wallet_id', $wallet->id)->count())->toBe(0);
});

test('Re-running worker over completed jobs does not duplicate customer balance', function () {
    $customerId = DB::table('customers')->insertGetId([
        'first_name' => 'Repeat',
        'last_name' => 'Worker',
        'email' => 'repeat_'.uniqid().'@example.com',
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

    $promotion = WalletPromotion::create([
        'name' => 'Single Reward 20',
        'type' => WalletPromotion::TYPE_WELCOME_BONUS,
        'status' => WalletPromotion::STATUS_ACTIVE,
        'action_type' => WalletPromotion::ACTION_FIXED,
        'reward_value' => '20.0000',
    ]);

    $eventKey = 'single:worker:'.uniqid();

    $job = WalletPromotionOutbox::create([
        'event_type' => 'welcome_bonus',
        'event_key' => $eventKey,
        'aggregate_type' => 'customer',
        'aggregate_id' => $customerId,
        'payload' => [
            'promotion_id' => $promotion->id,
            'wallet_id' => $wallet->id,
        ],
        'status' => WalletPromotionOutbox::STATUS_PENDING,
        'attempts' => 0,
    ]);

    $worker = app(WalletPromotionOutboxWorker::class);

    // Pass 1: processes job
    $count1 = $worker->runOnce(batchSize: 10, leaseSeconds: 60);
    expect($count1)->toBe(1);
    expect((string) $wallet->fresh()->promo_balance)->toEqual('20.0000');

    // Pass 2: worker runs again, job is already completed so 0 jobs claimed/processed
    $count2 = $worker->runOnce(batchSize: 10, leaseSeconds: 60);
    expect($count2)->toBe(0);

    // If job were forced to process again manually:
    $worker->processJob($job->fresh());
    expect((string) $wallet->fresh()->promo_balance)->toEqual('20.0000'); // Still 20!

    expect(WalletPromotionGrant::where('wallet_id', $wallet->id)->count())->toBe(1);
});
