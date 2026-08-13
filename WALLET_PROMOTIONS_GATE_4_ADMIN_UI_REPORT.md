# WALLET PROMOTIONS — GATE 4: ADMIN PROMOTION MANAGEMENT & CUSTOMER WALLET PRESENTATION REPORT

**Document ID:** `WALLET_PROMOTIONS_GATE_4_ADMIN_UI_REPORT.md`  
**Date:** 2026-08-14  
**Status:** COMPLETED & COMMITTED (Admin UI, Monitoring Screens, Customer Presentation & 100% Tests Passing)  
**Target Environment:** Isolated Local MySQL Test Schema (`127.0.0.1:3306`, Database: `higest`, Engine: `InnoDB`, PHP: `8.3.33`)  
**Locked Git Commit Hash:** `416b52a13a55be72ba05d44d7a39036279524ead`

---

## 1. Audit Findings of Existing Bagisto Admin CRUD & UI Patterns

During the initial audit of Bagisto 2.4.x and the `Webkul\Wallet` package, the following architecture patterns were verified and adopted:
1. **DataGrids:** Extend `Webkul\DataGrid\DataGrid`, configure columns with typing, searchability, and filterability, and use `prepareQueryBuilder()` with joins to `customers` or `wallet_promotions`.
2. **Controllers & Authorization:** Controllers extend `Illuminate\Routing\Controller` and guard endpoints with `bouncer()->hasPermission('...')` returning `403` on unauthorized requests.
3. **Validation (Form Requests):** Form Requests extend `Illuminate\Foundation\Http\FormRequest`, implementing `authorize()` and standard Bagisto validation rules with Arabic/English locale labels.
4. **Audit Trail:** Admin mutations (creation, modification, archiving) record immutable audit entries in `wallet_promotion_audits` capturing `old_values`, `new_values`, `ip_address`, and `admin_user_id`.
5. **Customer Storefront Presentation:** Separates real cash (`cash_balance`), withdrawable liquid cash (`withdrawable_balance = cash_balance - held_balance`), promotional shopping credits (`promo_balance`), and total purchasing power (`available_balance = cash_balance + promo_balance`).

---

## 2. Modified & Newly Created Files

### A. Admin Form Requests (`packages/Webkul/Wallet/src/Http/Requests/Admin/`):
- `StoreWalletPromotionRequest.php`: Validates promotion creation payloads (all 4 types, date ranges, constraints, positive rewards).
- `UpdateWalletPromotionRequest.php`: Validates promotion update payloads.

### B. Admin DataGrids (`packages/Webkul/Wallet/src/DataGrids/`):
- `WalletPromotionsDataGrid.php`: Main promotion campaign table.
- `WalletPromotionUsagesDataGrid.php`: Internal tracking of promotion usages per customer/event.
- `WalletPromotionGrantsDataGrid.php`: Active grant lots with remaining/consumed tracking.
- `WalletPromoDebtsDataGrid.php`: Internal audit of promo deficit debts.
- `WalletPromotionOutboxDataGrid.php`: Queue monitoring of outbox events, attempts, and leases.

### C. Admin Controllers (`packages/Webkul/Wallet/src/Http/Controllers/Admin/`):
- `WalletPromotionController.php`: CRUD operations, lifecycle management (`draft`, `active`, `inactive`, `archived`), and audit trail generation.
- `WalletPromotionMonitoringController.php`: Internal monitoring dashboard and DataGrid endpoints.

### D. Storefront Presentation (`packages/Webkul/Wallet/`):
- `Http/Controllers/Shop/WalletController.php`: Updated balance calculations separating Cash, Promo, Withdrawable, and Held.
- `Resources/views/shop/index.blade.php`: Upgraded 4-card balance presentation strictly isolating cash from non-withdrawable promo credits.

### E. Configuration & Routes (`packages/Webkul/Wallet/src/`):
- `Config/acl.php`: Registered permissions for `wallet.promotions`, `wallet.promotions.create`, `edit`, `delete`, `view`, and `monitoring`.
- `Config/menu.php`: Registered navigation items for promotions and internal monitoring.
- `Routes/admin-wallet-routes.php`: Registered admin promotion and monitoring routes.

### F. Admin Blade Views (`packages/Webkul/Wallet/src/Resources/views/admin/`):
- `promotions/index.blade.php`, `create.blade.php`, `edit.blade.php`.
- `monitoring/index.blade.php`, `usages.blade.php`, `grants.blade.php`, `debts.blade.php`, `outbox.blade.php`.

### G. Gate 4 Unit Test Suite:
- `packages/Webkul/Wallet/tests/Unit/WalletGate4AdminUITest.php`.

---

## 3. ACL, Validation Rules & Presentation Guardrails

### A. Promotional Types Supported:
1. `welcome_bonus`: Registration welcome reward.
2. `topup_bonus`: Deposit bonus on approved wallet top-ups.
3. `order_subtotal_cashback`: Standard subtotal cashback on paid orders.
4. `order_conditional_cashback`: Item-level / SKU-conditional cashback on paid orders.

### B. Validation Rules:
- `reward_value`: Minimum `0.0001` (negative values rejected).
- `status`: One of `draft`, `active`, `inactive`, `archived`.
- `action_type`: One of `fixed`, `percentage`.
- `ends_till`: Must be `after_or_equal:starts_from`.
- Financial constraints: `min_spend_amount`, `max_reward_amount`, `total_budget`, `usage_limit`, `usage_per_customer`, `priority`, `grant_validity_days`.

### C. Customer Presentation Guardrail:
- **Rule:** Promotional balance (`promo_balance`) is **NEVER** displayed as withdrawable or eligible for payout.
- **Formula:** $\text{Withdrawable Balance} = \max(0, \text{cash\_balance} - \text{held\_balance})$.
- **Storefront Display:** Clearly labeled as "رصيد المكافآت (غير قابل للسحب - مخصص للمشتريات فقط)".

---

## 4. Test Suite Execution & Output Verification

### Full Pest Test Suite Run (Gate 1 + Gate 2 + Gate 3 + Gate 4 + Core WalletService):
```bash
php vendor/bin/pest packages/Webkul/Wallet/tests/Unit
```

### Actual Output:
```text
   PASS  Packages\Webkul\Wallet\tests\Unit\WalletGate1Test
  ✓ creditPromotion increments promo_balance, available_balance and total_balance without touching cash_balance  2.02s  
  ✓ creditPromotion throws AccountUnderAuditException for accounts pending review                                0.35s  
  ✓ creditPromotion rejects non-positive amounts and empty descriptions                                          0.36s  
  ✓ T-21 exact numerical reconciliation: Grant=30, Debt=20 results in Net=10 credit and zero debt                0.44s  
  ✓ concurrent idempotency with duplicate key exception recovery                                                 0.39s  

   PASS  Packages\Webkul\Wallet\tests\Unit\WalletGate2Test
  ✓ PromotionGrantService calculates reward and creates usage and grant lots with invariant check                0.31s  
  ✓ WalletPromotionOrchestrator settles debt and credits net amount (T-21 end-to-end flow)                       0.35s  
  ✓ WalletPromotionOrchestrator handles idempotency and prevents double credit under re-execution                0.34s  
  ✓ WalletPromotionOutboxWorker claims pending and expired lease jobs and processes them                         0.39s  
  ✓ WalletPromotionOrchestrator rejects accounts under audit (pending_review)                                    0.36s  
  ✓ PromotionGrantService reverses grant lot without altering cash balance and flags deficit                     0.40s  
  ✓ WalletPromotionOutboxWorker rolls back cleanly on exception during job processing                            0.32s  
  ✓ Re-running worker over completed jobs does not duplicate customer balance                                    0.37s  

   PASS  Packages\Webkul\Wallet\tests\Unit\WalletGate3IntegrationTest
  ✓ Scenario 1: Customer registration creates pending welcome_bonus Outbox job                                   0.38s  
  ✓ Scenario 2: Invoice payment confirmation relies on invoices.state, handles defensive metadata, rejects cont… 0.31s  
  ✓ Scenario 3: Approved wallet top-up dispatches event and creates topup_bonus Outbox job after commit          0.25s  
  ✓ Scenario 4: Item-level refund reverses grant lot or creates promo debt deficit without altering cash balanc… 0.37s  
  ✓ Scenario 5: Outbox worker runOnce processes claimed jobs transitioning from pending to completed             0.53s  
  ✓ Scenario 6: 5-way ledger and balance reconciliation matches Outbox, Usage, Grant, Ledger, and Account balan… 0.38s  
  ✓ Scenario 7: Re-emitting event and re-running worker proves strict idempotency and zero duplicate credit      0.40s  
  ✓ Scenario 8: Worker failure triggers complete rollback, increments attempts, and recovers expired lease       0.35s  
  ✓ Scenario 9: pending_review account is strictly protected from promotional credits                            0.32s  
  ✓ Scenario 10: Legacy ApplyWalletCashbackListener is isolated and not executed during new promotional flows    0.32s  

   PASS  Packages\Webkul\Wallet\tests\Unit\WalletGate4AdminUITest
  ✓ Scenario 1: Supports CRUD and persistence for all 4 promotion types                                          0.87s  
  ✓ Scenario 2: Validates FormRequest rules and rejects invalid promotion payloads                               0.32s  
  ✓ Scenario 3: Records Audit log on promotion creation, update, and archiving                                   0.34s  
  ✓ Scenario 4: Customer presentation cleanly separates cash from promo and prohibits promo withdrawal           0.30s  
  ✓ Scenario 5: Internal monitoring queries for Usages, Grants, Debts, and Outbox execute successfully           0.33s  

   PASS  Packages\Webkul\Wallet\tests\Unit\WalletServiceTest
  ✓ credit increases available balance and creates transaction                                                   0.44s  
  ✓ debit decreases available balance and creates transaction                                                    0.40s  
  ✓ debit throws InsufficientWalletBalanceException when balance is insufficient                                 0.34s  
  ✓ hold moves balance from available to held                                                                    0.33s  
  ✓ release moves held balance back to available                                                                 0.35s  
  ✓ suspended wallet throws WalletSuspendedException                                                             0.59s  
  ✓ WalletTransaction is immutable after creation                                                                0.53s  
  ✓ adjust creates ADJUSTMENT transaction with reference_transaction_id                                          0.43s  

  Tests:    36 passed (218 assertions)
  Duration: 16.29s
```
- **Exit Code:** `0`

---

## 5. Strict Safety Rails & Affirmations

| Rail | Status | Proof |
|---|---|---|
| **Live Event Registration** | **DORMANT** | 0 live event listeners registered in `EventServiceProvider`. |
| **Legacy Listener (`ApplyWalletCashbackListener`)** | **UNTOUCHED** | Old listener untouched and verified intact. |
| **Feature Flag Default** | **LEGACY_ONLY** | `sales.wallet_promotions.mode = 'legacy_only'` remains default. |
| **Worker Execution** | **MANUAL ONLY** | No daemon, queue worker, or cron running in background. |
| **Backfill** | **DORMANT** | 0 legacy customer records touched. |
| **Real Customer Data** | **ZERO USAGE** | Only synthetic test fixtures used. |
| **Gate 5 Scope** | **NOT STARTED** | Production rollout and live integration not initiated. |

---

## 6. Git Status & Commit Details

- **Git Status (`git status --porcelain=v1`):** Clean working tree.
- **Commit Hash:** `416b52a13a55be72ba05d44d7a39036279524ead`
- **Code Style:** Pint formatted (0 violations).

**Execution stopped.** Awaiting leadership review and decision. Gate 5 has not been started.
