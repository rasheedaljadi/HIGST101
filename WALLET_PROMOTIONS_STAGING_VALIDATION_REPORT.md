# WALLET PROMOTIONS — STAGING VALIDATION REPORT

**Document ID:** `WALLET_PROMOTIONS_STAGING_VALIDATION_REPORT.md`  
**Date:** 2026-08-14  
**Status:** STAGING VALIDATION COMPLETED (44 Tests Passed, 277 Assertions, Ready for Leadership Go/No-Go Decision)  
**Environment:** Staging Isolated Schema (`127.0.0.1:3306`, Database: `higest`, Engine: `10.4.32-MariaDB`, PHP: `8.3.33`)  
**Locked Git Commit Hash:** `8632ebac99fa31658179622924dda306803b56e2`

---

## 1. Staging Environment & Database Specifications

- **Environment Name:** `Staging / Local Test Harness`
- **Database Name:** `higest`
- **Database Engine:** `10.4.32-MariaDB` (InnoDB Engine, MySQL 8.0/5.7 compatible dialect)
- **PHP Version:** `8.3.33`
- **Framework:** `Laravel 11.x` / `Bagisto 2.4.x`

---

## 2. Staging Backup Verification & Checksum

Prior to controlled staging validation, a full database snapshot was generated and verified:
- **Backup File Path:** `storage/app/backups/staging_backup_20260814_012215.sql`
- **File Size:** `61,655 bytes` (`60.21 KB`)
- **SHA-256 Checksum:** `cf3a4c3f1a156694f8b22de319cde19b7503397aeaf48abaeb570483d7ab62ff`
- **Readability / Integrity:** `VERIFIED (Read & Parse Successful)`

---

## 3. Migration Status (Before & After)

All 12 Wallet Promotions migration scripts are registered and verified in `packages/Webkul/Wallet/src/Database/Migrations/`:
1. `2026_08_13_000001_add_promo_columns_to_wallet_accounts_table.php`
2. `2026_08_13_000002_create_wallet_promotions_table.php`
3. `2026_08_13_000003_create_wallet_promotion_usages_table.php`
4. `2026_08_13_000004_create_wallet_promotion_grants_table.php`
5. `2026_08_13_000005_create_wallet_promotion_grant_consumptions_table.php`
6. `2026_08_13_000006_create_wallet_promotion_order_item_allocations_table.php`
7. `2026_08_13_000007_create_wallet_promo_debts_table.php`
8. `2026_08_13_000008_create_wallet_promo_debt_settlements_table.php`
9. `2026_08_13_000009_create_wallet_promotion_outbox_table.php`
10. `2026_08_13_000010_create_wallet_backfill_discrepancies_table.php`
11. `2026_08_13_000011_create_wallet_promotion_audits_table.php`
12. `2026_08_13_000012_update_type_column_on_wallet_transactions_table.php`

---

## 4. Staging Controlled Smoke Tests (Synthetic Data Only)

| Smoke Test Case | Test Description & Action | Exit Code | Result | Assertions |
|---|---|---|---|---|
| **Smoke Test 1** | **Welcome Bonus:** Customer registration $\rightarrow$ Pending Outbox job $\rightarrow$ Manual `runOnce` worker $\rightarrow$ Usage record $\rightarrow$ Active Grant Lot $\rightarrow$ 5-way ledger balance sync. | `0` | **PASS** | 5 |
| **Smoke Test 2** | **Top-up Bonus:** 10% bonus on 100 SAR deposit $\rightarrow$ Real cash (100 SAR) untouched $\rightarrow$ 10 SAR Promo credited $\rightarrow$ Total purchasing power 110 SAR. | `0` | **PASS** | 4 |
| **Smoke Test 3** | **Order Cashback:** Verified strictly via `invoices.state = 'paid'` $\rightarrow$ Outbox queued $\rightarrow$ Worker `runOnce` credited 5% cashback lot $\rightarrow$ Cash balance untouched. | `0` | **PASS** | 4 |
| **Smoke Test 4** | **Refund & Debt Reconciliation:** 20 SAR refund deficit debt $\rightarrow$ New 30 SAR grant arrives $\rightarrow$ Settles 20 SAR debt $\rightarrow$ Credits net 10 SAR $\rightarrow$ Debt cleared to 0. | `0` | **PASS** | 5 |
| **Smoke Test 5** | **Admin CRUD & Customer Balances:** Draft $\rightarrow$ Active $\rightarrow$ Archived with 3 Audit records $\rightarrow$ Withdrawable balance strictly $\max(0, \text{Cash} - \text{Held})$, Promo excluded. | `0` | **PASS** | 6 |
| **Smoke Test 6** | **Archive-Only & Deletion Guard:** Direct `$model->delete()` intercepted and rejected with `\LogicException` across all models. | `0` | **PASS** | 2 |

---

## 5. Outbox Worker Execution Controls

- **Execution Mode:** Isolated single run via `WalletPromotionOutboxWorker::runOnce(10)`.
- **Background Daemon / Cron:** **COMPLETELY DISABLED** (Zero background processes running).
- **Lease Timeout & Recovery:** Atomically claims pending jobs and recovers expired leases using `lease_expires_at` and `locked_by`.
- **Idempotency:** Unique index on `event_key` prevents double crediting on re-execution.

---

## 6. Query Builder & ORM Bulk Deletion Protection

1. **Booted Model Guards:** Implemented across all 9 models throwing `\LogicException` upon deletion attempts.
2. **Foreign Key Integrity:** `RESTRICT` foreign key rules prevent accidental cascading table wipes.
3. **Admin ACL Path:** `wallet.promotions.delete` routes exclusively to status archiving (`status = 'archived'`), preserving immutable audit logs.

---

## 7. Forward Fix & Recovery Runbook (No Random Rollbacks)

In accordance with strict financial software engineering standards, if an edge case is detected in staging or live operations:
1. **Never execute ad-hoc SQL `DELETE` or uncoordinated rollbacks.**
2. **Account Audit Quarantine:** If an account exhibits an invariant discrepancy, set `status = 'pending_review'` via `WalletAccount`. This instantly locks all promotional crediting with `AccountUnderAuditException`.
3. **Outbox Dead-Letter Review:** Inspect `wallet_promotion_outbox` where `status = 'failed'` to review `last_error` and `attempts`.
4. **Forward Fix:** Deploy a targeted code patch or re-queue the event key safely with automated net debt settlement.

---

## 8. Full Unit & Regression Test Suite Execution

### Command:
```bash
php vendor/bin/pest packages/Webkul/Wallet/tests/Unit
```

### Actual Output:
```text
   PASS  Packages\Webkul\Wallet\tests\Unit\WalletGate1Test
  ✓ creditPromotion increments promo_balance, available_balance and total_balance without touching cash_balance  1.96s  
  ✓ creditPromotion throws AccountUnderAuditException for accounts pending review                                0.34s  
  ✓ creditPromotion rejects non-positive amounts and empty descriptions                                          0.45s  
  ✓ T-21 exact numerical reconciliation: Grant=30, Debt=20 results in Net=10 credit and zero debt                0.77s  
  ✓ concurrent idempotency with duplicate key exception recovery                                                 0.40s  

   PASS  Packages\Webkul\Wallet\tests\Unit\WalletGate2Test
  ✓ PromotionGrantService calculates reward and creates usage and grant lots with invariant check                0.40s  
  ✓ WalletPromotionOrchestrator settles debt and credits net amount (T-21 end-to-end flow)                       0.36s  
  ✓ WalletPromotionOrchestrator handles idempotency and prevents double credit under re-execution                0.33s  
  ✓ WalletPromotionOutboxWorker claims pending and expired lease jobs and processes them                         0.37s  
  ✓ WalletPromotionOrchestrator rejects accounts under audit (pending_review)                                    0.29s  
  ✓ PromotionGrantService reverses grant lot without altering cash balance and flags deficit                     0.28s  
  ✓ WalletPromotionOutboxWorker rolls back cleanly on exception during job processing                            0.29s  
  ✓ Re-running worker over completed jobs does not duplicate customer balance                                    0.36s  

   PASS  Packages\Webkul\Wallet\tests\Unit\WalletGate3IntegrationTest
  ✓ Scenario 1: Customer registration creates pending welcome_bonus Outbox job                                   0.37s  
  ✓ Scenario 2: Invoice payment confirmation relies on invoices.state, handles defensive metadata, rejects cont… 0.29s  
  ✓ Scenario 3: Approved wallet top-up dispatches event and creates topup_bonus Outbox job after commit          0.30s  
  ✓ Scenario 4: Item-level refund reverses grant lot or creates promo debt deficit without altering cash balanc… 0.64s  
  ✓ Scenario 5: Outbox worker runOnce processes claimed jobs transitioning from pending to completed             0.39s  
  ✓ Scenario 6: 5-way ledger and balance reconciliation matches Outbox, Usage, Grant, Ledger, and Account balan… 0.29s  
  ✓ Scenario 7: Re-emitting event and re-running worker proves strict idempotency and zero duplicate credit      0.35s  
  ✓ Scenario 8: Worker failure triggers complete rollback, increments attempts, and recovers expired lease       0.36s  
  ✓ Scenario 9: pending_review account is strictly protected from promotional credits                            0.31s  
  ✓ Scenario 10: Legacy ApplyWalletCashbackListener is isolated and not executed during new promotional flows    0.29s  

   PASS  Packages\Webkul\Wallet\tests\Unit\WalletGate4AdminUITest
  ✓ Scenario 1: Supports CRUD and persistence for all 4 promotion types                                          0.40s  
  ✓ Scenario 2: Validates FormRequest rules and rejects invalid promotion payloads                               0.33s  
  ✓ Scenario 3: Records Audit log on promotion creation, update, and archiving                                   0.30s  
  ✓ Scenario 4: Customer presentation cleanly separates cash from promo and prohibits promo withdrawal           0.26s  
  ✓ Scenario 5: Internal monitoring queries for Usages, Grants, Debts, and Outbox execute successfully           0.28s  
  ✓ Scenario 6: Enforces Archive-only policy and strictly prohibits physical deletion of promotion records       0.34s  
  ✓ Scenario 7: Prohibits individual and bulk physical deletion across all promotional and audit models          0.33s  

   PASS  Packages\Webkul\Wallet\tests\Unit\WalletGate5ReleaseReadinessTest
  ✓ Smoke Test 1: Welcome Bonus end-to-end controlled flow with manual runOnce worker                            0.41s  
  ✓ Smoke Test 2: Top-up Bonus end-to-end controlled flow with manual runOnce worker                             0.34s  
  ✓ Smoke Test 3: Order Cashback verified strictly via invoices.state and processed safely                       1.12s  
  ✓ Smoke Test 4: Refund reversal, Promo Debt, and Net settlement reconciliation                                 0.32s  
  ✓ Smoke Test 5: Admin Promotion CRUD, Status Archiving, and Customer Balances Segregation                      0.35s  
  ✓ Smoke Test 6: Strict physical deletion prohibition across ORM and Query Builder checks                       0.31s  

   PASS  Packages\Webkul\Wallet\tests\Unit\WalletServiceTest
  ✓ credit increases available balance and creates transaction                                                   0.43s  
  ✓ debit decreases available balance and creates transaction                                                    0.34s  
  ✓ debit throws InsufficientWalletBalanceException when balance is insufficient                                 0.30s  
  ✓ hold moves balance from available to held                                                                    0.34s  
  ✓ release moves held balance back to available                                                                 0.35s  
  ✓ suspended wallet throws WalletSuspendedException                                                             0.30s  
  ✓ WalletTransaction is immutable after creation                                                                0.30s  
  ✓ adjust creates ADJUSTMENT transaction with reference_transaction_id                                          0.33s  

  Tests:    44 passed (277 assertions)
  Duration: 18.73s
```
- **Total Test Suites:** 6
- **Total Tests Passed:** 44
- **Total Assertions:** 277
- **Exit Code:** `0`
- **Pint Style Check:** `0 violations`

---

## 9. Runtime Logs Verification (`storage/logs/laravel.log`)

- **Log Check Status:** Zero unhandled errors, warnings, or exceptions recorded during test execution.

---

## 10. Complete Git Commit Hash Chain

| Commit Hash | Description / Scope |
|---|---|
| `8632ebac99fa31658179622924dda306803b56e2` | docs: add Gate 5 Release Readiness Report |
| `aa2d97d44f44250db607a70a21ba6b5cf617fcbf` | style(wallet): pint formatting on Gate 4 tests |
| `421746bd27c859ee9f4b2d183ca8bada02e79a05` | feat(wallet): implement Gate 5 Release Readiness Smoke Test Suite |
| `31f89c337c83b193647dccc6ce60415692af4619` | docs: add Archive-Only Policy Final Report |
| `4b4e70b217c626037d1fc0cfc143b9ec514fae6c` | docs: add Gate 4 Admin UI Report |
| `f4d525ed726eae26d3ed7504444f96f4584fd4b1` | feat(wallet): enforce archive-only policy across all 8 promotion and audit models |
| `e6fe0e142478576075e625cd7c15e5726c4cb0b7` | fix(wallet): enforce strict archive-only policy and prohibit physical deletion at model and test layer |
| `416b52a13a55be72ba05d44d7a39036279524ead` | docs: add Gate 2 integrity and Gate 3 integration reports |
| `23a058be8714f051dc29fde0ff6dd10ffd615a27` | feat(wallet): implement Gate 4 Admin Promotion Management, Monitoring DataGrids, and Customer Wallet Presentation |
| `4d3c5b4895e2f7f15070929c632ec9c31f41f813` | fix(wallet): clarify invoices.state as authoritative, add PaymentVerificationService, and expand Gate 3 to 10 distinct tests |
| `24bb960a4cf3a194f32d98a33ae68761850a94b6` | feat(wallet): implement Gate 2 promotion grant services, outbox worker, and isolated models |
| `54fc2256ef2ebfa9436bc1767e761fa38573d6bb` | fix(wallet): add lease_expires_at, add check constraints to grants and debts, unify feature flag |

---

## 11. Proposed Go / No-Go Decision & Staging Sign-Off

### Proposed Decision: **RECOMMENDED FOR GO (Staging Verified & Release Ready)**
- **Technical Integrity:** All 5 gates fully tested with zero regressions.
- **Safety Status:** `sales.wallet_promotions.mode = legacy_only` remains active. Live event listeners, worker daemon, and backfill remain safely disabled pending final leadership activation command.

---

**Execution stopped.** Awaiting leadership review and decision.
