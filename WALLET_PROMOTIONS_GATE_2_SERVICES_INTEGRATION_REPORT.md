# WALLET PROMOTIONS — GATE 2: SERVICES & ISOLATED INTEGRATIONS REPORT

**Report Version:** V1.0  
**Date:** 2026-08-14  
**Status:** COMPLETED & VERIFIED (Gate 2 Services, Outbox Worker, Events & Unit Test Suite 100% Passed)  
**Target Environment:** Isolated Local MySQL Schema (`127.0.0.1:3306`, Database: `higest`, Engine: `InnoDB`, PHP: `8.3.33`)  
**Git Working Tree Fingerprint:** `c9e2b4e6d35d5b09d5b3ee33925c5dd4c98b693e`

---

## 1. Safety Rails & Strict Boundaries Confirmation

| Boundary / Condition | Verified Status | Evidence |
|---|---|---|
| **Old Listeners (`ApplyWalletCashbackListener`)** | **UNTOUCHED** | Old listener was not edited, not dispatched, and not altered. |
| **Live Event Registration** | **NOT CONNECTED** | Event classes created in isolation; 0 live event listeners registered in `EventServiceProvider`. |
| **Feature Flag** | **LEGACY_ONLY** | Default mode remains `sales.wallet_promotions.mode = 'legacy_only'`. Tests set mode dynamically per test case. |
| **Background Worker Daemon** | **DORMANT** | Worker is not running as a daemon or cron task; tested via isolated `runOnce()` calls only. |
| **Backfill Script** | **DORMANT** | 0 legacy customer transactions processed; 0 backfill commands run. |
| **Admin / Customer UI** | **NOT CREATED** | Scope strictly limited to service layer, events, and isolated unit tests. |

---

## 2. Inventory of Created & Modified Files

### A. New Service Layer Classes (`packages/Webkul/Wallet/src/Services/`):
1. `PromotionGrantService.php`:
   - Calculates fixed and percentage rewards using BCMath (`scale: 4`).
   - Verifies customer eligibility, budget limits, spend constraints, and validity windows.
   - Creates `WalletPromotionUsage` and initial `WalletPromotionGrant` records.
   - Reverses grant lots (`reverseGrantLot`) on refund while maintaining invariant $\text{original} = \text{remaining} + \text{consumed}$.
2. `WalletDebtService.php`:
   - Creates `WalletPromoDebt` records on refund deficits.
   - Retrieves active debts FIFO.
   - Settles active debts against new grant lots (`settleDebtFromGrant`), generating immutable `WalletPromoDebtSettlement` records.
   - Multi-lot settlement planner (`settleActiveDebtsForGrantAmount`).
   - Syncs and reconciles `wallet_accounts.promo_debt`.
3. `WalletPromotionOrchestrator.php`:
   - High-level coordinator for promotion grant execution.
   - Feature flag guard (`sales.wallet_promotions.mode`).
   - Row-level pessimistic locking (`lockForUpdate()`).
   - Guard against accounts under audit (`AccountUnderAuditException`).
   - Complete idempotency with recovery from race-condition duplicate key errors.
   - Orchestrates multi-lot debt settlement and calls `WalletService::creditPromotion()` for net amount only.
4. `WalletPromotionOutboxWorker.php`:
   - Atomically claims pending or expired lease jobs (`claimJobs()`).
   - Manages `locked_at`, `locked_by`, `lease_expires_at`, and `attempts`.
   - Idempotently processes outbox payloads via `WalletPromotionOrchestrator`.
   - Handles retries, records `last_error`, and transitions failed jobs to `failed` upon exceeding max attempts.

### B. Isolated Event Classes (`packages/Webkul/Wallet/src/Events/`):
1. `CustomerRegisteredForPromotion.php`
2. `OrderPaymentConfirmedForPromotion.php`
3. `WalletTopUpApprovedForPromotion.php`
4. `OrderRefundProcessedForPromotion.php`

### C. Test Suites (`packages/Webkul/Wallet/tests/Unit/`):
1. `WalletGate2Test.php` (8 comprehensive Gate 2 isolated tests)
2. `WalletGate1Test.php` (5 Gate 1 regression tests)
3. `WalletServiceTest.php` (8 core WalletService regression tests)

---

## 3. Detailed Service Architecture & Execution Flows

### Flow 1: Promotion Evaluation & Grant Orchestration (`WalletPromotionOrchestrator`)
```text
1. Event Trigger (Outbox Payload or Test Call)
   ↓
2. Feature Flag Check (sales.wallet_promotions.mode) -> Skip if legacy_only
   ↓
3. DB::transaction + WalletAccount::lockForUpdate()
   ↓
4. Audit Review Guard (throws AccountUnderAuditException if pending_review)
   ↓
5. Idempotency Check (WalletPromotionUsage where event_key = $eventKey)
   ├── If Found -> Return Existing Grant (is_idempotent = true)
   └── If Not Found -> Proceed
       ↓
6. Eligibility & Reward Calculation (PromotionGrantService::calculateReward via BCMath)
   ↓
7. Debt Settlement Planning (WalletDebtService::settleActiveDebtsForGrantAmount)
   ├── Calculate Total Active Debt to Settle
   └── Determine Net Credited Amount = Grant - Settled Debt
       ↓
8. Insert Usage & Grant (WalletPromotionGrant starts with original=reward, consumed=0)
   ↓
9. Execute Planned Settlements (WalletDebtService::settleDebtFromGrant)
   ├── Grant: remaining decreases, consumed increases
   ├── Debt: remaining decreases, settled increases (status -> settled if 0)
   └── Insert WalletPromoDebtSettlement
       ↓
10. Reconcile wallet.promo_debt = SUM(remaining_debt)
   ↓
11. Credit Net to Wallet (WalletService::creditPromotion -> +promo_balance, exactly 1 Ledger txn)
   ↓
12. Commit Transaction
```

### Flow 2: Outbox Claim / Lease & Worker Execution (`WalletPromotionOutboxWorker`)
```text
1. Query Pending OR Expired Lease Jobs:
   SELECT id FROM wallet_promotion_outbox
   WHERE (status = 'pending') OR (status = 'processing' AND lease_expires_at < NOW())
   ORDER BY id ASC LIMIT :batchSize FOR UPDATE
   ↓
2. Atomic Lease Claim:
   UPDATE wallet_promotion_outbox
   SET status = 'processing',
       locked_at = NOW(),
       locked_by = :workerId,
       lease_expires_at = NOW() + :leaseSeconds,
       attempts = attempts + 1
   WHERE id IN (:claimedIds)
   ↓
3. Execute Job Processing:
   ├── On Success: status = 'completed', processed_at = NOW(), last_error = NULL
   └── On Failure: 
       ├── If attempts < maxAttempts: status = 'pending', record last_error
       └── If attempts >= maxAttempts: status = 'failed', record last_error
```

---

## 4. Test Suite Execution & Output Verification

### Full Pest Test Suite Run (Gate 1 + Gate 2 + Core WalletService):
```bash
php vendor/bin/pest packages/Webkul/Wallet/tests/Unit
```

#### Actual Output:
```text
   PASS  Packages\Webkul\Wallet\tests\Unit\WalletGate1Test
  ✓ creditPromotion increments promo_balance, available_balance and total_balance without touching cash_balance  1.58s  
  ✓ creditPromotion throws AccountUnderAuditException for accounts pending review                                0.33s  
  ✓ creditPromotion rejects non-positive amounts and empty descriptions                                          0.32s  
  ✓ T-21 exact numerical reconciliation: Grant=30, Debt=20 results in Net=10 credit and zero debt                0.35s  
  ✓ concurrent idempotency with duplicate key exception recovery                                                 0.28s  

   PASS  Packages\Webkul\Wallet\tests\Unit\WalletGate2Test
  ✓ PromotionGrantService calculates reward and creates usage and grant lots with invariant check                0.37s  
  ✓ WalletPromotionOrchestrator settles debt and credits net amount (T-21 end-to-end flow)                       0.40s  
  ✓ WalletPromotionOrchestrator handles idempotency and prevents double credit under re-execution                0.32s  
  ✓ WalletPromotionOutboxWorker claims pending and expired lease jobs and processes them                         0.37s  
  ✓ WalletPromotionOrchestrator rejects accounts under audit (pending_review)                                    0.30s  
  ✓ PromotionGrantService reverses grant lot without altering cash balance and flags deficit                     0.33s  
  ✓ WalletPromotionOutboxWorker rolls back cleanly on exception during job processing                            0.32s  
  ✓ Re-running worker over completed jobs does not duplicate customer balance                                    0.29s  

   PASS  Packages\Webkul\Wallet\tests\Unit\WalletServiceTest
  ✓ credit increases available balance and creates transaction                                                   0.41s  
  ✓ debit decreases available balance and creates transaction                                                    0.34s  
  ✓ debit throws InsufficientWalletBalanceException when balance is insufficient                                 0.31s  
  ✓ hold moves balance from available to held                                                                    0.32s  
  ✓ release moves held balance back to available                                                                 0.35s  
  ✓ suspended wallet throws WalletSuspendedException                                                             0.34s  
  ✓ WalletTransaction is immutable after creation                                                                0.34s  
  ✓ adjust creates ADJUSTMENT transaction with reference_transaction_id                                          0.43s  

  Tests:    21 passed (125 assertions)
  Duration: 9.01s
```
- **Exit Code:** `0`

---

## 5. Specific Feature Verification Results

### 1. T-21 Numerical Reconciliation via `WalletPromotionOrchestrator`
- **Initial State:** $Cash = 100.0000$, $Promo = 0.0000$, $PromoDebt = 20.0000$, $Available = 100.0000$, $Total = 100.0000$.
- **Action:** Grant 30.00 SAR Cashback on 100.00 SAR Order.
- **Settlement Result:**
  - $DebtRemaining = 0.0000$, $DebtSettled = 20.0000$, Status = `settled`.
  - $GrantOriginal = 30.0000$, $GrantRemaining = 10.0000$, $GrantConsumed = 20.0000$.
  - $WalletCash = 100.0000$ (Untouched).
  - $WalletPromo = 10.0000$ (Net credited).
  - $WalletPromoDebt = 0.0000$.
  - $WalletAvailable = 110.0000$, $WalletTotal = 110.0000$.
  - $LedgerCount = 1$ transaction ($Type = CREDIT\_PROMOTION, Amount = 10.0000$).

### 2. Outbox Claim, Expired Lease Recovery & Retry
- **Pending Job:** Successfully claimed and transitioned to `completed`.
- **Expired Lease Job (`lease_expires_at < NOW()`):** Successfully reclaimed by worker, incremented `attempts` from 1 to 2, and transitioned to `completed`.
- **Failure Recovery & Rollback:** When an unhandled error occurs (e.g. audited account), the worker catches the error, rolls back all database modifications, increments attempts, and records `last_error`.

### 3. Grant Lot Refund / Reversal
- Reversing 5.00 SAR from 20.00 SAR grant lot $\rightarrow$ Remaining: $15.0000$, Consumed: $5.0000$, Deficit: $0.0000$.
- Reversing 25.00 SAR from remaining 15.00 SAR $\rightarrow$ Remaining: $0.0000$, Consumed: $20.0000$, Deficit: $10.0000$.
- Customer `cash_balance` remained strictly untouched throughout.

---

## 6. Code Style (Pint) Verification

- **Command:** `php vendor/bin/pint packages/Webkul/Wallet`
- **Exit Code:** `0`
- **Output:**
```json
{"tool":"pint","result":"passed"}
```

---

## 7. Git Status & Working Tree Diff Stat

### `git diff --stat`:
```text
 packages/Webkul/Wallet/src/Database/Factories/WalletAccountFactory.php        |  17 +++-
 packages/Webkul/Wallet/src/Database/Migrations/2026_08_13_000004_create_wallet_promotion_grants_table.php | 10 +++
 packages/Webkul/Wallet/src/Database/Migrations/2026_08_13_000007_create_wallet_promo_debts_table.php       | 10 +++
 packages/Webkul/Wallet/src/Database/Migrations/2026_08_13_000009_create_wallet_promotion_outbox_table.php |  2 +-
 packages/Webkul/Wallet/src/Models/WalletAccount.php                         |  49 +++++++++-
 packages/Webkul/Wallet/src/Models/WalletTransaction.php                     |   4 +-
 packages/Webkul/Wallet/src/Providers/ModuleServiceProvider.php              |  20 ++++
 packages/Webkul/Wallet/src/Services/WalletService.php                       | 108 +++++++++++++++++++--
 packages/Webkul/Wallet/tests/Unit/WalletServiceTest.php                     |  76 ++++++++++++++-
 9 files changed, 275 insertions(+), 22 deletions(-)
```

---

## 8. Final Conclusion & Stop

Gate 2 is complete:
- `PromotionGrantService`, `WalletDebtService`, `WalletPromotionOrchestrator`, `WalletPromotionOutboxWorker` fully implemented with pure BCMath arithmetic.
- Isolated Events created without hooking to live pipelines.
- Outbox Claim/Lease/Retry and Invariant-safe Grant Reversal verified.
- Full regression suite passes (**21 tests, 125 assertions**, Exit Code `0`).
- Code formatting passes Pint checks with 0 violations.

**Execution stopped.** Awaiting leadership review and decision. No Gate 3 work has been started.
