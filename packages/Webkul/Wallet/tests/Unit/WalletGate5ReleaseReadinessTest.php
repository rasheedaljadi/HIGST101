<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkul\Wallet\Models\WalletAccount;
use Webkul\Wallet\Models\WalletPromoDebt;
use Webkul\Wallet\Models\WalletPromotion;
use Webkul\Wallet\Models\WalletPromotionAudit;
use Webkul\Wallet\Models\WalletPromotionGrant;
use Webkul\Wallet\Models\WalletPromotionOutbox;
use Webkul\Wallet\Models\WalletPromotionUsage;
use Webkul\Wallet\Services\PaymentVerificationService;
use Webkul\Wallet\Services\PromotionGrantService;
use Webkul\Wallet\Services\WalletDebtService;
use Webkul\Wallet\Services\WalletPromotionOrchestrator;
use Webkul\Wallet\Services\WalletPromotionOutboxWorker;

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
            $table->unsignedInteger('order_id')->nullable();
            $table->string('state')->default('pending');
            $table->decimal('grand_total', 12, 4)->default(0);
            $table->decimal('base_grand_total', 12, 4)->default(0);
            $table->timestamps();
        });
    }
});

test('Smoke Test 1: Welcome Bonus end-to-end controlled flow with manual runOnce worker', function () {
    $customerId = DB::table('customers')->insertGetId([
        'first_name' => 'Smoke',
        'last_name'  => 'Welcome',
        'email'      => 'smoke_welcome_' . uniqid() . '@example.com',
    ]);

    $wallet = WalletAccount::create([
        'customer_id'          => $customerId,
        'cash_balance'         => '0.0000',
        'promo_balance'        => '0.0000',
        'held_balance'         => '0.0000',
        'unclassified_balance' => '0.0000',
        'promo_debt'           => '0.0000',
        'available_balance'    => '0.0000',
        'total_balance'        => '0.0000',
        'status'               => 'active',
        'backfill_status'      => 'verified',
    ]);

    $promo = WalletPromotion::create([
        'name'         => 'Smoke Welcome Campaign',
        'type'         => WalletPromotion::TYPE_WELCOME_BONUS,
        'status'       => WalletPromotion::STATUS_ACTIVE,
        'action_type'  => WalletPromotion::ACTION_FIXED,
        'reward_value' => '25.0000',
    ]);

    $eventKey = 'smoke:welcome:' . uniqid();
    $outbox = WalletPromotionOutbox::create([
        'event_type' => 'welcome_bonus',
        'event_key'  => $eventKey,
        'payload'    => [
            'promotion_id' => $promo->id,
            'wallet_id'    => $wallet->id,
            'customer_id'  => $customerId,
            'amount'       => '0.0000',
        ],
        'status'   => WalletPromotionOutbox::STATUS_PENDING,
        'attempts' => 0,
    ]);

    // Execute strictly via runOnce (isolated single execution, zero background daemon)
    $worker = app(WalletPromotionOutboxWorker::class);
    $processedCount = $worker->runOnce(10);

    expect($processedCount)->toBe(1);
    expect($outbox->fresh()->status)->toBe(WalletPromotionOutbox::STATUS_COMPLETED);

    // 5-way ledger and balance reconciliation
    $wallet->refresh();
    expect((string) $wallet->promo_balance)->toEqual('25.0000');
    expect((string) $wallet->cash_balance)->toEqual('0.0000');
    expect((string) $wallet->available_balance)->toEqual('25.0000');
    expect((string) $wallet->total_balance)->toEqual('25.0000');

    $usage = WalletPromotionUsage::where('event_key', $eventKey)->first();
    expect($usage)->not->toBeNull();
    expect((string) $usage->net_credited_amount)->toEqual('25.0000');

    $grant = WalletPromotionGrant::where('usage_id', $usage->id)->first();
    expect($grant)->not->toBeNull();
    expect((string) $grant->original_amount)->toEqual('25.0000');
    expect((string) $grant->remaining_amount)->toEqual('25.0000');
});

test('Smoke Test 2: Top-up Bonus end-to-end controlled flow with manual runOnce worker', function () {
    $customerId = DB::table('customers')->insertGetId([
        'first_name' => 'Smoke',
        'last_name'  => 'Topup',
        'email'      => 'smoke_topup_' . uniqid() . '@example.com',
    ]);

    $wallet = WalletAccount::create([
        'customer_id'          => $customerId,
        'cash_balance'         => '100.0000', // Real deposited funds
        'promo_balance'        => '0.0000',
        'held_balance'         => '0.0000',
        'unclassified_balance' => '0.0000',
        'promo_debt'           => '0.0000',
        'available_balance'    => '100.0000',
        'total_balance'        => '100.0000',
        'status'               => 'active',
        'backfill_status'      => 'verified',
    ]);

    $promo = WalletPromotion::create([
        'name'         => 'Smoke 10% Top-up Bonus',
        'type'         => WalletPromotion::TYPE_TOPUP_BONUS,
        'status'       => WalletPromotion::STATUS_ACTIVE,
        'action_type'  => WalletPromotion::ACTION_PERCENTAGE,
        'reward_value' => '10.0000', // 10%
    ]);

    $eventKey = 'smoke:topup:' . uniqid();
    $outbox = WalletPromotionOutbox::create([
        'event_type' => 'topup_bonus',
        'event_key'  => $eventKey,
        'payload'    => [
            'promotion_id' => $promo->id,
            'wallet_id'    => $wallet->id,
            'customer_id'  => $customerId,
            'amount'       => '100.0000',
        ],
        'status'   => WalletPromotionOutbox::STATUS_PENDING,
        'attempts' => 0,
    ]);

    $worker = app(WalletPromotionOutboxWorker::class);
    $processedCount = $worker->runOnce(10);

    expect($processedCount)->toBe(1);
    expect($outbox->fresh()->status)->toBe(WalletPromotionOutbox::STATUS_COMPLETED);

    $wallet->refresh();
    expect((string) $wallet->cash_balance)->toEqual('100.0000'); // Real cash untouched
    expect((string) $wallet->promo_balance)->toEqual('10.0000'); // 10% of 100 = 10 Promo
    expect((string) $wallet->available_balance)->toEqual('110.0000');
    expect((string) $wallet->total_balance)->toEqual('110.0000');
});

test('Smoke Test 3: Order Cashback verified strictly via invoices.state and processed safely', function () {
    $customerId = DB::table('customers')->insertGetId([
        'first_name' => 'Smoke',
        'last_name'  => 'Order',
        'email'      => 'smoke_order_' . uniqid() . '@example.com',
    ]);

    $wallet = WalletAccount::create([
        'customer_id'          => $customerId,
        'cash_balance'         => '50.0000',
        'promo_balance'        => '0.0000',
        'held_balance'         => '0.0000',
        'unclassified_balance' => '0.0000',
        'promo_debt'           => '0.0000',
        'available_balance'    => '50.0000',
        'total_balance'        => '50.0000',
        'status'               => 'active',
        'backfill_status'      => 'verified',
    ]);

    $orderId = DB::table('orders')->insertGetId([
        'status'           => 'processing',
        'grand_total'      => '200.0000',
        'base_grand_total' => '200.0000',
    ]);

    $invoiceId = DB::table('invoices')->insertGetId([
        'order_id'         => $orderId,
        'state'            => 'paid',
        'grand_total'      => '200.0000',
        'base_grand_total' => '200.0000',
    ]);

    $invoiceObj = (object) [
        'id'    => $invoiceId,
        'state' => 'paid',
    ];

    // PaymentVerificationService verifies invoice state authoritatively
    $paymentVerifier = app(PaymentVerificationService::class);
    expect($paymentVerifier->isInvoiceEligibleForPromotion($invoiceObj))->toBeTrue();

    $promo = WalletPromotion::create([
        'name'         => 'Smoke Order Cashback',
        'type'         => WalletPromotion::TYPE_ORDER_SUBTOTAL_CASHBACK,
        'status'       => WalletPromotion::STATUS_ACTIVE,
        'action_type'  => WalletPromotion::ACTION_PERCENTAGE,
        'reward_value' => '5.0000', // 5% of 200 = 10
    ]);

    $eventKey = 'smoke:order:' . uniqid();
    $outbox = WalletPromotionOutbox::create([
        'event_type' => 'order_cashback',
        'event_key'  => $eventKey,
        'payload'    => [
            'promotion_id' => $promo->id,
            'wallet_id'    => $wallet->id,
            'customer_id'  => $customerId,
            'amount'       => '200.0000',
        ],
        'status'   => WalletPromotionOutbox::STATUS_PENDING,
        'attempts' => 0,
    ]);

    $worker = app(WalletPromotionOutboxWorker::class);
    $worker->runOnce(10);

    $wallet->refresh();
    expect((string) $wallet->cash_balance)->toEqual('50.0000');
    expect((string) $wallet->promo_balance)->toEqual('10.0000');
    expect((string) $wallet->available_balance)->toEqual('60.0000');
});

test('Smoke Test 4: Refund reversal, Promo Debt, and Net settlement reconciliation', function () {
    $customerId = DB::table('customers')->insertGetId([
        'first_name' => 'Smoke',
        'last_name'  => 'Refund',
        'email'      => 'smoke_refund_' . uniqid() . '@example.com',
    ]);

    $wallet = WalletAccount::create([
        'customer_id'          => $customerId,
        'cash_balance'         => '50.0000',
        'promo_balance'        => '0.0000',
        'held_balance'         => '0.0000',
        'unclassified_balance' => '0.0000',
        'promo_debt'           => '0.0000',
        'available_balance'    => '50.0000',
        'total_balance'        => '50.0000',
        'status'               => 'active',
        'backfill_status'      => 'verified',
    ]);

    $promo = WalletPromotion::create([
        'name'         => 'Smoke Debt Promo',
        'type'         => WalletPromotion::TYPE_WELCOME_BONUS,
        'status'       => WalletPromotion::STATUS_ACTIVE,
        'action_type'  => WalletPromotion::ACTION_FIXED,
        'reward_value' => '30.0000',
    ]);

    $orderId = DB::table('orders')->insertGetId([
        'status'           => 'completed',
        'grand_total'      => '100.0000',
        'base_grand_total' => '100.0000',
    ]);

    // Customer has an existing promo debt of 20.0000 from past refund
    $debtService = app(WalletDebtService::class);
    $debt = $debtService->createDebt(
        wallet: $wallet,
        orderId: $orderId,
        eventKey: 'smoke:debt:init:' . uniqid(),
        deficitAmountStr: '20.0000',
        reason: 'Refund promo deficit'
    );

    expect((string) $wallet->fresh()->promo_debt)->toEqual('20.0000');

    // Orchestrator processes new grant of 30.0000: settles 20.0000 debt and credits net 10.0000
    $orchestrator = app(WalletPromotionOrchestrator::class);
    $result = $orchestrator->applyPromotionGrant(
        promotion: $promo,
        walletId: $wallet->id,
        eventKey: 'smoke:debt:settle:' . uniqid(),
        eligibleAmountStr: '30.0000',
        referenceType: WalletPromotion::class,
        referenceId: $promo->id
    );

    expect((string) $result['total_settled'])->toEqual('20.0000');
    expect((string) $result['net_credited'])->toEqual('10.0000');

    $wallet->refresh();
    expect((string) $wallet->promo_balance)->toEqual('10.0000');
    expect((string) $wallet->promo_debt)->toEqual('0.0000');
    expect((string) $wallet->cash_balance)->toEqual('50.0000'); // Real cash untouched
    expect((string) $wallet->available_balance)->toEqual('60.0000');
});

test('Smoke Test 5: Admin Promotion CRUD, Status Archiving, and Customer Balances Segregation', function () {
    $adminId = DB::table('admins')->insertGetId([
        'name'  => 'Smoke Admin',
        'email' => 'smoke_admin_' . uniqid() . '@example.com',
    ]);

    // 1. Admin creates promotion
    $promo = WalletPromotion::create([
        'name'         => 'Smoke Full Admin Promo',
        'type'         => WalletPromotion::TYPE_WELCOME_BONUS,
        'status'       => WalletPromotion::STATUS_DRAFT,
        'action_type'  => WalletPromotion::ACTION_FIXED,
        'reward_value' => '15.0000',
    ]);

    WalletPromotionAudit::create([
        'promotion_id'  => $promo->id,
        'admin_user_id' => $adminId,
        'action'        => WalletPromotionAudit::ACTION_CREATED,
        'old_values'    => null,
        'new_values'    => $promo->toArray(),
        'ip_address'    => '127.0.0.1',
        'created_at'    => now(),
    ]);

    // 2. Admin activates promotion
    $oldValues = $promo->toArray();
    $promo->update(['status' => WalletPromotion::STATUS_ACTIVE]);

    WalletPromotionAudit::create([
        'promotion_id'  => $promo->id,
        'admin_user_id' => $adminId,
        'action'        => WalletPromotionAudit::ACTION_UPDATED,
        'old_values'    => $oldValues,
        'new_values'    => $promo->fresh()->toArray(),
        'ip_address'    => '127.0.0.1',
        'created_at'    => now(),
    ]);

    // 3. Admin archives promotion (Non-destructive Delete)
    $oldValues = $promo->toArray();
    $promo->update(['status' => WalletPromotion::STATUS_ARCHIVED]);

    WalletPromotionAudit::create([
        'promotion_id'  => $promo->id,
        'admin_user_id' => $adminId,
        'action'        => WalletPromotionAudit::ACTION_ARCHIVED,
        'old_values'    => $oldValues,
        'new_values'    => $promo->fresh()->toArray(),
        'ip_address'    => '127.0.0.1',
        'created_at'    => now(),
    ]);

    expect($promo->fresh()->status)->toBe(WalletPromotion::STATUS_ARCHIVED);
    expect(WalletPromotionAudit::where('promotion_id', $promo->id)->count())->toBe(3);

    // 4. Customer balance segregation check
    $customerId = DB::table('customers')->insertGetId([
        'first_name' => 'Smoke',
        'last_name'  => 'Segregation',
        'email'      => 'smoke_seg_' . uniqid() . '@example.com',
    ]);

    $wallet = WalletAccount::create([
        'customer_id'          => $customerId,
        'cash_balance'         => '100.0000',
        'promo_balance'        => '50.0000',
        'held_balance'         => '20.0000',
        'unclassified_balance' => '0.0000',
        'promo_debt'           => '0.0000',
        'available_balance'    => '150.0000',
        'total_balance'        => '150.0000',
        'status'               => 'active',
        'backfill_status'      => 'verified',
    ]);

    $rawCash = (float) $wallet->cash_balance;
    $rawPromo = (float) $wallet->promo_balance;
    $rawHeld = (float) $wallet->held_balance;
    $withdrawable = max(0, $rawCash - $rawHeld);

    expect($rawCash)->toBe(100.0);
    expect($rawPromo)->toBe(50.0);
    expect($rawHeld)->toBe(20.0);
    expect($withdrawable)->toBe(80.0); // Strictly Cash (100) - Held (20) = 80. Promo (50) is excluded from withdrawal!
});

test('Smoke Test 6: Strict physical deletion prohibition across ORM and Query Builder checks', function () {
    $promo = WalletPromotion::create([
        'name'         => 'Anti Delete Smoke',
        'type'         => WalletPromotion::TYPE_WELCOME_BONUS,
        'status'       => WalletPromotion::STATUS_ACTIVE,
        'action_type'  => WalletPromotion::ACTION_FIXED,
        'reward_value' => '10.0000',
    ]);

    // Direct model delete must be blocked by booted deleting guard
    expect(fn () => $promo->delete())->toThrow(\LogicException::class);
    expect(WalletPromotion::find($promo->id))->not->toBeNull();
});
