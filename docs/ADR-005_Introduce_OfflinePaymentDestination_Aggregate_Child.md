# ADR-005: Introduce OfflinePaymentDestination Aggregate Child

**Status:** APPROVED & IMPLEMENTED  
**Date:** 2026-07-31  
**Deciders:** Lead Architect, Engineering Team  
**Scope:** `packages/Webkul/OfflinePayments`

---

## 1. Context & Problem Statement

In Offline Payments V1 and early V2 designs, the data model assumed a flat 1:1 coupling between a merchant account and a currency (`OfflinePaymentAccount -> 1 Currency`).

Under real-world multi-currency commerce and regional payment operations (e.g. Yalla Pay Mobile Wallet, Al Rajhi Bank, Kuraimi Bank), a single merchant payment account (one legal recipient, one provider, one logo) supports multiple currencies:
- **Scenario A (Unified Identifier):** Yalla Pay wallet phone number `777777777` accepts USD, SAR, and YER under the same phone number.
- **Scenario B (Currency-Specific Identifiers):** Al Rajhi Bank account provides a USD IBAN (`SA111...`) and a SAR IBAN (`SA222...`).

The flat 1:1 model forced store managers to create duplicate account records (`Yalla Pay USD`, `Yalla Pay SAR`, `Yalla Pay YER`) for every supported currency, duplicating recipient names, logos, display names, and channels. This led to data duplication, update anomalies (inconsistent recipient names across currencies), and cluttered Admin DataGrids.

---

## 2. Options Considered

### Option A: Retain Flat 1:1 Model (`OfflinePaymentAccount -> 1 Currency`)
- **Pros:** Minimal schema complexity.
- **Cons:** High data duplication, update anomalies, poor Admin UX.

### Option B: Array of Currency IDs in Account Table (`currency_ids` JSON column)
- **Pros:** Reduces parent row duplication.
- **Cons:** Fails for Scenario B (where each currency requires a different IBAN or SWIFT code), violates 1NF normalization, prevents relational foreign key enforcement.

### Option C: Decouple Account Identity & Destination Endpoints (`OfflinePaymentAccount -> HasMany OfflinePaymentDestination`)
- **Pros:** 
  - Perfect Ubiquitous Language alignment (DDD).
  - Eliminates data duplication completely (3NF).
  - Accommodates both unified identifiers (wallets) and currency-specific IBANs (banks).
  - Extensible: future commercial policies (minimums, fees, settlement rules) can attach to destinations without altering parent root.
- **Cons:** Requires a child table (`offline_payment_destinations`) and relational joins.

---

## 3. Decision Outcome

We selected **Option C**.

We explicitly redefined the aggregate boundaries:
1. `OfflinePaymentAccount` is the **Aggregate Root**, holding provider identity, display name, recipient name, logo, and active channels.
2. `OfflinePaymentDestination` is the **Child Entity**, holding currency reference (`currency_id`), IBAN/identifier (`account_identifier`), SWIFT code (`swift_code`), and transfer instructions (`transfer_instructions`).

### Structural Guarantees:
- **Unique Constraint:** `(offline_payment_account_id, currency_id)` is enforced as unique at DB level.
- **Cascading Deletion:** `ON DELETE CASCADE` removes destinations when parent account is deleted.
- **Immutable Snapshot (Schema Version 2):** Order payment snapshots store versioned payloads containing `"snapshot_type": "offline_payment"` and `"schema_version": 2`.
- **Snapshot Reader Strategy:** All snapshot consumption is handled exclusively via `OfflinePaymentSnapshotReader`.

---

## 4. Architectural Principles Established

- **Principle 6: Stable Aggregate Root, Extensible Children:** The `OfflinePaymentAccount` aggregate root remains minimal, highly stable, and focused solely on merchant/provider identity. All structural extensions (currency routes, fees, thresholds, policies) are introduced as decoupled child entities (`Destination`, `Policy`) attached to the root without inflating or mutating the parent schema.

---

## 5. Consequences & Status

- **Database:** Tables `offline_payment_accounts` and `offline_payment_destinations` created and seeded.
- **Services:** `OfflinePaymentAccountResolver` queries eligible destinations matching cart currency and channel.
- **Verification:** Integration tests and Pint linter passed with 100% compliance.
