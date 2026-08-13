# WALLET PROMOTIONS — INTERNAL SIMULATION REPORT

> **Executive Directive:** Isolated Financial & Operational Simulation Harness
> **Execution Timestamp:** `2026-08-14T02:40:50+03:00`
> **Locked Commit Hash:** `a7ef01b2d80d4a7b09c67e98796c0c9992f2a8dd`
> **Target Simulation Database:** `higest_wallet_promotions_simulation`
> **Overall Simulation Decision:** **PASS**

---

## 1. Environment & Production Safety State

| Safety Parameter | Verified Value | Compliance |
|---|---|:---:|
| `APP_ENV` | `local` | **PASS** |
| `sales.wallet_promotions.mode` | `legacy_only` | **PASS (legacy_only)** |
| `Live Event Listeners` | **Disabled (Guarded by Mode)** | **PASS** |
| `Outbox Worker Daemon` | **Disabled (Manual runOnce Only)** | **PASS** |
| `Outbox Scheduler / Cron` | **Guarded / Disabled for Simulation** | **PASS** |
| `Backfill Engine` | **Disabled** | **PASS** |
| `Simulation Database Isolation` | `higest_wallet_promotions_simulation` | **PASS** |

---

## 2. Mandatory Scenarios Execution Matrix

| Scenario | Target Subsystem | Status | Key Metric / Verification |
|---|---|:---:|---|
| Scenario 1: Welcome Bonus (Single Outbox, Idempotency, Grant Lot) | Wallet Promotions | **PASS ✅** | `{"customer_id":1,"promotion_id":1,"promo_balance_after":10,"cash_balance_after":0,"withdrawable_balance":0,"event_key":"welcome_bonus:customer:1:promo:1"}` |
| Scenario 2: Top-Up Bonus (Pending Guard, 10% Bonus, Purchasing Power vs Withdrawable) | Wallet Promotions | **PASS ✅** | `{"cash_balance":100,"promo_balance":10,"total_purchasing_power":110,"withdrawable_balance":100,"event_key":"topup_bonus:topup:1:promo:2"}` |
| Scenario 3: Order Cashback & Multi-Factor Invoice Verification | Wallet Promotions | **PASS ✅** | `{"pending_invoice_verified":"REJECTED_CLEANLY","paid_invoice_verified":"ACCEPTED_CONFIRMED","contradictory_invoice_verified":"REJECTED_DEFENSIVELY","consistent_invoice_verified":"ACCEPTED_CONFIRMED"}` |
| Scenario 4: Item-Level Refund Reversal & Promo Debt Deficit Creation | Wallet Promotions | **PASS ✅** | `{"reversal_status":"DEFICIT_CONVERTED_TO_DEBT","cash_balance_preserved":500,"promo_debt_recorded":10,"debt_record_id":1}` |
| Scenario 5: T-21 Exact Debt Settlement Reconciliation (20 Debt + 30 Grant = 10 Net) | Wallet Promotions | **PASS ✅** | `{"initial_debt":20,"gross_grant":30,"settled_debt_amount":20,"net_credited_amount":10,"remaining_debt":0,"cash_balance":200,"ledger_transaction_id":5}` |
| Scenario 6: Outbox Worker Idempotency & Re-execution Protection | Wallet Promotions | **PASS ✅** | `{"reexecutions_attempted":5,"grants_count":1,"promo_balance_final":15,"idempotency_status":"STRICTLY_ENFORCED"}` |
| Scenario 7: Expired Lease Recovery & Lock Acquisition | Wallet Promotions | **PASS ✅** | `{"reclaimed_from_worker":"dead-worker-pid-999","reclaimed_by_worker":"recovering-worker-pid-101","final_attempts":2,"promo_balance_credited":25}` |
| Scenario 8: Atomic Rollback on Worker Processing Injected Failure | Wallet Promotions | **PASS ✅** | `{"injected_exception":"SIMULATED_WORKER_CRASH_BEFORE_COMMIT","orphan_usages_count":0,"orphan_grants_count":0,"account_promo_balance":0,"rollback_verification":"100% ATOMIC & CLEAN"}` |
| Scenario 9: pending_review Account Audit Quarantine Guard | Wallet Promotions | **PASS ✅** | `{"quarantine_status":"pending_review","exception_thrown":"Webkul\\Wallet\\Exceptions\\AccountUnderAuditException","promo_balance_preserved":0,"audit_quarantine_guard":"STRICTLY_ENFORCED"}` |
| Scenario 10: Archive-Only Policy & Physical Deletion Prohibition | Wallet Promotions | **PASS ✅** | `{"orm_delete_blocked":true,"query_builder_delete_blocked":true,"archive_transition":"STATUS_ARCHIVED_SUCCESS","audit_log_id":1,"archive_only_policy":"FULLY_COMPLIANT"}` |

---

## 3. Global Financial & Invariant Reconciliation

- **Grant Lot Conservation:** `original_grant == remaining_grant + consumed_grant` (Violations: `0`)
- **Debt Lot Conservation:** `original_debt == remaining_debt + settled_debt` (Violations: `0`)
- **Withdrawable Balance Segregation:** `withdrawable == max(0, cash - held)` (Violations: `0`)
- **Cash Balance Non-Contamination:** Cash is strictly unaltered by promotional operations.

### Synthetic Schema Row Counts

| Table Name | Row Count |
|---|:---:|
| `wallet_promotions` | **9** |
| `wallet_promotion_usages` | **6** |
| `wallet_promotion_grants` | **6** |
| `wallet_promotion_grant_consumptions` | **0** |
| `wallet_promo_debts` | **2** |
| `wallet_promo_debt_settlements` | **1** |
| `wallet_promotion_outbox` | **4** |
| `wallet_promotion_audits` | **1** |
| `wallet_transactions` | **7** |
| `wallet_accounts` | **8** |

---

## 4. Promotion Types Decision Summary

| Promotion Type | Simulation Decision | Rationale |
|---|:---:|---|
| **Welcome Bonus** | **PASS** | Idempotency, grant lot creation, FIFO segregation confirmed. |
| **Top-Up Bonus** | **PASS** | Pending topup ignored, approved topup credited cash 100 + promo 10 at 10%. |
| **Order Subtotal Cashback** | **PASS** | Verified multi-factor Invoice validation (`invoices.state === 'paid'`). |
| **Item-Level / Refund Deficit** | **PASS** | Item-level reversal and deficit conversion to Promo Debt without cash impact. |
| **T-21 Debt Settlement** | **PASS** | Exact numerical reconciliation (20 Debt + 30 Grant = 10 Net promo credit). |
| **Archive-Only & Deletion Guard** | **PASS** | Physical deletes completely rejected across ORM and Query Builder. |

---

## 5. Final Release & Rollout Signoff Boundary

> [!IMPORTANT]
> **Simulation Boundary Enforcement:** This report certifies successful execution of the automated internal simulation on isolated synthetic database `higest_wallet_promotions_simulation`. No live traffic was enabled, no commercial promotions were launched, and all safety freeze invariants remain active.
