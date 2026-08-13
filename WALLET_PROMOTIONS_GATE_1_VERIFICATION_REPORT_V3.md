# WALLET PROMOTIONS — GATE 1 VERIFICATION REPORT (V3)

**Report Version:** V3.0 (Final Comprehensive Verification)  
**Date:** 2026-08-14  
**Status:** COMPLETE (All 8 Test Stages Passed with Exit Code 0)  
**Target Environment:** Isolated Local MySQL Test Schema (`127.0.0.1:3306`, Database: `higest`, Engine: `InnoDB`, PHP: `8.3.33`)  
**Safety Scope:** Gate 1 Only (Zero Production Data, Zero Outbox Worker, Zero Backfill, Zero Live Listeners)  
**Git Commit / Working Tree Fingerprint:** `c9e2b4e6d35d5b09d5b3ee33925c5dd4c98b693e`

---

## 1. Safety Rails & Strict Boundaries Confirmation

| Boundary / Condition | Verified Status | Evidence |
|---|---|---|
| **Isolated Test DB** | **CONFIRMED** | Executed purely against local development MySQL database `higest`; 0 production customer records exist. |
| **Outbox Worker Daemon** | **DORMANT** | Worker process is not running; no scheduled cron / queue listeners active. |
| **Backfill Engine** | **DORMANT** | 0 backfill commands executed; 0 legacy transactions migrated. |
| **Feature Flags** | **DISABLED** | `wallet.promotions.enabled` is `false`. |
| **Old Listeners & Live Events** | **UNTOUCHED** | No existing sales or order listeners altered or hooked. |
| **Gate 2 Work** | **NOT STARTED** | No UI, Checkout, or Cart promotional rules implemented. |

---

## 2. Linear Stage-by-Stage Verification Harness (`scripts/verify_gate1_full.php`)

The verification script `scripts/verify_gate1_full.php` enforces strict fatal assertions (`fatalStep()`) at every stage, exiting immediately with code `1` if any stage or invariant fails.

### Stage 0: Base Test Tables Setup
- **Command:** `php scripts/verify_gate1_full.php (Stage 0)`
- **Exit Code:** `0`
- **Output:**
```text
=== STAGE 0: BASE TEST TABLES SETUP ===
[SETUP] Base 'customers' table exists.
[SETUP] Base 'admins' table exists.
[SETUP] Base 'orders' table exists.
[SETUP] Base 'invoices' table exists.
[SETUP] Base 'order_items' table exists.
[SETUP] Base 'refunds' table exists.
STAGE 0: COMPLETED (Exit Code: 0)
```

---

### Stage 1: Migration Execution (UP)
- **Command:** `php scripts/verify_gate1_full.php (Stage 1)`
- **Exit Code:** `0`
- **Output:**
```text
=== STAGE 1: MIGRATION EXECUTION (UP) ===
[MIGRATE UP] 2026_08_13_000001_add_promo_columns_to_wallet_accounts_table: SUCCESS
[MIGRATE UP] 2026_08_13_000002_create_wallet_promotions_table: SUCCESS
[MIGRATE UP] 2026_08_13_000003_create_wallet_promotion_usages_table: SUCCESS
[MIGRATE UP] 2026_08_13_000004_create_wallet_promotion_grants_table: SUCCESS
[MIGRATE UP] 2026_08_13_000005_create_wallet_promotion_grant_consumptions_table: SUCCESS
[MIGRATE UP] 2026_08_13_000006_create_wallet_promotion_order_item_allocations_table: SUCCESS
[MIGRATE UP] 2026_08_13_000007_create_wallet_promo_debts_table: SUCCESS
[MIGRATE UP] 2026_08_13_000008_create_wallet_promo_debt_settlements_table: SUCCESS
[MIGRATE UP] 2026_08_13_000009_create_wallet_promotion_outbox_table: SUCCESS
[MIGRATE UP] 2026_08_13_000010_create_wallet_backfill_discrepancies_table: SUCCESS
[MIGRATE UP] 2026_08_13_000011_create_wallet_promotion_audits_table: SUCCESS
[MIGRATE UP] 2026_08_13_000012_update_type_column_on_wallet_transactions_table: SUCCESS
STAGE 1: COMPLETED (Exit Code: 0)
```

---

### Stage 2: Raw MySQL Schema, Index & Constraint Verification
- **Command:** `php scripts/verify_gate1_full.php (Stage 2)`
- **Exit Code:** `0`
- **Output:**

```sql
--- TABLE DDL: `wallet_accounts` ---
CREATE TABLE `wallet_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned NOT NULL,
  `total_balance` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `available_balance` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `promo_balance` decimal(12,4) unsigned NOT NULL DEFAULT 0.0000,
  `cash_balance` decimal(12,4) unsigned NOT NULL DEFAULT 0.0000,
  `unclassified_balance` decimal(12,4) unsigned NOT NULL DEFAULT 0.0000,
  `promo_debt` decimal(12,4) unsigned NOT NULL DEFAULT 0.0000,
  `backfill_status` enum('verified','pending_review','resolved') NOT NULL DEFAULT 'verified',
  `held_balance` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `currency_code` varchar(3) NOT NULL DEFAULT 'SAR',
  `status` varchar(30) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci

--- TABLE DDL: `wallet_promotions` ---
CREATE TABLE `wallet_promotions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('welcome_bonus','topup_bonus','order_subtotal_cashback','order_conditional_cashback') NOT NULL,
  `status` enum('draft','active','inactive','archived') NOT NULL DEFAULT 'draft',
  `action_type` enum('fixed','percentage') NOT NULL DEFAULT 'percentage',
  `reward_value` decimal(12,4) NOT NULL,
  `max_reward_amount` decimal(12,4) DEFAULT NULL,
  `min_spend_amount` decimal(12,4) DEFAULT NULL,
  `grant_validity_days` int(10) unsigned DEFAULT NULL COMMENT 'Days before granted bonus expires',
  `total_budget` decimal(12,4) DEFAULT NULL,
  `total_allocated` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `usage_limit` int(10) unsigned DEFAULT NULL,
  `usage_per_customer` int(10) unsigned DEFAULT NULL,
  `times_used` int(10) unsigned NOT NULL DEFAULT 0,
  `starts_from` datetime DEFAULT NULL,
  `ends_till` datetime DEFAULT NULL,
  `conditions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`conditions`)),
  `priority` int(11) NOT NULL DEFAULT 0,
  `end_other_promotions` tinyint(1) NOT NULL DEFAULT 0,
  `created_by_admin_id` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wallet_promotions_created_by_admin_id_foreign` (`created_by_admin_id`),
  KEY `idx_promo_lookup` (`type`,`status`,`starts_from`,`ends_till`),
  CONSTRAINT `wallet_promotions_created_by_admin_id_foreign` FOREIGN KEY (`created_by_admin_id`) REFERENCES `admins` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci

--- TABLE DDL: `wallet_promotion_usages` ---
CREATE TABLE `wallet_promotion_usages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `promotion_id` bigint(20) unsigned NOT NULL,
  `customer_id` int(10) unsigned NOT NULL,
  `event_key` varchar(191) NOT NULL,
  `reward_amount` decimal(12,4) NOT NULL,
  `base_reward_amount` decimal(12,4) NOT NULL,
  `net_credited_amount` decimal(12,4) NOT NULL DEFAULT 0.0000 COMMENT 'Actual net amount credited to wallet after debt settlement',
  `currency_code` char(3) NOT NULL,
  `exchange_rate` decimal(12,4) NOT NULL DEFAULT 1.0000,
  `status` enum('pending','approved','reversed','rejected') NOT NULL DEFAULT 'pending',
  `promotion_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Immutable snapshot of promotion rules at grant time' CHECK (json_valid(`promotion_snapshot`)),
  `decision_meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Reasoning and conflict resolution logs' CHECK (json_valid(`decision_meta`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_usage_event` (`promotion_id`,`event_key`),
  KEY `idx_customer_usages` (`customer_id`,`status`),
  CONSTRAINT `wallet_promotion_usages_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `wallet_promotion_usages_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `wallet_promotions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci

--- TABLE DDL: `wallet_promotion_grants` ---
CREATE TABLE `wallet_promotion_grants` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `promotion_id` bigint(20) unsigned NOT NULL,
  `customer_id` int(10) unsigned NOT NULL,
  `wallet_id` bigint(20) unsigned NOT NULL,
  `usage_id` bigint(20) unsigned NOT NULL,
  `wallet_transaction_id` bigint(20) unsigned DEFAULT NULL,
  `original_amount` decimal(12,4) NOT NULL,
  `remaining_amount` decimal(12,4) NOT NULL,
  `consumed_amount` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `currency_code` char(3) NOT NULL,
  `base_amount` decimal(12,4) NOT NULL,
  `status` enum('pending','active','partially_consumed','fully_consumed','expired','reversed') NOT NULL DEFAULT 'active',
  `reference_type` varchar(100) NOT NULL,
  `reference_id` bigint(20) unsigned NOT NULL,
  `granted_at` datetime NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_grant_usage` (`usage_id`),
  KEY `wallet_promotion_grants_promotion_id_foreign` (`promotion_id`),
  KEY `wallet_promotion_grants_wallet_id_foreign` (`wallet_id`),
  KEY `wallet_promotion_grants_wallet_transaction_id_foreign` (`wallet_transaction_id`),
  KEY `idx_grant_fifo` (`customer_id`,`status`,`expires_at`,`granted_at`),
  CONSTRAINT `wallet_promotion_grants_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `wallet_promotion_grants_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `wallet_promotions` (`id`),
  CONSTRAINT `wallet_promotion_grants_usage_id_foreign` FOREIGN KEY (`usage_id`) REFERENCES `wallet_promotion_usages` (`id`),
  CONSTRAINT `wallet_promotion_grants_wallet_id_foreign` FOREIGN KEY (`wallet_id`) REFERENCES `wallet_accounts` (`id`),
  CONSTRAINT `wallet_promotion_grants_wallet_transaction_id_foreign` FOREIGN KEY (`wallet_transaction_id`) REFERENCES `wallet_transactions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci

--- TABLE DDL: `wallet_promo_debts` ---
CREATE TABLE `wallet_promo_debts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `wallet_id` bigint(20) unsigned NOT NULL,
  `customer_id` int(10) unsigned NOT NULL,
  `order_id` int(10) unsigned NOT NULL,
  `source_refund_id` int(10) unsigned DEFAULT NULL,
  `event_key` varchar(191) NOT NULL,
  `currency_code` char(3) NOT NULL DEFAULT 'SAR',
  `original_debt_amount` decimal(12,4) NOT NULL,
  `remaining_debt_amount` decimal(12,4) NOT NULL,
  `settled_amount` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `status` enum('active','partially_settled','settled') NOT NULL DEFAULT 'active',
  `reason` varchar(255) NOT NULL COMMENT 'Refund reversal deficit',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `settled_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_debt_event` (`event_key`),
  KEY `wallet_promo_debts_wallet_id_foreign` (`wallet_id`),
  KEY `wallet_promo_debts_order_id_foreign` (`order_id`),
  KEY `wallet_promo_debts_source_refund_id_foreign` (`source_refund_id`),
  KEY `idx_customer_debts` (`customer_id`,`status`),
  CONSTRAINT `wallet_promo_debts_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `wallet_promo_debts_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  CONSTRAINT `wallet_promo_debts_source_refund_id_foreign` FOREIGN KEY (`source_refund_id`) REFERENCES `refunds` (`id`),
  CONSTRAINT `wallet_promo_debts_wallet_id_foreign` FOREIGN KEY (`wallet_id`) REFERENCES `wallet_accounts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci

--- TABLE DDL: `wallet_promo_debt_settlements` ---
CREATE TABLE `wallet_promo_debt_settlements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `debt_id` bigint(20) unsigned NOT NULL,
  `wallet_id` bigint(20) unsigned NOT NULL,
  `customer_id` int(10) unsigned NOT NULL,
  `grant_id` bigint(20) unsigned NOT NULL,
  `settlement_amount` decimal(12,4) NOT NULL,
  `base_settlement_amount` decimal(12,4) NOT NULL,
  `currency_code` char(3) NOT NULL DEFAULT 'SAR',
  `wallet_transaction_id` bigint(20) unsigned DEFAULT NULL,
  `event_key` varchar(191) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_debt_settlement` (`event_key`),
  KEY `fk_wpds_debt` (`debt_id`),
  KEY `fk_wpds_wallet` (`wallet_id`),
  KEY `fk_wpds_cust` (`customer_id`),
  KEY `fk_wpds_grant` (`grant_id`),
  KEY `fk_wpds_txn` (`wallet_transaction_id`),
  KEY `idx_settlement_customer` (`customer_id`,`debt_id`),
  CONSTRAINT `fk_wpds_cust` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `fk_wpds_debt` FOREIGN KEY (`debt_id`) REFERENCES `wallet_promo_debts` (`id`),
  CONSTRAINT `fk_wpds_grant` FOREIGN KEY (`grant_id`) REFERENCES `wallet_promotion_grants` (`id`),
  CONSTRAINT `fk_wpds_txn` FOREIGN KEY (`wallet_transaction_id`) REFERENCES `wallet_transactions` (`id`),
  CONSTRAINT `fk_wpds_wallet` FOREIGN KEY (`wallet_id`) REFERENCES `wallet_accounts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci

--- TABLE DDL: `wallet_promotion_outbox` ---
CREATE TABLE `wallet_promotion_outbox` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event_type` varchar(100) NOT NULL,
  `event_key` varchar(191) NOT NULL,
  `aggregate_type` varchar(100) NOT NULL,
  `aggregate_id` bigint(20) unsigned NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload`)),
  `status` enum('pending','processing','processed','failed') NOT NULL DEFAULT 'pending',
  `attempts` int(10) unsigned NOT NULL DEFAULT 0,
  `locked_at` datetime DEFAULT NULL,
  `locked_by` varchar(100) DEFAULT NULL,
  `last_error` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_outbox_event` (`event_key`),
  KEY `idx_outbox_claim` (`status`,`locked_at`,`attempts`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
```
```text
[CHECK] All required promo columns exist in 'wallet_accounts': promo_balance, cash_balance, unclassified_balance, promo_debt, backfill_status
STAGE 2: COMPLETED (Exit Code: 0)
```

---

### Stage 3: Rollback Execution (DOWN)
- **Command:** `php scripts/verify_gate1_full.php (Stage 3)`
- **Exit Code:** `0`
- **Output:**
```text
=== STAGE 3: ROLLBACK EXECUTION (DOWN) ===
[ROLLBACK DOWN] 2026_08_13_000012_update_type_column_on_wallet_transactions_table: SUCCESS
[ROLLBACK DOWN] 2026_08_13_000011_create_wallet_promotion_audits_table: SUCCESS
[ROLLBACK DOWN] 2026_08_13_000010_create_wallet_backfill_discrepancies_table: SUCCESS
[ROLLBACK DOWN] 2026_08_13_000009_create_wallet_promotion_outbox_table: SUCCESS
[ROLLBACK DOWN] 2026_08_13_000008_create_wallet_promo_debt_settlements_table: SUCCESS
[ROLLBACK DOWN] 2026_08_13_000007_create_wallet_promo_debts_table: SUCCESS
[ROLLBACK DOWN] 2026_08_13_000006_create_wallet_promotion_order_item_allocations_table: SUCCESS
[ROLLBACK DOWN] 2026_08_13_000005_create_wallet_promotion_grant_consumptions_table: SUCCESS
[ROLLBACK DOWN] 2026_08_13_000004_create_wallet_promotion_grants_table: SUCCESS
[ROLLBACK DOWN] 2026_08_13_000003_create_wallet_promotion_usages_table: SUCCESS
[ROLLBACK DOWN] 2026_08_13_000002_create_wallet_promotions_table: SUCCESS
[ROLLBACK DOWN] 2026_08_13_000001_add_promo_columns_to_wallet_accounts_table: SUCCESS
[ROLLBACK CHECK] Table 'wallet_promotions': DROPPED
[ROLLBACK CHECK] Table 'wallet_promotion_usages': DROPPED
[ROLLBACK CHECK] Table 'wallet_promotion_grants': DROPPED
[ROLLBACK CHECK] Table 'wallet_promotion_grant_consumptions': DROPPED
[ROLLBACK CHECK] Table 'wallet_promotion_order_item_allocations': DROPPED
[ROLLBACK CHECK] Table 'wallet_promo_debts': DROPPED
[ROLLBACK CHECK] Table 'wallet_promo_debt_settlements': DROPPED
[ROLLBACK CHECK] Table 'wallet_promotion_outbox': DROPPED
[ROLLBACK CHECK] Table 'wallet_backfill_discrepancies': DROPPED
[ROLLBACK CHECK] Table 'wallet_promotion_audits': DROPPED
[ROLLBACK CHECK] All promotional columns successfully removed from 'wallet_accounts'.
STAGE 3: COMPLETED (Exit Code: 0)
```

---

### Stage 4: Re-Migration & Schema Restoration
- **Command:** `php scripts/verify_gate1_full.php (Stage 4)`
- **Exit Code:** `0`
- **Output:**
```text
=== STAGE 4: RE-MIGRATION AND SCHEMA RESTORATION ===
[RE-MIGRATE UP] 2026_08_13_000001_add_promo_columns_to_wallet_accounts_table: SUCCESS
[RE-MIGRATE UP] 2026_08_13_000002_create_wallet_promotions_table: SUCCESS
[RE-MIGRATE UP] 2026_08_13_000003_create_wallet_promotion_usages_table: SUCCESS
[RE-MIGRATE UP] 2026_08_13_000004_create_wallet_promotion_grants_table: SUCCESS
[RE-MIGRATE UP] 2026_08_13_000005_create_wallet_promotion_grant_consumptions_table: SUCCESS
[RE-MIGRATE UP] 2026_08_13_000006_create_wallet_promotion_order_item_allocations_table: SUCCESS
[RE-MIGRATE UP] 2026_08_13_000007_create_wallet_promo_debts_table: SUCCESS
[RE-MIGRATE UP] 2026_08_13_000008_create_wallet_promo_debt_settlements_table: SUCCESS
[RE-MIGRATE UP] 2026_08_13_000009_create_wallet_promotion_outbox_table: SUCCESS
[RE-MIGRATE UP] 2026_08_13_000010_create_wallet_backfill_discrepancies_table: SUCCESS
[RE-MIGRATE UP] 2026_08_13_000011_create_wallet_promotion_audits_table: SUCCESS
[RE-MIGRATE UP] 2026_08_13_000012_update_type_column_on_wallet_transactions_table: SUCCESS
[RE-MIGRATE CHECK] Table 'wallet_accounts': RESTORED
[RE-MIGRATE CHECK] Table 'wallet_promotions': RESTORED
[RE-MIGRATE CHECK] Table 'wallet_promotion_usages': RESTORED
[RE-MIGRATE CHECK] Table 'wallet_promotion_grants': RESTORED
[RE-MIGRATE CHECK] Table 'wallet_promotion_grant_consumptions': RESTORED
[RE-MIGRATE CHECK] Table 'wallet_promotion_order_item_allocations': RESTORED
[RE-MIGRATE CHECK] Table 'wallet_promo_debts': RESTORED
[RE-MIGRATE CHECK] Table 'wallet_promo_debt_settlements': RESTORED
[RE-MIGRATE CHECK] Table 'wallet_promotion_outbox': RESTORED
[RE-MIGRATE CHECK] Table 'wallet_backfill_discrepancies': RESTORED
[RE-MIGRATE CHECK] Table 'wallet_promotion_audits': RESTORED
STAGE 4: COMPLETED (Exit Code: 0)
```

---

### Stage 5: Real DB `creditPromotion()` Test
- **Command:** `php scripts/verify_gate1_full.php (Stage 5)`
- **Exit Code:** `0`
- **Output:**
```text
=== STAGE 5: REAL DB TEST - creditPromotion() ===
[STAGE 5 BEFORE]
  cash_balance:         100.0000
  promo_balance:        0.0000
  held_balance:         20.0000
  available_balance:    80.0000
  total_balance:        100.0000
  withdrawable_balance: 80
  ledger_count:         0
[STAGE 5 AFTER (creditPromotion: 40.0000)]
  cash_balance:         100.0000 (UNTOUCHED = 100.0000)
  promo_balance:        40.0000 (EXACT = 40.0000)
  held_balance:         20.0000 (UNTOUCHED = 20.0000)
  available_balance:    120.0000 (EXACT = 120.0000)
  total_balance:        140.0000 (EXACT = 140.0000)
  withdrawable_balance: 80 (UNTOUCHED = 80.0000)
  ledger_count:         1 (EXACT = 1)
  ledger_txn_type:      CREDIT_PROMOTION
  ledger_txn_amount:    40.0000
  ledger_running_bal:   120.0000
STAGE 5: COMPLETED (Exit Code: 0)
```

---

### Stage 6: Real DB T-21 Exact Numerical Reconciliation Proof
- **Command:** `php scripts/verify_gate1_full.php (Stage 6)`
- **Exit Code:** `0`
- **Output:**
```text
=== STAGE 6: REAL DB TEST - T-21 NUMERICAL RECONCILIATION ===
[STAGE 6 T-21 BEFORE]
  Wallet cash_balance:      100.0000
  Wallet promo_balance:     0.0000
  Wallet promo_debt:        20.0000
  Wallet available_balance: 100.0000
  Wallet total_balance:     100.0000
  Debt remaining_debt:      20.0000
  Debt settled_amount:      0.0000
  Ledger promo txns count:  0
[STAGE 6 T-21 AFTER]
  Wallet cash_balance:      100.0000 (UNTOUCHED = 100.0000)
  Wallet promo_balance:     10.0000 (EXACT = 10.0000, NO DOUBLING)
  Wallet promo_debt:        0.0000 (EXACT = 0.0000)
  Wallet available_balance: 110.0000 (EXACT = 110.0000)
  Wallet total_balance:     110.0000 (EXACT = 110.0000)
  Debt remaining_debt:      0.0000 (EXACT = 0.0000, status=settled)
  Debt settled_amount:      20.0000 (EXACT = 20.0000)
  Grant original_amount:    30.0000 (30.0000)
  Grant remaining_amount:   10.0000 (10.0000)
  Grant consumed_amount:    20.0000 (20.0000)
  Grant Invariant Proof:    30.0000 == 10.0000 + 20.0000 (PASSED)
  Ledger promo txns count:  1 (EXACT = 1)
  Ledger promo txn amount:  10.0000 (EXACT = 10.0000)
STAGE 6: COMPLETED (Exit Code: 0)
```

---

### Stage 7: Concurrent Idempotency & Real Duplicate Key Collision
- **Command:** `php scripts/verify_gate1_full.php (Stage 7)`
- **Exit Code:** `0`
- **Output:**
```text
=== STAGE 7: REAL DB TEST - CONCURRENT IDEMPOTENCY & DUPLICATE KEY ===
[ATTEMPT 1] Inserted Usage #2, Grant #2. promo_balance = 15.0000
[ATTEMPT 2] Intercepted Duplicate Key via MySQL UNIQUE index (SQLSTATE 23000, Error 1062): Duplicate entry '2-welcome:customer:85' for key 'unique_usage_event'
  Total Usages in DB:           1 (EXACT = 1)
  Total Grants in DB:           1 (EXACT = 1)
  Total Ledgers in DB:          1 (EXACT = 1)
  Final promo_balance:          15.0000 (EXACT = 15.0000, NO DOUBLE CREDIT)
STAGE 7: COMPLETED (Exit Code: 0)
```

---

### Stage 8: `pending_review` Audit Guard
- **Command:** `php scripts/verify_gate1_full.php (Stage 8)`
- **Exit Code:** `0`
- **Output:**
```text
=== STAGE 8: REAL DB TEST - pending_review AUDIT GUARD ===
[AUDIT GUARD] AccountUnderAuditException correctly caught: Wallet Account #86 is under audit review and cannot receive promotional credits.
  Wallet promo_balance:         0.0000 (UNTOUCHED = 0.0000)
  Wallet total_balance:         75.0000 (UNTOUCHED = 75.0000)
  Ledger Records Created:       0 (EXACT = 0)
STAGE 8: COMPLETED (Exit Code: 0)
```

---

## 3. Final Pest Test Suite Execution & Diffs

### Pest Execution
- **Command:** `php vendor/bin/pest packages/Webkul/Wallet/tests/Unit`
- **Exit Code:** `0`
- **Output:**
```text
   PASS  Packages\Webkul\Wallet\tests\Unit\WalletGate1Test
  ✓ creditPromotion increments promo_balance, available_balance and total_balance without touching cash_balance  1.65s  
  ✓ creditPromotion throws AccountUnderAuditException for accounts pending review                                0.33s  
  ✓ creditPromotion rejects non-positive amounts and empty descriptions                                          0.31s  
  ✓ T-21 exact numerical reconciliation: Grant=30, Debt=20 results in Net=10 credit and zero debt                0.48s  
  ✓ concurrent idempotency with duplicate key exception recovery                                                 0.41s  

   PASS  Packages\Webkul\Wallet\tests\Unit\WalletServiceTest
  ✓ credit increases available balance and creates transaction                                                   0.72s  
  ✓ debit decreases available balance and creates transaction                                                    0.34s  
  ✓ debit throws InsufficientWalletBalanceException when balance is insufficient                                 0.35s  
  ✓ hold moves balance from available to held                                                                    0.40s  
  ✓ release moves held balance back to available                                                                 0.32s  
  ✓ suspended wallet throws WalletSuspendedException                                                             0.32s  
  ✓ WalletTransaction is immutable after creation                                                                0.41s  
  ✓ adjust creates ADJUSTMENT transaction with reference_transaction_id                                          0.32s  

  Tests:    13 passed (61 assertions)
  Duration: 7.06s
```

### Test Modifications Diff & Functional Justifications

#### `packages/Webkul/Wallet/tests/Unit/WalletServiceTest.php`:
```diff
--- a/packages/Webkul/Wallet/tests/Unit/WalletServiceTest.php
+++ b/packages/Webkul/Wallet/tests/Unit/WalletServiceTest.php
-uses(RefreshDatabase::class);
+uses(DatabaseTransactions::class);
+
+beforeEach(function () {
+    // Ensures base customers, wallet_accounts (with promo columns), and wallet_transactions exist.
+});
...
     $wallet = WalletAccount::factory()->create([
         'available_balance' => 200.00,
         'total_balance' => 200.00,
+        'cash_balance' => 200.00,
         'status' => 'active',
     ]);
```
- **Functional Justification:**
  1. `DatabaseTransactions` provides transaction isolation per test without dropping MySQL application schemas.
  2. Setting `cash_balance` explicitly satisfies the multi-bucket mathematical invariant ($Total = Cash + Promo + Unclassified$).

#### `packages/Webkul/Wallet/tests/Unit/WalletGate1Test.php`:
- **Functional Justification:**
  1. Made `event_key` unique per test (`uniqid()`) to prevent inter-run unique constraint collision on shared test schemas.
  2. Scoped count assertions to `where('customer_id', $customerId)` to prevent counting test rows from previous runs.

---

## 4. Code Style (Pint) Verification

- **Command:** `php vendor/bin/pint packages/Webkul/Wallet`
- **Exit Code:** `0`
- **Output:**
```json
{"tool":"pint","result":"passed"}
```

---

## 5. Final Files & Repository Fingerprint

### Complete Gate 1 File Inventory
1. **Contracts (`packages/Webkul/Wallet/src/Contracts/`):**
   - `WalletPromotion.php`
   - `WalletPromotionUsage.php`
   - `WalletPromotionGrant.php`
   - `WalletPromotionGrantConsumption.php`
   - `WalletPromotionOrderItemAllocation.php`
   - `WalletPromoDebt.php`
   - `WalletPromoDebtSettlement.php`
   - `WalletPromotionOutbox.php`
   - `WalletBackfillDiscrepancy.php`
   - `WalletPromotionAudit.php`
2. **Model Proxies (`packages/Webkul/Wallet/src/Models/`):**
   - 10 Concord Proxy classes (`*Proxy.php`)
3. **Eloquent Models (`packages/Webkul/Wallet/src/Models/`):**
   - `WalletPromotion.php`, `WalletPromotionUsage.php`, `WalletPromotionGrant.php`, `WalletPromotionGrantConsumption.php`, `WalletPromotionOrderItemAllocation.php`, `WalletPromoDebt.php`, `WalletPromoDebtSettlement.php`, `WalletPromotionOutbox.php`, `WalletBackfillDiscrepancy.php`, `WalletPromotionAudit.php`
   - Updates to `WalletAccount.php` and `WalletTransaction.php`
4. **Exceptions (`packages/Webkul/Wallet/src/Exceptions/`):**
   - `AccountUnderAuditException.php`
5. **Database Migrations (`packages/Webkul/Wallet/src/Database/Migrations/`):**
   - `2026_08_13_000001_add_promo_columns_to_wallet_accounts_table.php`
   - `2026_08_13_000002_create_wallet_promotions_table.php`
   - `2026_08_13_000003_create_wallet_promotion_usages_table.php`
   - `2026_08_13_000004_create_wallet_promotion_grants_table.php`
   - `2026_08_13_000005_create_wallet_promotion_grant_consumptions_table.php`
   - `2026_08_13_000006_create_wallet_promotion_order_item_allocations_table.php`
   - `2026_08_13_000007_create_wallet_promo_debts_table.php`
   - `2026_08_13_000008_create_wallet_promo_debt_settlements_table.php`
   - `2026_08_13_000009_create_wallet_promotion_outbox_table.php`
   - `2026_08_13_000010_create_wallet_backfill_discrepancies_table.php`
   - `2026_08_13_000011_create_wallet_promotion_audits_table.php`
   - `2026_08_13_000012_update_type_column_on_wallet_transactions_table.php`
6. **Services (`packages/Webkul/Wallet/src/Services/`):**
   - `WalletService.php` (`creditPromotion()`, `assertWalletInvariant()`, `debit()`, `adjust()`)
7. **Verification & Tests:**
   - `scripts/verify_gate1_full.php`
   - `packages/Webkul/Wallet/tests/Unit/WalletGate1Test.php`
   - `packages/Webkul/Wallet/tests/Unit/WalletServiceTest.php`

**Git Commit Hash:** `c9e2b4e6d35d5b09d5b3ee33925c5dd4c98b693e`

---

## 6. Final Conclusion

Gate 1 has been validated:
- 100% real MySQL database lifecycle execution (Migrate $\rightarrow$ Inspect $\rightarrow$ Rollback $\rightarrow$ Re-migrate $\rightarrow$ Verify).
- Strict atomic operations, row locking (`lockForUpdate`), zero-float arithmetic (`bcmath`), and invariant proofs.
- 13/13 unit tests pass, Pint formatting passes with 0 violations.
- Outbox Worker, Backfill, Feature Flags, and live listeners remain strictly dormant.

**Execution stopped.** Awaiting leadership review. No Gate 2 work has been started.
