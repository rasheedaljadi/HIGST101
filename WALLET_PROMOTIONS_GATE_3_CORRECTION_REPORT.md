# WALLET PROMOTIONS — GATE 3 CORRECTION REPORT

**Document ID:** `WALLET_PROMOTIONS_GATE_3_CORRECTION_REPORT.md`  
**Date:** 2026-08-14  
**Status:** COMPLETED & COMMITTED (Invoice State Verification Corrected, 10 Distinct Integration Tests, 100% Passed)  
**Target Environment:** Isolated Local MySQL Test Schema (`127.0.0.1:3306`, Database: `higest`, Engine: `InnoDB`, PHP: `8.3.33`)  
**Locked Git Commit Hash:** `4d3c5b4895e2f7f15070929c632ec9c31f41f813`

---

## 1. Architectural Correction: Official Invoice State in Bagisto 2.4.x

### Finding & Clarification:
In Bagisto 2.4.x, the official database column on the `invoices` table is **`state`** (constants: `Invoice::STATUS_PAID = 'paid'`, `STATUS_PENDING = 'pending'`, `STATUS_PENDING_PAYMENT = 'pending_payment'`, `STATUS_OVERDUE = 'overdue'`, `STATUS_REFUNDED = 'refunded'`). There is **no physical `status` column** in Bagisto's invoice schema.

### Implementation Resolution (`PaymentVerificationService`):
1. **Authoritative Database Source:** `invoices.state === 'paid'` is the sole official database criteria for promotion eligibility.
2. **Defensive Metadata Guard:** If caller or external system passes a legacy/transient `status` metadata:
   - If `status = 'pending'`, `'failed'`, `'cancelled'`, or `'unpaid'`, it is flagged as a contradictory state and **strictly rejected** (returns `false`, 0 Outbox created).
   - If `status = 'paid'`, it is accepted.
   - If no external metadata is provided, `state === 'paid'` is trusted as the database source of truth.
3. **Cash on Delivery (COD) & Offline Methods:**
   - Uncollected COD invoices have `state = 'pending'`, which is rejected.
   - Collected and verified COD invoices transition to `state = 'paid'`, which is permitted.

---

## 2. Gate 3: 10 Explicit Integration Tests Matrix

| Test Case | Scenario Description | Result | Assertions & Behavior |
|---|---|---|---|
| **Test 1** | **Customer Registration $\rightarrow$ Outbox `welcome_bonus`** | **PASS** | Registration creates pending `welcome_bonus` job in Outbox; wallet balance remains untouched until worker execution. |
| **Test 2** | **Invoice Payment Verification (`PaymentVerificationService`)** | **PASS** | <ul><li>`state = pending` $\rightarrow$ REJECT.</li><li>`state = paid` without metadata $\rightarrow$ ALLOW.</li><li>`state = paid` / `status = pending` (contradictory) $\rightarrow$ REJECT.</li><li>`state = paid` / `status = paid` $\rightarrow$ ALLOW.</li><li>COD uncollected $\rightarrow$ REJECT.</li><li>COD collected (`state = paid`) $\rightarrow$ ALLOW.</li><li>Re-saving paid invoice $\rightarrow$ No duplicate Outbox.</li><li>Multiple invoices on same order $\rightarrow$ Scoped to `invoice_id`.</li></ul> |
| **Test 3** | **Approved Wallet Top-Up $\rightarrow$ Outbox `topup_bonus`** | **PASS** | Approved top-up fixture writes `topup_bonus` outbox record post-commit. |
| **Test 4** | **Item-Level Refund Reversal & Deficit Handling** | **PASS** | Reverses active grant lots FIFO. Deficit exceeding available grant creates `WalletPromoDebt`. `cash_balance` strictly untouched. |
| **Test 5** | **Outbox Worker `runOnce` Job Processing** | **PASS** | Claims pending jobs, transitions to `completed`, updates `processed_at` timestamp. |
| **Test 6** | **5-Way Reconciliation (Outbox $\rightarrow$ Usage $\rightarrow$ Grant $\rightarrow$ Ledger $\rightarrow$ Account)** | **PASS** | Proves exact mathematical balance match across all 5 financial tables. |
| **Test 7** | **Event & Outbox Re-emission (Idempotency)** | **PASS** | Re-processing identical event key produces `is_idempotent = true`, 0 duplicate credit, 0 duplicate ledger. |
| **Test 8** | **Worker Failure, Rollback & Lease Recovery** | **PASS** | Full DB rollback on failure, attempts incremented to 3 $\rightarrow$ status `failed`. Expired lease reclaimed. |
| **Test 9** | **`pending_review` Audit Guard** | **PASS** | Throws `AccountUnderAuditException`; wallet promo balance and ledger protected from modification. |
| **Test 10** | **Legacy Listener Isolation** | **PASS** | Proves `ApplyWalletCashbackListener` is isolated and not dispatched concurrently. |

---

## 3. Test Suite Execution & Output Verification

### Command:
```bash
php vendor/bin/pest packages/Webkul/Wallet/tests/Unit
```

### Actual Output:
```text
   PASS  Packages\Webkul\Wallet\tests\Unit\WalletGate1Test
  ✓ creditPromotion increments promo_balance, available_balance and total_balance without touching cash_balance  1.85s  
  ✓ creditPromotion throws AccountUnderAuditException for accounts pending review                                0.28s  
  ✓ creditPromotion rejects non-positive amounts and empty descriptions                                          0.29s  
  ✓ T-21 exact numerical reconciliation: Grant=30, Debt=20 results in Net=10 credit and zero debt                0.36s  
  ✓ concurrent idempotency with duplicate key exception recovery                                                 0.36s  

   PASS  Packages\Webkul\Wallet\tests\Unit\WalletGate2Test
  ✓ PromotionGrantService calculates reward and creates usage and grant lots with invariant check                0.37s  
  ✓ WalletPromotionOrchestrator settles debt and credits net amount (T-21 end-to-end flow)                       0.35s  
  ✓ WalletPromotionOrchestrator handles idempotency and prevents double credit under re-execution                0.31s  
  ✓ WalletPromotionOutboxWorker claims pending and expired lease jobs and processes them                         0.36s  
  ✓ WalletPromotionOrchestrator rejects accounts under audit (pending_review)                                    0.24s  
  ✓ PromotionGrantService reverses grant lot without altering cash balance and flags deficit                     0.30s  
  ✓ WalletPromotionOutboxWorker rolls back cleanly on exception during job processing                            0.28s  
  ✓ Re-running worker over completed jobs does not duplicate customer balance                                    0.33s  

   PASS  Packages\Webkul\Wallet\tests\Unit\WalletGate3IntegrationTest
  ✓ Scenario 1: Customer registration creates pending welcome_bonus Outbox job                                   0.39s  
  ✓ Scenario 2: Invoice payment confirmation relies on invoices.state, handles defensive metadata, rejects cont… 0.29s  
  ✓ Scenario 3: Approved wallet top-up dispatches event and creates topup_bonus Outbox job after commit          0.29s  
  ✓ Scenario 4: Item-level refund reverses grant lot or creates promo debt deficit without altering cash balanc… 0.32s  
  ✓ Scenario 5: Outbox worker runOnce processes claimed jobs transitioning from pending to completed             0.33s  
  ✓ Scenario 6: 5-way ledger and balance reconciliation matches Outbox, Usage, Grant, Ledger, and Account balan… 0.27s  
  ✓ Scenario 7: Re-emitting event and re-running worker proves strict idempotency and zero duplicate credit      0.46s  
  ✓ Scenario 8: Worker failure triggers complete rollback, increments attempts, and recovers expired lease       0.35s  
  ✓ Scenario 9: pending_review account is strictly protected from promotional credits                            0.32s  
  ✓ Scenario 10: Legacy ApplyWalletCashbackListener is isolated and not executed during new promotional flows    0.30s  

   PASS  Packages\Webkul\Wallet\tests\Unit\WalletServiceTest
  ✓ credit increases available balance and creates transaction                                                   0.44s  
  ✓ debit decreases available balance and creates transaction                                                    0.32s  
  ✓ debit throws InsufficientWalletBalanceException when balance is insufficient                                 0.30s  
  ✓ hold moves balance from available to held                                                                    0.27s  
  ✓ release moves held balance back to available                                                                 0.32s  
  ✓ suspended wallet throws WalletSuspendedException                                                             0.31s  
  ✓ WalletTransaction is immutable after creation                                                                0.32s  
  ✓ adjust creates ADJUSTMENT transaction with reference_transaction_id                                          0.34s  

  Tests:    31 passed (180 assertions)
  Duration: 12.31s
```
- **Exit Code:** `0`

---

## 4. Git Working Tree & Locked Commit Fingerprint

- **Working Tree (`git status --porcelain=v1`):** Clean (all files staged, styled via Pint, and committed).
- **Committed Hash:** `4d3c5b4895e2f7f15070929c632ec9c31f41f813`
- **Commit Message:** `fix(wallet): clarify invoices.state as authoritative, add PaymentVerificationService, and expand Gate 3 to 10 distinct tests`

---

## 5. Strict Safety Affirmations & Hold

- **Live Listeners:** `ApplyWalletCashbackListener` was NOT modified.
- **Event Wiring:** 0 live events wired into production pipelines in `EventServiceProvider`.
- **Feature Flag:** Default remains `sales.wallet_promotions.mode = 'legacy_only'`.
- **Background Processes:** No background worker daemon or cron running.
- **Backfill:** Dormant (0 legacy records processed).
- **Gate 4:** NOT started.

**Execution stopped.** Awaiting leadership review and decision.
