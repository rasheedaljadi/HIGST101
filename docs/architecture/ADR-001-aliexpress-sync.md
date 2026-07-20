# Architecture Decision Record (ADR-001)

## Title
AliExpress Dropshipping Sync Optimization & Scalability Architecture

## Status
Approved & Implemented (2026-07-01)

## Context
During Phase 4 (Verification & Reproduction Audit), multiple structural vulnerabilities were identified in the synchronous AliExpress Dropshipping synchronization pipeline:
1. **Sync Timeouts**: Syncing 40 products took over 26 minutes sequentially, making it prone to PHP web request timeouts and cron session aborts.
2. **Database Deadlocks**: Concurrent manual and scheduled syncs updating the same product EAV and flat index tables caused lock contention and deadlocks in MariaDB/MySQL.
3. **Price Index Mismatch (Stale Index)**: Out-of-stock variations were correctly excluded from the price index by Bagisto core logic, but the parent flat price was updated with out-of-stock prices, resulting in discrepancies.
4. **Token Expiration Calculations**: Millisecond timestamps returned by AliExpress for the refresh token were treated as seconds, causing future date overflow and resulting in zeroized timestamps (`0000-00-00 00:00:00`) that blocked auto-refresh.

---

## Decisions

### 1. Introduction of Laravel Queues & Jobs
* **Decision**: Encapsulate product synchronization in a job queue pipeline (`SyncProductJob` & `SyncAllProductsJob`).
* **Rationale**:
  * Offloads long-running API and database operations to background workers.
  * Allows parallel processing, preventing sequential delay bottlenecks.
  * Supports automatic retries and backoff management for transient network or API failures.

### 2. Distributed Locking (Mutex) via Cache
* **Decision**: Wrapped the syncer loop in a Cache-backed lock (`Cache::lock`) using the AliExpress product ID.
* **Rationale**:
  * Prevents multiple processes (scheduled cron vs. admin manual clicks) from processing the same product concurrently.
  * Completely eliminates database lockups and race conditions on variant updates.

### 3. Pilot Feature: Deferred Indexing
* **Decision**: Created a Feature Flag `defer_indexing` that queues affected product and variant IDs in a cache set rather than running indexers during the database transaction. A scheduled job (`aliexpress:sync-products --process-deferred-index`) clears this cache and indexes in batch every 10 minutes.
* **Rationale**:
  * Bypasses the heavy write and indexing lock overhead of Bagisto's EAV and Flat engines during bulk sync.
  * Minimizes disk I/O bottlenecks.

### 4. Aligning Parent Flat Price Logic
* **Decision**: Modified the representative price calculation for parent configurable products to prioritize in-stock variations.
* **Rationale**:
  * Establishes consistency between the product detail page pricing (flat table) and the catalog filtering/sorting pricing (price index table).

---

## Consequences

### Pros
* **High Scalability**: Safe execution under larger catalogs; moving I/O-bound API processes to queues.
* **Deadlock Prevention**: Cache locks guarantee exclusive transactions per product ID.
* **Fault Tolerance**: Jobs fail gracefully and can be monitored or retried automatically.

### Cons & Trade-offs
* **Eventual Consistency**: Changes to product prices/inventory will take up to 10 minutes to reflect on catalog lists and search filters (due to deferred indexing), though correct details are loaded on product pages and enforced at checkout/cart validation.
* **Infrastructure Requirement**: Production environment requires running queue workers (e.g. via Supervisor) and a caching store (like Redis or database-backed caches).
