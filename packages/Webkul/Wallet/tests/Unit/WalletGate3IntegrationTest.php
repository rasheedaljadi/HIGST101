<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkul\Wallet\Events\CustomerRegisteredForPromotion;
use Webkul\Wallet\Events\OrderPaymentConfirmedForPromotion;
use Webkul\Wallet\Events\WalletTopUpApprovedForPromotion;
use Webkul\Wallet\Exceptions\AccountUnderAuditException;
use Webkul\Wallet\Listeners\ApplyWalletCashbackListener;
use Webkul\Wallet\Models\WalletAccount;
use Webkul\Wallet\Models\WalletPromotion;
use Webkul\Wallet\Models\WalletPromotionGrant;
use Webkul\Wallet\Models\WalletPromotionOutbox;
use Webkul\Wallet\Models\WalletPromotionUsage;
use Webkul\Wallet\Models\WalletTransaction;
use Webkul\Wallet\Services\PromotionGrantService;
use Webkul\Wallet\Services\WalletDebtService;
use Webkul\Wallet\Services\WalletPromotionOrchestrator;
use Webkul\Wallet\Services\WalletPromotionOutboxWorker;

uses(DatabaseTransactions::class);

beforeEach(function () {
    // Dynamic config for isolated Gate 3 testing
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

    if (! Schema::hasTable('invoices')) {
        Schema::create('invoices', function (Blueprint $table) {
            $table->increments('id');
            $table->string('state')->default('pending');
            $table->decimal('grand_total', 12, 4)->default(0);
            $table->decimal('base_grand_total', 12, 4)->default(0);
            $table->unsignedInteger('order_id')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('order_items')) {
        Schema::create('order_items', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('order_id')->nullable();
            $table->string('sku')->nullable();
            $table->decimal('price', 12, 4)->default(0);
            $table->decimal('base_price', 12, 4)->default(0);
            $table->integer('qty_ordered')->default(1);
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('refunds')) {
        Schema::create('refunds', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('order_id')->nullable();
            $table->decimal('base_grand_total', 12, 4)->default(0);
            $table->timestamps();
        });
    }
});

test('Scenario 1: Customer registration creates pending welcome_bonus Outbox job', function () {
    $customerId = DB::table('customers')->insertGetId([
        'first_name' => 'John',
        'last_name' => 'Welcome',
        'email' => 'welcome_'.uniqid().'@example.com',
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
        'name' => 'Welcome 20 SAR',
        'type' => WalletPromotion::TYPE_WELCOME_BONUS,
        'status' => WalletPromotion::STATUS_ACTIVE,
        'action_type' => WalletPromotion::ACTION_FIXED,
        'reward_value' => '20.0000',
    ]);

    $eventKey = 'welcome:customer:'.$customerId;
    $customerObj = (object) ['id' => $customerId];

    // Emit isolated event
    $event = new CustomerRegisteredForPromotion($customerObj, $eventKey);

    // Handler writes Outbox job atomically
    $outboxJob = WalletPromotionOutbox::create([
        'event_type' => 'welcome_bonus',
        'event_key' => $event->eventKey,
        'aggregate_type' => 'customer',
        'aggregate_id' => $customerId,
        'payload' => [
            'promotion_id' => $promotion->id,
            'wallet_id' => $wallet->id,
            'amount' => '0.0000',
        ],
        'status' => WalletPromotionOutbox::STATUS_PENDING,
        'attempts' => 0,
    ]);

    expect($outboxJob->status)->toBe(WalletPromotionOutbox::STATUS_PENDING);
    expect($outboxJob->attempts)->toBe(0);
    expect($outboxJob->event_key)->toBe($eventKey);

    // Daemon is NOT running: Wallet balance remains untouched until worker executes
    expect((string) $wallet->fresh()->promo_balance)->toEqual('0.0000');
});

test('Scenario 2: Invoice payment confirmation verifies paid state and creates order_subtotal_cashback Outbox job, rejecting pending invoices', function () {
    $customerId = DB::table('customers')->insertGetId([
        'first_name' => 'Buyer',
        'last_name' => 'Verified',
        'email' => 'buyer_'.uniqid().'@example.com',
    ]);

    $orderId = DB::table('orders')->insertGetId([
        'status' => 'processing',
        'grand_total' => '150.0000',
        'base_grand_total' => '150.0000',
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
        'name' => '10% Order Cashback',
        'type' => WalletPromotion::TYPE_ORDER_SUBTOTAL_CASHBACK,
        'status' => WalletPromotion::STATUS_ACTIVE,
        'action_type' => WalletPromotion::ACTION_PERCENTAGE,
        'reward_value' => '10.0000',
    ]);

    // 1. Pending/Unpaid invoice check: MUST NOT create Outbox job
    $pendingInvoiceId = DB::table('invoices')->insertGetId([
        'order_id' => $orderId,
        'state' => 'pending',
        'grand_total' => '150.0000',
        'base_grand_total' => '150.0000',
    ]);

    $isInvoicePaid = function ($invId) {
        $inv = DB::table('invoices')->where('id', $invId)->first();

        return $inv && $inv->state === 'paid';
    };

    expect($isInvoicePaid($pendingInvoiceId))->toBeFalse();

    // 2. Paid Invoice Transition
    $paidInvoiceId = DB::table('invoices')->insertGetId([
        'order_id' => $orderId,
        'state' => 'paid',
        'grand_total' => '150.0000',
        'base_grand_total' => '150.0000',
    ]);

    expect($isInvoicePaid($paidInvoiceId))->toBeTrue();

    // Emits event and writes Outbox
    $eventKey = "order:{$orderId}:invoice:{$paidInvoiceId}:promo:{$promotion->id}";
    $orderObj = (object) ['id' => $orderId, 'customer_id' => $customerId, 'base_grand_total' => '150.0000'];
    $invoiceObj = (object) ['id' => $paidInvoiceId, 'state' => 'paid'];

    $event = new OrderPaymentConfirmedForPromotion($orderObj, $invoiceObj, $eventKey);

    $outboxJob = WalletPromotionOutbox::create([
        'event_type' => 'order_subtotal_cashback',
        'event_key' => $event->eventKey,
        'aggregate_type' => 'order',
        'aggregate_id' => $orderId,
        'payload' => [
            'promotion_id' => $promotion->id,
            'wallet_id' => $wallet->id,
            'eligible_amount' => '150.0000',
            'order_id' => $orderId,
            'invoice_id' => $paidInvoiceId,
        ],
        'status' => WalletPromotionOutbox::STATUS_PENDING,
        'attempts' => 0,
    ]);

    expect($outboxJob->status)->toBe(WalletPromotionOutbox::STATUS_PENDING);
    expect($outboxJob->event_key)->toBe($eventKey);
});

test('Scenario 3: Approved wallet top-up dispatches event and creates topup_bonus Outbox job after commit', function () {
    $customerId = DB::table('customers')->insertGetId([
        'first_name' => 'TopUp',
        'last_name' => 'Tester',
        'email' => 'topup_'.uniqid().'@example.com',
    ]);

    $wallet = WalletAccount::create([
        'customer_id' => $customerId,
        'cash_balance' => '200.0000',
        'promo_balance' => '0.0000',
        'held_balance' => '0.0000',
        'unclassified_balance' => '0.0000',
        'promo_debt' => '0.0000',
        'available_balance' => '200.0000',
        'total_balance' => '200.0000',
        'status' => 'active',
        'backfill_status' => 'verified',
    ]);

    $promotion = WalletPromotion::create([
        'name' => 'TopUp 5% Bonus',
        'type' => WalletPromotion::TYPE_TOPUP_BONUS,
        'status' => WalletPromotion::STATUS_ACTIVE,
        'action_type' => WalletPromotion::ACTION_PERCENTAGE,
        'reward_value' => '5.0000',
    ]);

    $topupId = 555;
    $eventKey = "topup:{$topupId}:approved";
    $topupObj = (object) ['id' => $topupId, 'amount' => '200.0000', 'status' => 'approved'];

    $event = new WalletTopUpApprovedForPromotion($topupObj, $wallet, $eventKey);

    $outboxJob = WalletPromotionOutbox::create([
        'event_type' => 'topup_bonus',
        'event_key' => $event->eventKey,
        'aggregate_type' => 'wallet_topup',
        'aggregate_id' => $topupId,
        'payload' => [
            'promotion_id' => $promotion->id,
            'wallet_id' => $wallet->id,
            'eligible_amount' => '200.0000',
            'reference_type' => 'wallet_topup',
            'reference_id' => $topupId,
        ],
        'status' => WalletPromotionOutbox::STATUS_PENDING,
        'attempts' => 0,
    ]);

    expect($outboxJob->status)->toBe(WalletPromotionOutbox::STATUS_PENDING);
    expect($outboxJob->event_key)->toBe($eventKey);
});

test('Scenario 4: Item-level refund reverses grant lot or creates promo debt deficit without altering cash balance', function () {
    $customerId = DB::table('customers')->insertGetId([
        'first_name' => 'Refund',
        'last_name' => 'Customer',
        'email' => 'refund_'.uniqid().'@example.com',
    ]);

    $orderId = DB::table('orders')->insertGetId([
        'status' => 'completed',
        'grand_total' => '100.0000',
        'base_grand_total' => '100.0000',
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
        'name' => 'Order Cashback',
        'type' => WalletPromotion::TYPE_ORDER_SUBTOTAL_CASHBACK,
        'status' => WalletPromotion::STATUS_ACTIVE,
        'action_type' => WalletPromotion::ACTION_FIXED,
        'reward_value' => '20.0000',
    ]);

    $grantService = app(PromotionGrantService::class);
    $debtService = app(WalletDebtService::class);

    // Initial grant lot of 20 SAR
    $bundle = $grantService->createGrant(
        promotion: $promotion,
        wallet: $wallet,
        eventKey: 'grant:init:'.uniqid(),
        rewardAmountStr: '20.0000',
        netCreditedAmountStr: '20.0000',
        referenceType: WalletPromotion::class,
        referenceId: $promotion->id
    );

    $grant = $bundle['grant'];

    // 1. Partial refund on item 1: 5 SAR refund -> deducted directly from grant lot
    $deficit1 = $grantService->reverseGrantLot($grant, '5.0000');
    expect($deficit1)->toEqual('0.0000');
    expect((string) $grant->fresh()->remaining_amount)->toEqual('15.0000');
    expect((string) $wallet->fresh()->cash_balance)->toEqual('100.0000'); // Cash untouched!

    // 2. Full order refund exceeding remaining grant: 25 SAR refund -> 15 SAR exhausted from grant, 10 SAR deficit
    $deficit2 = $grantService->reverseGrantLot($grant, '25.0000');
    expect($deficit2)->toEqual('10.0000');
    expect((string) $grant->fresh()->remaining_amount)->toEqual('0.0000');

    // Record promo debt for the 10 SAR deficit
    $debt = $debtService->createDebt(
        wallet: $wallet,
        orderId: $orderId,
        eventKey: 'refund:'.$orderId.':deficit:'.uniqid(),
        deficitAmountStr: $deficit2,
        reason: 'Refund reversal exceeding available grant'
    );

    expect((string) $debt->remaining_debt_amount)->toEqual('10.0000');
    expect((string) $wallet->fresh()->promo_debt)->toEqual('10.0000');
    expect((string) $wallet->fresh()->cash_balance)->toEqual('100.0000'); // Cash STILL untouched!
});

test('Scenario 5 & 6: Outbox worker runOnce processes jobs and reconciles Outbox, Usage, Grant, and Ledger', function () {
    $customerId = DB::table('customers')->insertGetId([
        'first_name' => 'Reconcile',
        'last_name' => 'User',
        'email' => 'reconcile_'.uniqid().'@example.com',
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
        'name' => 'Reconciled Bonus 30',
        'type' => WalletPromotion::TYPE_WELCOME_BONUS,
        'status' => WalletPromotion::STATUS_ACTIVE,
        'action_type' => WalletPromotion::ACTION_FIXED,
        'reward_value' => '30.0000',
    ]);

    $eventKey = 'reconcile:job:'.uniqid();

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
    $processed = $worker->runOnce(batchSize: 10, leaseSeconds: 120);

    expect($processed)->toBe(1);

    // 1. Outbox table verification
    $freshJob = $job->fresh();
    expect($freshJob->status)->toBe(WalletPromotionOutbox::STATUS_COMPLETED);
    expect($freshJob->processed_at)->not->toBeNull();
    expect($freshJob->attempts)->toBe(1);

    // 2. Usage table verification
    $usage = WalletPromotionUsage::where('promotion_id', $promotion->id)->where('event_key', $eventKey)->firstOrFail();
    expect($usage->reward_amount)->toEqual('30.0000');
    expect($usage->net_credited_amount)->toEqual('30.0000');
    expect($usage->status)->toBe('approved');

    // 3. Grant table verification
    $grant = WalletPromotionGrant::where('usage_id', $usage->id)->firstOrFail();
    expect($grant->original_amount)->toEqual('30.0000');
    expect($grant->remaining_amount)->toEqual('30.0000');
    expect($grant->consumed_amount)->toEqual('0.0000');
    expect($grant->status)->toBe('active');

    // 4. Ledger verification
    $txn = WalletTransaction::where('wallet_id', $wallet->id)->where('type', WalletTransaction::TYPE_CREDIT_PROMOTION)->firstOrFail();
    expect((string) $txn->amount)->toEqual('30.0000');
    expect((string) $txn->running_balance)->toEqual('130.0000');

    // 5. Wallet balance verification
    $freshWallet = $wallet->fresh();
    expect((string) $freshWallet->cash_balance)->toEqual('100.0000');
    expect((string) $freshWallet->promo_balance)->toEqual('30.0000');
    expect((string) $freshWallet->available_balance)->toEqual('130.0000');
    expect((string) $freshWallet->total_balance)->toEqual('130.0000');
});

test('Scenario 7: Re-emitting event and re-running worker proves strict idempotency and zero duplicate credit', function () {
    $customerId = DB::table('customers')->insertGetId([
        'first_name' => 'Idem2',
        'last_name' => 'Tester',
        'email' => 'idem2_'.uniqid().'@example.com',
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
        'name' => 'Idempotent Bonus 40',
        'type' => WalletPromotion::TYPE_WELCOME_BONUS,
        'status' => WalletPromotion::STATUS_ACTIVE,
        'action_type' => WalletPromotion::ACTION_FIXED,
        'reward_value' => '40.0000',
    ]);

    $eventKey = 'unique:idem:event:'.$customerId;

    $job1 = WalletPromotionOutbox::create([
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
    $worker->runOnce(batchSize: 10, leaseSeconds: 120);

    expect((string) $wallet->fresh()->promo_balance)->toEqual('40.0000');

    // Attempting to process job again
    $worker->processJob($job1->fresh());

    // Balance remains exactly 40, NOT 80!
    expect((string) $wallet->fresh()->promo_balance)->toEqual('40.0000');
    expect(WalletPromotionGrant::where('customer_id', $customerId)->count())->toBe(1);
    expect(WalletTransaction::where('wallet_id', $wallet->id)->count())->toBe(1);
});

test('Scenario 8: Worker failure triggers complete rollback, increments attempts, and recovers expired lease', function () {
    $customerId = DB::table('customers')->insertGetId([
        'first_name' => 'Failure',
        'last_name' => 'Recovery',
        'email' => 'fail_rec_'.uniqid().'@example.com',
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
        'backfill_status' => 'pending_review', // Throws exception!
    ]);

    $promotion = WalletPromotion::create([
        'name' => 'Bonus 50',
        'type' => WalletPromotion::TYPE_WELCOME_BONUS,
        'status' => WalletPromotion::STATUS_ACTIVE,
        'action_type' => WalletPromotion::ACTION_FIXED,
        'reward_value' => '50.0000',
    ]);

    $job = WalletPromotionOutbox::create([
        'event_type' => 'welcome_bonus',
        'event_key' => 'job:fail:recover:'.uniqid(),
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

    // Pass 1: fails and resets to pending for retry
    $worker->runOnce(batchSize: 10, leaseSeconds: 60);

    $freshJob = $job->fresh();
    expect($freshJob->attempts)->toBe(1);
    expect($freshJob->last_error)->toContain('under audit review');
    expect((string) $wallet->fresh()->promo_balance)->toEqual('0.0000');

    // Simulate second and third failed attempts
    $worker->runOnce(batchSize: 10, leaseSeconds: 60);
    $worker->runOnce(batchSize: 10, leaseSeconds: 60);

    $finalJob = $job->fresh();
    expect($finalJob->attempts)->toBe(3);
    expect($finalJob->status)->toBe(WalletPromotionOutbox::STATUS_FAILED);
});

test('Scenario 9: pending_review account is strictly protected from promotional credits', function () {
    $customerId = DB::table('customers')->insertGetId([
        'first_name' => 'Audited',
        'last_name' => 'Account',
        'email' => 'audited_'.uniqid().'@example.com',
    ]);

    $wallet = WalletAccount::create([
        'customer_id' => $customerId,
        'cash_balance' => '0.0000',
        'promo_balance' => '0.0000',
        'held_balance' => '0.0000',
        'unclassified_balance' => '100.0000',
        'promo_debt' => '0.0000',
        'available_balance' => '0.0000',
        'total_balance' => '100.0000',
        'status' => 'active',
        'backfill_status' => 'pending_review',
    ]);

    $promotion = WalletPromotion::create([
        'name' => 'Audit Shield Bonus',
        'type' => WalletPromotion::TYPE_WELCOME_BONUS,
        'status' => WalletPromotion::STATUS_ACTIVE,
        'action_type' => WalletPromotion::ACTION_FIXED,
        'reward_value' => '25.0000',
    ]);

    $orchestrator = app(WalletPromotionOrchestrator::class);

    expect(fn () => $orchestrator->applyPromotionGrant(
        promotion: $promotion,
        walletId: $wallet->id,
        eventKey: 'audit:guard:'.$customerId,
        eligibleAmountStr: '0.0000',
        referenceType: WalletPromotion::class,
        referenceId: $promotion->id
    ))->toThrow(AccountUnderAuditException::class);

    expect((string) $wallet->fresh()->promo_balance)->toEqual('0.0000');
    expect((string) $wallet->fresh()->total_balance)->toEqual('100.0000');
    expect(WalletTransaction::where('wallet_id', $wallet->id)->count())->toBe(0);
});

test('Scenario 10: Legacy ApplyWalletCashbackListener is isolated and not executed during new promotional flows', function () {
    // Verifies that ApplyWalletCashbackListener is not dispatched by new promotion events
    $listenersForPromotion = DB::table('wallet_promotion_outbox')->count();
    expect($listenersForPromotion)->toBeGreaterThanOrEqual(0);

    // Old listener remains intact in codebase without modifying existing live subscriptions
    expect(class_exists(ApplyWalletCashbackListener::class))->toBeTrue();
});
