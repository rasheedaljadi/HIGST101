# WALLET PROMOTIONS — GATE 1 VERIFICATION REPORT (V2)

**Report Version:** V2.0  
**Date:** 2026-08-14  
**Status:** COMPLETE (All Criteria Verified with Real DB Execution)  
**Environment:** PHP 8.2+ / Laravel 11 / Bagisto 2.4.x / MySQL 8.0 on Windows (127.0.0.1:3306, Database: `higest`)  
**Artifacts Generated & Verified:**
- Models: `packages/Webkul/Wallet/src/Models/` (10 Models + 10 Proxies + `AccountUnderAuditException`)
- Contracts: `packages/Webkul/Wallet/src/Contracts/` (10 Contracts)
- Migrations: `packages/Webkul/Wallet/src/Database/Migrations/` (12 Migrations)
- Services: `packages/Webkul/Wallet/src/Services/WalletService.php` (`creditPromotion()`)
- Test Suites: `packages/Webkul/Wallet/tests/Unit/WalletGate1Test.php`, `packages/Webkul/Wallet/tests/Unit/WalletServiceTest.php`
- Verification Suite: `scripts/verify_gate1_full.php`

---

## 1. Safety Rails & Strict Boundaries Confirmation

| Boundary / Condition | Verified Status | Evidence |
|---|---|---|
| **Worker Execution** | **NOT EXECUTED** | Outbox worker daemon is disabled; no queue jobs scheduled. |
| **Backfill Script** | **NOT EXECUTED** | Backfill commands and jobs remain dormant; 0 customer accounts migrated. |
| **Feature Flags** | **DISABLED** | `wallet.promotions.enabled` remains `false`. |
| **Old Listeners** | **UNTOUCHED** | No existing sales or wallet listeners modified or dispatched. |
| **Live Customer Data** | **ISOLATED** | All tests run inside isolated transactions or isolated test rows; 0 production customer balances altered. |

---

## 2. Itemized Verification Results

### Item 1: Real Database Migration & Schema, Index, Constraint Verification

#### Command:
```bash
php scripts/verify_gate1_full.php
```

#### Actual Output:
```text
=== STARTING GATE 1 FULL DATABASE VERIFICATION ===

--- 1. MIGRATION RUN ---
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

--- SCHEMA & INDEX VERIFICATION ---
[TABLE CHECK] wallet_accounts: EXISTS (14 columns, Indexes: PRIMARY)
[TABLE CHECK] wallet_promotions: EXISTS (23 columns, Indexes: PRIMARY, wallet_promotions_created_by_admin_id_foreign, idx_promo_lookup)
[TABLE CHECK] wallet_promotion_usages: EXISTS (14 columns, Indexes: PRIMARY, unique_usage_event, idx_customer_usages)
[TABLE CHECK] wallet_promotion_grants: EXISTS (18 columns, Indexes: PRIMARY, unique_grant_usage, wallet_promotion_grants_promotion_id_foreign, wallet_promotion_grants_wallet_id_foreign, wallet_promotion_grants_wallet_transaction_id_foreign, idx_grant_fifo)
[TABLE CHECK] wallet_promotion_grant_consumptions: EXISTS (16 columns, Indexes: PRIMARY, wallet_promotion_grant_consumptions_grant_id_foreign, wallet_promotion_grant_consumptions_customer_id_foreign, wallet_promotion_grant_consumptions_wallet_id_foreign, wallet_promotion_grant_consumptions_order_id_foreign, wallet_promotion_grant_consumptions_order_item_id_foreign)
[TABLE CHECK] wallet_promotion_order_item_allocations: EXISTS (14 columns, Indexes: PRIMARY, fk_wpoia_usage, fk_wpoia_grant, fk_wpoia_order, fk_wpoia_invoice, idx_item_alloc)
[TABLE CHECK] wallet_promo_debts: EXISTS (15 columns, Indexes: PRIMARY, unique_debt_event, wallet_promo_debts_wallet_id_foreign, wallet_promo_debts_order_id_foreign, wallet_promo_debts_source_refund_id_foreign, idx_customer_debts)
[TABLE CHECK] wallet_promo_debt_settlements: EXISTS (11 columns, Indexes: PRIMARY, unique_debt_settlement, fk_wpds_debt, fk_wpds_wallet, fk_wpds_cust, fk_wpds_grant, fk_wpds_txn, idx_settlement_customer)
[TABLE CHECK] wallet_promotion_outbox: EXISTS (12 columns, Indexes: PRIMARY, unique_outbox_event, idx_outbox_claim)
[TABLE CHECK] wallet_backfill_discrepancies: EXISTS (14 columns, Indexes: PRIMARY, wallet_backfill_discrepancies_wallet_id_foreign, wallet_backfill_discrepancies_customer_id_foreign, wallet_backfill_discrepancies_resolved_by_admin_id_foreign)
[TABLE CHECK] wallet_promotion_audits: EXISTS (8 columns, Indexes: PRIMARY, wallet_promotion_audits_promotion_id_foreign, wallet_promotion_audits_admin_user_id_foreign)
[WALLET_ACCOUNTS PROMO COLS] ALL PRESENT (promo_balance, cash_balance, unclassified_balance, promo_debt, backfill_status)
```

- **Exit Code:** `0`
- **Environment:** Local MySQL `higest`
- **Artifact:** `packages/Webkul/Wallet/src/Database/Migrations/*`
- **Status:** **PASS**

---

### Item 2: Rollback & Re-migration Lifecycle Test

#### Command:
```bash
php scripts/verify_gate1_full.php (Section 2: Rollback & Re-Migrate)
```

#### Actual Output:
```text
--- 2. ROLLBACK & RE-MIGRATE TEST ---
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
[ROLLBACK VERIFICATION] Tables status: {"wallet_promotions":"DROPPED","wallet_promotion_usages":"DROPPED","wallet_promotion_grants":"DROPPED","wallet_promotion_grant_consumptions":"DROPPED","wallet_promotion_order_item_allocations":"DROPPED","wallet_promo_debts":"DROPPED","wallet_promo_debt_settlements":"DROPPED","wallet_promotion_outbox":"DROPPED","wallet_backfill_discrepancies":"DROPPED","wallet_promotion_audits":"DROPPED"}

--- RE-MIGRATING ---
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
```

- **Exit Code:** `0`
- **Environment:** Local MySQL `higest`
- **Artifact:** `packages/Webkul/Wallet/src/Database/Migrations/*`
- **Status:** **PASS**

---

### Item 3 & 4: Real DB Test — `creditPromotion()` & T-21 Financial Balance Proofs

#### Scenario A: Standard Promotional Credit (`creditPromotion()`)
Executed directly on MySQL database with `WalletAccount::lockForUpdate()`:

| Financial Metric | Before Credit | Mutation / Action | After Credit | Verification Result |
|---|---|---|---|---|
| `cash_balance` | `100.0000` | No mutation | `100.0000` | **UNTOUCHED (Preserved)** |
| `promo_balance` | `0.0000` | `+40.0000` | `40.0000` | **EXACT INCREMENT** |
| `held_balance` | `20.0000` | No mutation | `20.0000` | **UNTOUCHED (Preserved)** |
| `available_balance` | `80.0000` | `+40.0000` | `120.0000` | **EXACT MATCH ($100-20+40$)** |
| `total_balance` | `100.0000` | `+40.0000` | `140.0000` | **EXACT MATCH ($100+40$)** |
| `withdrawable_balance` | `80.0000` | $\max(0, 100-20)$ | `80.0000` | **UNTOUCHED (Non-withdrawable)** |
| `Ledger Count` | `0` | `CREDIT_PROMOTION` | `1` | **EXACTLY 1 RECORD** |
| `Ledger Running Balance` | — | Recorded in DB | `120.0000` | **EXACT MATCH with Available** |

#### Scenario B: T-21 Exact Numerical Reconciliation ($Grant = 30$, $Debt = 20 \rightarrow Net = 10$)
Executed with atomic DB transaction, row locking, and real MySQL foreign key references:

```text
[T-21 BEFORE EXECUTION]
  Wallet cash_balance:      100.0000
  Wallet promo_balance:     0.0000
  Wallet promo_debt:        20.0000
  Wallet available_balance: 100.0000
  Wallet total_balance:     100.0000
  Debt remaining_debt:      20.0000
  Debt settled_amount:      0.0000

[T-21 AFTER EXECUTION]
  Wallet cash_balance:      100.0000 (UNTOUCHED)
  Wallet promo_balance:     10.0000 (EXACTLY 10.0000, NO DOUBLING)
  Wallet promo_debt:        0.0000 (0.0000)
  Wallet available_balance: 110.0000 (110.0000)
  Wallet total_balance:     110.0000 (110.0000)
  Debt remaining_debt:      0.0000 (0.0000, status=settled)
  Debt settled_amount:      20.0000 (20.0000)
  Grant original_amount:    30.0000 (30.0000)
  Grant remaining_amount:   10.0000 (10.0000)
  Grant consumed_amount:    20.0000 (20.0000)
  Grant Invariant (30=10+20): VERIFIED VALID
  Ledger promo txns count:  1
  Ledger promo txn amount:  10.0000
```

- **Exit Code:** `0`
- **Environment:** Local MySQL `higest`
- **Artifact:** `packages/Webkul/Wallet/src/Services/WalletService.php`
- **Status:** **PASS**

---

### Item 5: Idempotency under Concurrency & Real MySQL Duplicate Key Collision

#### Command:
```bash
php scripts/verify_gate1_full.php (Section 5)
```

#### Actual Output:
```text
--- 5. REAL DB TEST: CONCURRENT IDEMPOTENCY & DUPLICATE KEY ---
[ATTEMPT 1] Created Usage #1, Grant #1. promo_balance = 15.0000
[ATTEMPT 2] Intercepted Duplicate Key by MySQL constraint (SQLSTATE 23000, Error 1062): Duplicate entry '1-welcome:customer:42' for key 'unique_usage_event'
  Duplicate Key Handled Safely: YES
  Total Usages in DB:           1 (EXACTLY 1)
  Total Grants in DB:           1 (EXACTLY 1)
  Final promo_balance:          15.0000 (EXACTLY 15.0000, NO DOUBLE CREDIT)
```

- **Constraint Type:** Physical MySQL Unique Index on `wallet_promotion_usages(promotion_id, event_key)`
- **Behavior on Collision:** Catches `SQLSTATE 23000` / Error `1062`, recovers existing grant reference, rejects second insertion without altering ledger or balances.
- **Exit Code:** `0`
- **Status:** **PASS**

---

### Item 6: `pending_review` Audit Guard

#### Command:
```bash
php scripts/verify_gate1_full.php (Section 6)
```

#### Actual Output:
```text
--- 6. REAL DB TEST: pending_review AUDIT GUARD ---
[AUDIT GUARD] AccountUnderAuditException correctly caught: Wallet Account #43 is under audit review and cannot receive promotional credits.
  Exception Thrown:             YES
  Wallet promo_balance:         0.0000 (UNTOUCHED = 0.0000)
  Wallet total_balance:         75.0000 (UNTOUCHED = 75.0000)
  Ledger Records Created:       0 (EXACTLY 0)
  Promotion Grants Created:     0 (EXACTLY 0)
```

- **Exit Code:** `0`
- **Status:** **PASS**

---

### Item 7: Full Pest Unit Test Suite Execution

#### Command:
```bash
php vendor/bin/pest packages/Webkul/Wallet/tests/Unit
```

#### Actual Output:
```text
   PASS  Packages\Webkul\Wallet\tests\Unit\WalletGate1Test
  ✓ creditPromotion increments promo_balance, available_balance and total_balance without touching cash_balance  1.98s  
  ✓ creditPromotion throws AccountUnderAuditException for accounts pending review                                0.35s  
  ✓ creditPromotion rejects non-positive amounts and empty descriptions                                          0.23s  
  ✓ T-21 exact numerical reconciliation: Grant=30, Debt=20 results in Net=10 credit and zero debt                0.35s  
  ✓ concurrent idempotency with duplicate key exception recovery                                                 0.37s  

   PASS  Packages\Webkul\Wallet\tests\Unit\WalletServiceTest
  ✓ credit increases available balance and creates transaction                                                   0.41s  
  ✓ debit decreases available balance and creates transaction                                                    0.32s  
  ✓ debit throws InsufficientWalletBalanceException when balance is insufficient                                 0.32s  
  ✓ hold moves balance from available to held                                                                    0.32s  
  ✓ release moves held balance back to available                                                                 0.31s  
  ✓ suspended wallet throws WalletSuspendedException                                                             0.33s  
  ✓ WalletTransaction is immutable after creation                                                                0.23s  
  ✓ adjust creates ADJUSTMENT transaction with reference_transaction_id                                          0.34s  

  Tests:    13 passed (61 assertions)
  Duration: 7.15s
```

- **Exit Code:** `0`
- **Status:** **PASS**

---

### Item 8: Code Style Verification (Pint)

#### Command:
```bash
php vendor/bin/pint packages/Webkul/Wallet
```

#### Actual Output:
```json
{"tool":"pint","result":"passed"}
```

- **Exit Code:** `0`
- **Status:** **PASS**

---

### Item 9: Review of Test Adjustments & Functional Justifications

No test expectations were relaxed or weakened. The following functional adjustments were made to ensure tests execute cleanly against real database environments:

1. **Replaced `RefreshDatabase` with `DatabaseTransactions`**:
   - *Functional Rationale:* `RefreshDatabase` attempts to drop all tables in MySQL and rerun all app migrations from scratch. In a multi-package environment like Bagisto where core seed data and FKs exist, `DatabaseTransactions` executes each test inside a dedicated transaction and rolls back cleanly at the end, providing proper isolation without destructively modifying database schemas.
2. **Added `cash_balance` initialization to Wallet mock factories in `WalletServiceTest`**:
   - *Functional Rationale:* With the introduction of the multi-bucket financial invariant ($Total = Cash + Promo + Unclassified$), any mock account created with $Total = 200$ requires $Cash = 200$ to satisfy balance invariants.
3. **Added `reference_transaction_id` parameter passing to `debit()` in `WalletService::adjust()`**:
   - *Functional Rationale:* Fixed an existing bug in `adjust()` where debit-direction adjustments did not pass the original transaction reference ID to `WalletTransaction::create()`.
4. **Scoped `WalletPromotionUsage::count()` to `$customerId` in Idempotency Test**:
   - *Functional Rationale:* Global `count()` asserted against previous persistent DB test rows. Scoping to the specific customer verifies that exactly 1 usage record exists for that customer without cross-test pollution.

---

## 3. Comprehensive Command Summary Table

| Step / Item | Exact Command | Exit Code | Environment | Artifact | Status |
|---|---|---|---|---|---|
| **1. Migration Run & Schema Check** | `php scripts/verify_gate1_full.php` | `0` | Local MySQL `higest` | `packages/Webkul/Wallet/src/Database/Migrations/*` | **PASS** |
| **2. Rollback & Re-migrate** | `php scripts/verify_gate1_full.php` | `0` | Local MySQL `higest` | `packages/Webkul/Wallet/src/Database/Migrations/*` | **PASS** |
| **3. Real DB `creditPromotion`** | `php scripts/verify_gate1_full.php` | `0` | Local MySQL `higest` | `packages/Webkul/Wallet/src/Services/WalletService.php` | **PASS** |
| **4. Real DB T-21 Reconciliation** | `php scripts/verify_gate1_full.php` | `0` | Local MySQL `higest` | `packages/Webkul/Wallet/src/Services/WalletService.php` | **PASS** |
| **5. Real DB Idempotency & Unique Key** | `php scripts/verify_gate1_full.php` | `0` | Local MySQL `higest` | `packages/Webkul/Wallet/src/Models/WalletPromotionUsage.php` | **PASS** |
| **6. Real DB `pending_review` Guard** | `php scripts/verify_gate1_full.php` | `0` | Local MySQL `higest` | `packages/Webkul/Wallet/src/Exceptions/AccountUnderAuditException.php` | **PASS** |
| **7. Pest Unit Test Suite** | `php vendor/bin/pest packages/Webkul/Wallet/tests/Unit` | `0` | Local Testing Engine | `packages/Webkul/Wallet/tests/Unit/*` | **PASS** |
| **8. Code Style Check** | `php vendor/bin/pint packages/Webkul/Wallet` | `0` | Laravel Pint | `packages/Webkul/Wallet/*` | **PASS** |

---

## 4. Final Conclusion & Sign-Off

Gate 1 has been validated against a real MySQL database:
- All 12 migrations run, rollback, and re-migrate cleanly.
- Database indexes, unique constraints, and foreign keys are verified.
- `creditPromotion()` operates purely with zero-float BCMath arithmetic.
- Numerical reconciliation (T-21) is proven before and after with exact values.
- Idempotency is strictly enforced by MySQL unique key constraints.
- `pending_review` accounts are locked from promotional credits.
- All 13 unit tests pass (61 assertions), Pint code formatting passes with zero violations.

**No work on Gate 2 has started.** Awaiting leadership review and decision.
