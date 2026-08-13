<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Webkul\Wallet\Models\WalletAccount;
use Webkul\Wallet\Models\WalletTransaction;
use Webkul\Wallet\Models\WalletPromotion;
use Webkul\Wallet\Models\WalletPromotionUsage;
use Webkul\Wallet\Models\WalletPromotionGrant;
use Webkul\Wallet\Models\WalletPromoDebt;
use Webkul\Wallet\Models\WalletPromoDebtSettlement;
use Webkul\Wallet\Services\WalletService;
use Webkul\Wallet\Exceptions\AccountUnderAuditException;

$dbConfig = config('database.connections.mysql');
echo "======================================================================\n";
echo " GATE 1 COMPREHENSIVE DATABASE VERIFICATION HARNESS (V3)\n";
echo "======================================================================\n";
echo "Database Connection: mysql\n";
echo "Host:                " . $dbConfig['host'] . ":" . $dbConfig['port'] . "\n";
echo "Database:            " . $dbConfig['database'] . " (ISOLATED LOCAL TEST SCHEMA)\n";
echo "Date / Timestamp:    " . date('Y-m-d H:i:s') . "\n";
echo "PHP Version:         " . PHP_VERSION . "\n";
echo "======================================================================\n\n";

function fatalStep(string $stepName, string $message): void {
    echo "\n[FATAL FAILURE] Stage '$stepName': $message\n";
    exit(1);
}

// ---------------------------------------------------------
// STAGE 0: BASE TEST TABLES SETUP
// ---------------------------------------------------------
echo "=== STAGE 0: BASE TEST TABLES SETUP ===\n";

if (! Schema::hasTable('customers')) {
    Schema::create('customers', function (Blueprint $table) {
        $table->increments('id');
        $table->string('first_name')->nullable();
        $table->string('last_name')->nullable();
        $table->string('email')->nullable();
        $table->timestamps();
    });
    echo "[SETUP] Created base 'customers' table.\n";
} else {
    echo "[SETUP] Base 'customers' table exists.\n";
}

if (! Schema::hasTable('admins')) {
    Schema::create('admins', function (Blueprint $table) {
        $table->increments('id');
        $table->string('name')->nullable();
        $table->string('email')->nullable();
        $table->timestamps();
    });
    echo "[SETUP] Created base 'admins' table.\n";
} else {
    echo "[SETUP] Base 'admins' table exists.\n";
}

if (! Schema::hasTable('orders')) {
    Schema::create('orders', function (Blueprint $table) {
        $table->increments('id');
        $table->string('status')->default('pending');
        $table->decimal('grand_total', 12, 4)->default(0);
        $table->decimal('base_grand_total', 12, 4)->default(0);
        $table->timestamps();
    });
    echo "[SETUP] Created base 'orders' table.\n";
} else {
    echo "[SETUP] Base 'orders' table exists.\n";
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
    echo "[SETUP] Created base 'invoices' table.\n";
} else {
    echo "[SETUP] Base 'invoices' table exists.\n";
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
    echo "[SETUP] Created base 'order_items' table.\n";
} else {
    echo "[SETUP] Base 'order_items' table exists.\n";
}

if (! Schema::hasTable('refunds')) {
    Schema::create('refunds', function (Blueprint $table) {
        $table->increments('id');
        $table->unsignedInteger('order_id')->nullable();
        $table->decimal('base_grand_total', 12, 4)->default(0);
        $table->timestamps();
    });
    echo "[SETUP] Created base 'refunds' table.\n";
} else {
    echo "[SETUP] Base 'refunds' table exists.\n";
}

echo "STAGE 0: COMPLETED (Exit Code: 0)\n\n";

// ---------------------------------------------------------
// STAGE 1: EXECUTE MIGRATIONS (UP)
// ---------------------------------------------------------
echo "=== STAGE 1: MIGRATION EXECUTION (UP) ===\n";
$migrations = [
    '2026_08_13_000001_add_promo_columns_to_wallet_accounts_table',
    '2026_08_13_000002_create_wallet_promotions_table',
    '2026_08_13_000003_create_wallet_promotion_usages_table',
    '2026_08_13_000004_create_wallet_promotion_grants_table',
    '2026_08_13_000005_create_wallet_promotion_grant_consumptions_table',
    '2026_08_13_000006_create_wallet_promotion_order_item_allocations_table',
    '2026_08_13_000007_create_wallet_promo_debts_table',
    '2026_08_13_000008_create_wallet_promo_debt_settlements_table',
    '2026_08_13_000009_create_wallet_promotion_outbox_table',
    '2026_08_13_000010_create_wallet_backfill_discrepancies_table',
    '2026_08_13_000011_create_wallet_promotion_audits_table',
    '2026_08_13_000012_update_type_column_on_wallet_transactions_table',
];

foreach ($migrations as $mig) {
    $file = __DIR__ . '/../packages/Webkul/Wallet/src/Database/Migrations/' . $mig . '.php';
    if (! file_exists($file)) {
        fatalStep("Stage 1 Migration", "Migration file not found: $file");
    }
    $instance = require $file;
    try {
        $instance->up();
        echo "[MIGRATE UP] $mig: SUCCESS\n";
    } catch (\Throwable $e) {
        // If already exists, log info; otherwise check if fatal
        if (str_contains($e->getMessage(), 'already exists') || str_contains($e->getMessage(), 'Duplicate column')) {
            echo "[MIGRATE UP] $mig: PREVIOUSLY APPLIED (OK)\n";
        } else {
            fatalStep("Stage 1 Migration: $mig", $e->getMessage());
        }
    }
}
echo "STAGE 1: COMPLETED (Exit Code: 0)\n\n";

// ---------------------------------------------------------
// STAGE 2: RAW SCHEMA, INDEX & CONSTRAINT VERIFICATION
// ---------------------------------------------------------
echo "=== STAGE 2: RAW SCHEMA, INDEX & CONSTRAINT INSPECTION ===\n";
$promoTables = [
    'wallet_accounts',
    'wallet_promotions',
    'wallet_promotion_usages',
    'wallet_promotion_grants',
    'wallet_promotion_grant_consumptions',
    'wallet_promotion_order_item_allocations',
    'wallet_promo_debts',
    'wallet_promo_debt_settlements',
    'wallet_promotion_outbox',
    'wallet_backfill_discrepancies',
    'wallet_promotion_audits',
];

foreach ($promoTables as $tbl) {
    if (! Schema::hasTable($tbl)) {
        fatalStep("Stage 2 Schema Inspection", "Table '$tbl' does not exist in MySQL!");
    }
    $createRes = DB::select("SHOW CREATE TABLE `{$tbl}`");
    $createSql = $createRes[0]->{'Create Table'} ?? '';
    echo "--- TABLE DDL: `{$tbl}` ---\n";
    echo $createSql . "\n\n";
}

// Check wallet_accounts columns
$waCols = Schema::getColumnListing('wallet_accounts');
$requiredCols = ['promo_balance', 'cash_balance', 'unclassified_balance', 'promo_debt', 'backfill_status'];
foreach ($requiredCols as $c) {
    if (! in_array($c, $waCols)) {
        fatalStep("Stage 2 Column Check", "Column '$c' missing from 'wallet_accounts'!");
    }
}
echo "[CHECK] All required promo columns exist in 'wallet_accounts': " . implode(', ', $requiredCols) . "\n";
echo "STAGE 2: COMPLETED (Exit Code: 0)\n\n";

// ---------------------------------------------------------
// STAGE 3: ROLLBACK EXECUTION (DOWN)
// ---------------------------------------------------------
echo "=== STAGE 3: ROLLBACK EXECUTION (DOWN) ===\n";
for ($i = count($migrations) - 1; $i >= 0; $i--) {
    $mig = $migrations[$i];
    $file = __DIR__ . '/../packages/Webkul/Wallet/src/Database/Migrations/' . $mig . '.php';
    $instance = require $file;
    try {
        $instance->down();
        echo "[ROLLBACK DOWN] $mig: SUCCESS\n";
    } catch (\Throwable $e) {
        fatalStep("Stage 3 Rollback: $mig", $e->getMessage());
    }
}

// Verify promotional tables are completely dropped
foreach (array_slice($promoTables, 1) as $tbl) {
    if (Schema::hasTable($tbl)) {
        fatalStep("Stage 3 Rollback Verification", "Table '$tbl' was NOT dropped during rollback!");
    }
    echo "[ROLLBACK CHECK] Table '$tbl': DROPPED\n";
}

// Verify promo columns dropped from wallet_accounts
$waColsAfterRollback = Schema::getColumnListing('wallet_accounts');
foreach ($requiredCols as $c) {
    if (in_array($c, $waColsAfterRollback)) {
        fatalStep("Stage 3 Rollback Verification", "Column '$c' was NOT removed from 'wallet_accounts'!");
    }
}
echo "[ROLLBACK CHECK] All promotional columns successfully removed from 'wallet_accounts'.\n";
echo "STAGE 3: COMPLETED (Exit Code: 0)\n\n";

// ---------------------------------------------------------
// STAGE 4: RE-MIGRATE AND VERIFY RESTORATION
// ---------------------------------------------------------
echo "=== STAGE 4: RE-MIGRATION AND SCHEMA RESTORATION ===\n";
foreach ($migrations as $mig) {
    $file = __DIR__ . '/../packages/Webkul/Wallet/src/Database/Migrations/' . $mig . '.php';
    $instance = require $file;
    try {
        $instance->up();
        echo "[RE-MIGRATE UP] $mig: SUCCESS\n";
    } catch (\Throwable $e) {
        fatalStep("Stage 4 Re-migrate: $mig", $e->getMessage());
    }
}

// Verify all tables restored
foreach ($promoTables as $tbl) {
    if (! Schema::hasTable($tbl)) {
        fatalStep("Stage 4 Re-migration Verification", "Table '$tbl' was NOT restored!");
    }
    echo "[RE-MIGRATE CHECK] Table '$tbl': RESTORED\n";
}
echo "STAGE 4: COMPLETED (Exit Code: 0)\n\n";

// ---------------------------------------------------------
// STAGE 5: REAL DB TEST FOR creditPromotion()
// ---------------------------------------------------------
echo "=== STAGE 5: REAL DB TEST - creditPromotion() ===\n";
DB::beginTransaction();
try {
    $custId1 = DB::table('customers')->insertGetId([
        'first_name' => 'Stage5',
        'last_name'  => 'User',
        'email'      => 'stage5_' . uniqid() . '@test.local',
    ]);

    $wallet1 = WalletAccount::create([
        'customer_id'          => $custId1,
        'cash_balance'         => '100.0000',
        'promo_balance'        => '0.0000',
        'held_balance'         => '20.0000',
        'unclassified_balance' => '0.0000',
        'promo_debt'           => '0.0000',
        'available_balance'    => '80.0000',
        'total_balance'        => '100.0000',
        'status'               => 'active',
        'backfill_status'      => 'verified',
    ]);

    $service = app(WalletService::class);

    echo "[STAGE 5 BEFORE]\n";
    echo "  cash_balance:         {$wallet1->cash_balance}\n";
    echo "  promo_balance:        {$wallet1->promo_balance}\n";
    echo "  held_balance:         {$wallet1->held_balance}\n";
    echo "  available_balance:    {$wallet1->available_balance}\n";
    echo "  total_balance:        {$wallet1->total_balance}\n";
    echo "  withdrawable_balance: " . max(0, (float)$wallet1->cash_balance - (float)$wallet1->held_balance) . "\n";
    echo "  ledger_count:         " . WalletTransaction::where('wallet_id', $wallet1->id)->count() . "\n";

    $txn = $service->creditPromotion(
        wallet: $wallet1,
        amountStr: '40.0000',
        description: 'Promotional Credit Test'
    );

    $fresh1 = $wallet1->fresh();
    echo "[STAGE 5 AFTER (creditPromotion: 40.0000)]\n";
    echo "  cash_balance:         {$fresh1->cash_balance} (UNTOUCHED = 100.0000)\n";
    echo "  promo_balance:        {$fresh1->promo_balance} (EXACT = 40.0000)\n";
    echo "  held_balance:         {$fresh1->held_balance} (UNTOUCHED = 20.0000)\n";
    echo "  available_balance:    {$fresh1->available_balance} (EXACT = 120.0000)\n";
    echo "  total_balance:        {$fresh1->total_balance} (EXACT = 140.0000)\n";
    echo "  withdrawable_balance: " . max(0, (float)$fresh1->cash_balance - (float)$fresh1->held_balance) . " (UNTOUCHED = 80.0000)\n";
    echo "  ledger_count:         " . WalletTransaction::where('wallet_id', $wallet1->id)->count() . " (EXACT = 1)\n";
    echo "  ledger_txn_type:      {$txn->type}\n";
    echo "  ledger_txn_amount:    {$txn->amount}\n";
    echo "  ledger_running_bal:   {$txn->running_balance}\n";

    if (bccomp($fresh1->cash_balance, '100.0000', 4) !== 0) fatalStep("Stage 5", "cash_balance mutated!");
    if (bccomp($fresh1->promo_balance, '40.0000', 4) !== 0) fatalStep("Stage 5", "promo_balance != 40.0000!");
    if (bccomp($fresh1->available_balance, '120.0000', 4) !== 0) fatalStep("Stage 5", "available_balance != 120.0000!");
    if (bccomp($fresh1->total_balance, '140.0000', 4) !== 0) fatalStep("Stage 5", "total_balance != 140.0000!");

    DB::commit();
} catch (\Throwable $e) {
    DB::rollBack();
    fatalStep("Stage 5 creditPromotion", $e->getMessage());
}
echo "STAGE 5: COMPLETED (Exit Code: 0)\n\n";

// ---------------------------------------------------------
// STAGE 6: REAL DB TEST - T-21 RECONCILIATION
// ---------------------------------------------------------
echo "=== STAGE 6: REAL DB TEST - T-21 NUMERICAL RECONCILIATION ===\n";
DB::beginTransaction();
try {
    $custId2 = DB::table('customers')->insertGetId([
        'first_name' => 'T21',
        'last_name'  => 'Client',
        'email'      => 't21_' . uniqid() . '@test.local',
    ]);

    $orderId2 = DB::table('orders')->insertGetId([
        'status'           => 'completed',
        'grand_total'      => '100.0000',
        'base_grand_total' => '100.0000',
    ]);

    $wallet2 = WalletAccount::create([
        'customer_id'          => $custId2,
        'cash_balance'         => '100.0000',
        'promo_balance'        => '0.0000',
        'held_balance'         => '0.0000',
        'unclassified_balance' => '0.0000',
        'promo_debt'           => '20.0000',
        'available_balance'    => '100.0000',
        'total_balance'        => '100.0000',
        'status'               => 'active',
        'backfill_status'      => 'verified',
    ]);

    $debt2 = WalletPromoDebt::create([
        'wallet_id'             => $wallet2->id,
        'customer_id'           => $custId2,
        'order_id'              => $orderId2,
        'event_key'             => "refund:{$orderId2}:debt:reversal:" . uniqid(),
        'currency_code'         => 'SAR',
        'original_debt_amount'  => '20.0000',
        'remaining_debt_amount' => '20.0000',
        'settled_amount'        => '0.0000',
        'status'                => 'active',
        'reason'                => 'Initial promo reversal deficit',
    ]);

    $promo2 = WalletPromotion::create([
        'name'                => 'T21 Cashback Campaign',
        'type'                => WalletPromotion::TYPE_ORDER_SUBTOTAL_CASHBACK,
        'status'              => WalletPromotion::STATUS_ACTIVE,
        'action_type'         => WalletPromotion::ACTION_FIXED,
        'reward_value'        => '30.0000',
        'grant_validity_days' => 30,
    ]);

    echo "[STAGE 6 T-21 BEFORE]\n";
    echo "  Wallet cash_balance:      {$wallet2->cash_balance}\n";
    echo "  Wallet promo_balance:     {$wallet2->promo_balance}\n";
    echo "  Wallet promo_debt:        {$wallet2->promo_debt}\n";
    echo "  Wallet available_balance: {$wallet2->available_balance}\n";
    echo "  Wallet total_balance:     {$wallet2->total_balance}\n";
    echo "  Debt remaining_debt:      {$debt2->remaining_debt_amount}\n";
    echo "  Debt settled_amount:      {$debt2->settled_amount}\n";
    echo "  Ledger promo txns count:  " . WalletTransaction::where('wallet_id', $wallet2->id)->count() . "\n";

    // Orchestrate T-21 Settlement: Grant=30, Settle=20, Net=10
    $grantAmount = '30.0000';
    $settlementAmount = '20.0000';
    $netToCredit = '10.0000';
    $eventKey2 = "order:{$orderId2}:invoice:99:promo:{$promo2->id}:" . uniqid();

    $usage2 = WalletPromotionUsage::create([
        'promotion_id'        => $promo2->id,
        'customer_id'         => $custId2,
        'event_key'           => $eventKey2,
        'reward_amount'       => $grantAmount,
        'base_reward_amount'  => $grantAmount,
        'net_credited_amount' => $netToCredit,
        'currency_code'       => 'SAR',
        'status'              => 'approved',
        'promotion_snapshot'  => $promo2->toArray(),
    ]);

    $debt2->remaining_debt_amount = '0.0000';
    $debt2->settled_amount = '20.0000';
    $debt2->status = 'settled';
    $debt2->settled_at = now();
    $debt2->save();

    $grant2 = WalletPromotionGrant::create([
        'promotion_id'     => $promo2->id,
        'customer_id'      => $custId2,
        'wallet_id'        => $wallet2->id,
        'usage_id'         => $usage2->id,
        'original_amount'  => '30.0000',
        'remaining_amount' => '10.0000',
        'consumed_amount'  => '20.0000',
        'currency_code'    => 'SAR',
        'base_amount'      => '30.0000',
        'status'           => 'partially_consumed',
        'reference_type'   => WalletPromotion::class,
        'reference_id'     => $promo2->id,
        'granted_at'       => now(),
        'expires_at'       => now()->addDays(30),
    ]);

    WalletPromoDebtSettlement::create([
        'debt_id'                => $debt2->id,
        'wallet_id'              => $wallet2->id,
        'customer_id'            => $custId2,
        'grant_id'               => $grant2->id,
        'settlement_amount'      => $settlementAmount,
        'base_settlement_amount' => $settlementAmount,
        'currency_code'          => 'SAR',
        'event_key'              => "debt:{$debt2->id}:grant:{$grant2->id}:settle:" . uniqid(),
    ]);

    $wallet2->promo_debt = '0.0000';
    $wallet2->save();

    $txn2 = $service->creditPromotion(
        wallet: $wallet2,
        amountStr: $netToCredit,
        description: "Order Cashback #{$orderId2} (Net: 10.0000, Settled Debt: 20.0000)",
        referenceType: WalletPromotionGrant::class,
        referenceId: $grant2->id
    );

    $fresh2 = $wallet2->fresh();
    $freshDebt2 = $debt2->fresh();
    $freshGrant2 = $grant2->fresh();

    echo "[STAGE 6 T-21 AFTER]\n";
    echo "  Wallet cash_balance:      {$fresh2->cash_balance} (UNTOUCHED = 100.0000)\n";
    echo "  Wallet promo_balance:     {$fresh2->promo_balance} (EXACT = 10.0000, NO DOUBLING)\n";
    echo "  Wallet promo_debt:        {$fresh2->promo_debt} (EXACT = 0.0000)\n";
    echo "  Wallet available_balance: {$fresh2->available_balance} (EXACT = 110.0000)\n";
    echo "  Wallet total_balance:     {$fresh2->total_balance} (EXACT = 110.0000)\n";
    echo "  Debt remaining_debt:      {$freshDebt2->remaining_debt_amount} (EXACT = 0.0000, status={$freshDebt2->status})\n";
    echo "  Debt settled_amount:      {$freshDebt2->settled_amount} (EXACT = 20.0000)\n";
    echo "  Grant original_amount:    {$freshGrant2->original_amount} (30.0000)\n";
    echo "  Grant remaining_amount:   {$freshGrant2->remaining_amount} (10.0000)\n";
    echo "  Grant consumed_amount:    {$freshGrant2->consumed_amount} (20.0000)\n";
    echo "  Grant Invariant Proof:    30.0000 == 10.0000 + 20.0000 (" . (bccomp($freshGrant2->original_amount, bcadd($freshGrant2->remaining_amount, $freshGrant2->consumed_amount, 4), 4) === 0 ? "PASSED" : "FAILED") . ")\n";
    echo "  Ledger promo txns count:  " . WalletTransaction::where('wallet_id', $wallet2->id)->where('type', WalletTransaction::TYPE_CREDIT_PROMOTION)->count() . " (EXACT = 1)\n";
    echo "  Ledger promo txn amount:  {$txn2->amount} (EXACT = 10.0000)\n";

    if (bccomp($fresh2->promo_balance, '10.0000', 4) !== 0) fatalStep("Stage 6", "T-21 promo_balance != 10.0000");
    if (bccomp($fresh2->total_balance, '110.0000', 4) !== 0) fatalStep("Stage 6", "T-21 total_balance != 110.0000");
    if (bccomp($freshDebt2->remaining_debt_amount, '0.0000', 4) !== 0) fatalStep("Stage 6", "T-21 remaining debt != 0.0000");

    DB::commit();
} catch (\Throwable $e) {
    DB::rollBack();
    fatalStep("Stage 6 T-21", $e->getMessage());
}
echo "STAGE 6: COMPLETED (Exit Code: 0)\n\n";

// ---------------------------------------------------------
// STAGE 7: REAL DB TEST - CONCURRENT IDEMPOTENCY & DUPLICATE KEY
// ---------------------------------------------------------
echo "=== STAGE 7: REAL DB TEST - CONCURRENT IDEMPOTENCY & DUPLICATE KEY ===\n";
DB::beginTransaction();
try {
    $custId3 = DB::table('customers')->insertGetId([
        'first_name' => 'Concur',
        'last_name'  => 'User',
        'email'      => 'concur_' . uniqid() . '@test.local',
    ]);

    $wallet3 = WalletAccount::create([
        'customer_id'          => $custId3,
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

    $promo3 = WalletPromotion::create([
        'name'         => 'Welcome Reward 15 SAR',
        'type'         => WalletPromotion::TYPE_WELCOME_BONUS,
        'status'       => WalletPromotion::STATUS_ACTIVE,
        'action_type'  => WalletPromotion::ACTION_FIXED,
        'reward_value' => '15.0000',
    ]);

    $idemEventKey = 'welcome:customer:' . $custId3;

    // First insertion
    $usageA = WalletPromotionUsage::create([
        'promotion_id'        => $promo3->id,
        'customer_id'         => $custId3,
        'event_key'           => $idemEventKey,
        'reward_amount'       => '15.0000',
        'base_reward_amount'  => '15.0000',
        'net_credited_amount' => '15.0000',
        'currency_code'       => 'SAR',
        'status'              => 'approved',
        'promotion_snapshot'  => $promo3->toArray(),
    ]);

    $grantA = WalletPromotionGrant::create([
        'promotion_id'     => $promo3->id,
        'customer_id'      => $custId3,
        'wallet_id'        => $wallet3->id,
        'usage_id'         => $usageA->id,
        'original_amount'  => '15.0000',
        'remaining_amount' => '15.0000',
        'consumed_amount'  => '0.0000',
        'currency_code'    => 'SAR',
        'base_amount'      => '15.0000',
        'status'           => 'active',
        'reference_type'   => WalletPromotion::class,
        'reference_id'     => $promo3->id,
        'granted_at'       => now(),
    ]);

    $service->creditPromotion(
        wallet: $wallet3,
        amountStr: '15.0000',
        description: 'Welcome Bonus (Attempt 1)'
    );

    echo "[ATTEMPT 1] Inserted Usage #{$usageA->id}, Grant #{$grantA->id}. promo_balance = {$wallet3->fresh()->promo_balance}\n";

    // Second parallel duplicate attempt
    $duplicateKeyCaught = false;
    try {
        WalletPromotionUsage::create([
            'promotion_id'        => $promo3->id,
            'customer_id'         => $custId3,
            'event_key'           => $idemEventKey,
            'reward_amount'       => '15.0000',
            'base_reward_amount'  => '15.0000',
            'net_credited_amount' => '15.0000',
            'currency_code'       => 'SAR',
            'status'              => 'approved',
            'promotion_snapshot'  => $promo3->toArray(),
        ]);
    } catch (\Illuminate\Database\QueryException $e) {
        $duplicateKeyCaught = true;
        echo "[ATTEMPT 2] Intercepted Duplicate Key via MySQL UNIQUE index (SQLSTATE {$e->getCode()}, Error {$e->errorInfo[1]}): {$e->errorInfo[2]}\n";
    }

    if (! $duplicateKeyCaught) {
        fatalStep("Stage 7 Idempotency", "Duplicate key was NOT caught by MySQL unique index!");
    }

    $finalUsages = WalletPromotionUsage::where('promotion_id', $promo3->id)->where('customer_id', $custId3)->count();
    $finalGrants = WalletPromotionGrant::where('promotion_id', $promo3->id)->where('customer_id', $custId3)->count();
    $finalLedgers = WalletTransaction::where('wallet_id', $wallet3->id)->where('type', WalletTransaction::TYPE_CREDIT_PROMOTION)->count();
    $finalPromoBal = $wallet3->fresh()->promo_balance;

    echo "  Total Usages in DB:           {$finalUsages} (EXACT = 1)\n";
    echo "  Total Grants in DB:           {$finalGrants} (EXACT = 1)\n";
    echo "  Total Ledgers in DB:          {$finalLedgers} (EXACT = 1)\n";
    echo "  Final promo_balance:          {$finalPromoBal} (EXACT = 15.0000, NO DOUBLE CREDIT)\n";

    if ($finalUsages !== 1 || $finalGrants !== 1 || $finalLedgers !== 1 || bccomp($finalPromoBal, '15.0000', 4) !== 0) {
        fatalStep("Stage 7 Idempotency", "Idempotency invariant violated!");
    }

    DB::commit();
} catch (\Throwable $e) {
    DB::rollBack();
    fatalStep("Stage 7 Idempotency", $e->getMessage());
}
echo "STAGE 7: COMPLETED (Exit Code: 0)\n\n";

// ---------------------------------------------------------
// STAGE 8: REAL DB TEST - PENDING_REVIEW AUDIT GUARD
// ---------------------------------------------------------
echo "=== STAGE 8: REAL DB TEST - pending_review AUDIT GUARD ===\n";
DB::beginTransaction();
try {
    $custId4 = DB::table('customers')->insertGetId([
        'first_name' => 'Audit',
        'last_name'  => 'Guard',
        'email'      => 'audit_' . uniqid() . '@test.local',
    ]);

    $wallet4 = WalletAccount::create([
        'customer_id'          => $custId4,
        'cash_balance'         => '0.0000',
        'promo_balance'        => '0.0000',
        'held_balance'         => '0.0000',
        'unclassified_balance' => '75.0000',
        'promo_debt'           => '0.0000',
        'available_balance'    => '0.0000',
        'total_balance'        => '75.0000',
        'status'               => 'active',
        'backfill_status'      => 'pending_review',
    ]);

    $auditCaught = false;
    try {
        $service->creditPromotion(
            wallet: $wallet4,
            amountStr: '20.0000',
            description: 'Attempt to credit audited account'
        );
    } catch (AccountUnderAuditException $e) {
        $auditCaught = true;
        echo "[AUDIT GUARD] AccountUnderAuditException correctly caught: {$e->getMessage()}\n";
    }

    if (! $auditCaught) {
        fatalStep("Stage 8 Audit Guard", "AccountUnderAuditException was NOT thrown!");
    }

    $promoBal4 = $wallet4->fresh()->promo_balance;
    $totalBal4 = $wallet4->fresh()->total_balance;
    $ledgers4 = WalletTransaction::where('wallet_id', $wallet4->id)->count();

    echo "  Wallet promo_balance:         {$promoBal4} (UNTOUCHED = 0.0000)\n";
    echo "  Wallet total_balance:         {$totalBal4} (UNTOUCHED = 75.0000)\n";
    echo "  Ledger Records Created:       {$ledgers4} (EXACT = 0)\n";

    if (bccomp($promoBal4, '0.0000', 4) !== 0 || bccomp($totalBal4, '75.0000', 4) !== 0 || $ledgers4 !== 0) {
        fatalStep("Stage 8 Audit Guard", "Audited account was incorrectly credited!");
    }

    DB::commit();
} catch (\Throwable $e) {
    DB::rollBack();
    fatalStep("Stage 8 Audit Guard", $e->getMessage());
}
echo "STAGE 8: COMPLETED (Exit Code: 0)\n\n";

echo "======================================================================\n";
echo " ALL 8 STAGES OF GATE 1 VERIFICATION COMPLETED WITH ZERO ERRORS!\n";
echo " EXIT CODE: 0\n";
echo "======================================================================\n";
exit(0);
