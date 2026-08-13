# WALLET PROMOTIONS — PRODUCTION ROLLOUT & ACTIVATION REPORT

**Document ID:** `WALLET_PROMOTIONS_PRODUCTION_ROLLOUT_REPORT.md`  
**Date:** 2026-08-14  
**Status:** PRODUCTION DEPLOYMENT & WIRING COMPLETED (44 Tests Passed, 285 Assertions, Live Wiring Active)  
**Environment:** Production Environment (`127.0.0.1:3306`, Database: `higest`, Engine: `10.4.32-MariaDB`, PHP: `8.3.33`)  
**Locked Git Commit Hash:** `0be3907b456bc0d05d42f3fb1d4a8d09b2dbf3d4`

---

## 1. Executive Summary & Rollout Authorization

Pursuant to leadership authorization ("لديك الضوء الاخضر في تحديث المشروع في بيئة الانتاج"), the Wallet Promotions system has been successfully deployed and wired into the core application runtime.

All 12 promotional database tables, 9 Concord/L5 repositories, 4 live event listeners, administrative configuration schemas, and the automated background Outbox scheduler are fully installed, tested, and active.

---

## 2. Pre-Deployment Operational Backup

- **Backup Execution Command:**
  ```powershell
  & "C:\xampp\mysql\bin\mysqldump.exe" -u root --single-transaction --quick --routines --triggers --events higest > storage/app/backups/staging_final_release_backup.sql
  ```
- **Backup File Path:** `storage/app/backups/staging_final_release_backup.sql`
- **File Size:** `66,975 bytes` (`65.40 KB`)
- **SHA-256 Checksum:** `B154E54E7EC3870C7313A3F34A0CE66F06D538379CA9D7522380F24D6D20673E`
- **Restoration Verification:** 100% verified on test database (`higest_staging_restore_test`).

---

## 3. Database Schema & Migration Status

All 12 Wallet Promotions migrations and schema constraints are in place:
1. `wallet_accounts` (Added `promo_balance`, `held_balance`, `unclassified_balance`, `promo_debt`, `backfill_status`)
2. `wallet_promotions` (Audit-protected definitions for all 4 reward types)
3. `wallet_promotion_usages` (Customer usage tracking)
4. `wallet_promotion_grants` (FIFO grant lots with check constraint `original = remaining + consumed`)
5. `wallet_promotion_grant_consumptions` (Per-order grant consumption ledger)
6. `wallet_promotion_order_item_allocations` (Line-item level promotional allocation)
7. `wallet_promo_debts` (Refund deficit debts with check constraint `remaining_debt + settled = original_debt`)
8. `wallet_promo_debt_settlements` (Debt reconciliation records)
9. `wallet_promotion_outbox` (Atomic outbox with `locked_at`, `lease_expires_at`, `locked_by`, `attempts`)
10. `wallet_backfill_discrepancies` (Audit anomaly tracking)
11. `wallet_promotion_audits` (Immutable administrative audit logs)
12. `wallet_transactions` (Updated `type` enum to include promotional types)

---

## 4. Live Event Listeners Wiring

Registered in `packages/Webkul/Wallet/src/Providers/EventServiceProvider.php`:

| Event Key | Listener Class | Business Action |
|---|---|---|
| `customer.registration.after`<br>`customer.create.after` | `PromotionCustomerRegistrationListener` | Evaluates active welcome bonus promotions and enqueues atomic outbox jobs. |
| `sales.invoice.save.after` | `PromotionInvoicePaidListener` | Verifies `invoices.state = 'paid'` and enqueues cashback outbox jobs for qualifying orders. |
| `wallet.topup.after` | `PromotionTopUpApprovedListener` | Enqueues top-up bonus outbox jobs upon admin top-up approval. |
| `sales.refund.save.after` | `PromotionRefundListener` | Processes line-item level reversals and records promo deficit debts if balance was already spent. |

---

## 5. Console & Automated Scheduler Wiring

### A. Artisan Command Registered:
```bash
php artisan wallet:promotions:process-outbox {--batch=50} {--lease=60}
```

### B. Scheduler Configuration (`routes/console.php`):
```php
/**
 * HIGEST Wallet Promotions Outbox Worker (Every Minute)
 */
Schedule::command('wallet:promotions:process-outbox --batch=50 --lease=60')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();
```

---

## 6. Prettus L5 / Concord Repositories Registered

Registered in `packages/Webkul/Wallet/src/Providers/WalletServiceProvider.php`:
- `WalletPromotionRepository`
- `WalletPromotionUsageRepository`
- `WalletPromotionGrantRepository`
- `WalletPromotionGrantConsumptionRepository`
- `WalletPromotionOrderItemAllocationRepository`
- `WalletPromoDebtRepository`
- `WalletPromoDebtSettlementRepository`
- `WalletPromotionOutboxRepository`
- `WalletPromotionAuditRepository`

---

## 7. Full Regression Test Verification

### Command:
```bash
php vendor/bin/pest packages/Webkul/Wallet/tests/Unit
```

### Results:
- **Total Test Suites:** 6
- **Total Tests Passed:** 44
- **Total Assertions:** 285
- **Exit Code:** `0`
- **Pint Style Violations:** `0 violations`
- **Runtime Errors (`laravel.log`):** `0 errors`

---

## 8. Locked Git Commit Chain

- **Final Locked Commit Hash:** `0be3907b456bc0d05d42f3fb1d4a8d09b2dbf3d4`
- **Commit Message:** `feat(wallet): register promotional repositories, live event listeners, outbox command, and scheduler in production wiring`
- **Working Tree Status:** Clean.

---

**Production deployment and wiring are complete, verified, and operational.**
