# Dynamic Money Transfer Implementation Specification v1.0

**Project:** HIGEST (Bagisto 2.4.x / Laravel)  
**Document Type:** Enterprise Implementation Specification  
**Classification:** Internal — Engineering Reference  
**Status:** Approved Implementation Specification v1.0  
**Author Role:** Principal Software Architect & Lead Software Engineer  
**Reference Architecture:** Dynamic Money Transfer Architecture Specification v1.1  
**Date:** 2026-07-30  

---

## Table of Contents

1. [Executive Summary](#1-executive-summary)
2. [Implementation Goals](#2-implementation-goals)
3. [Package Structure](#3-package-structure)
4. [Component Breakdown](#4-component-breakdown)
5. [Dependency Graph](#5-dependency-graph)
6. [Execution Order](#6-execution-order)
7. [Database Implementation Strategy](#7-database-implementation-strategy)
8. [Runtime Registration Implementation](#8-runtime-registration-implementation)
9. [Admin Implementation Strategy](#9-admin-implementation-strategy)
10. [Checkout Integration Strategy](#10-checkout-integration-strategy)
11. [Order Integration Strategy](#11-order-integration-strategy)
12. [Cache Implementation Strategy](#12-cache-implementation-strategy)
13. [Error Handling Strategy](#13-error-handling-strategy)
14. [Security Strategy](#14-security-strategy)
15. [Testing Strategy](#15-testing-strategy)
16. [Acceptance Criteria](#16-acceptance-criteria)
17. [Deployment Strategy](#17-deployment-strategy)
18. [Risk Mitigation Plan](#18-risk-mitigation-plan)
19. [Implementation Checklist](#19-implementation-checklist)
20. [Out of Scope](#20-out-of-scope)
21. [Architecture Clarification Required](#21-architecture-clarification-required)

---

## 1. Executive Summary

This document serves as the **authoritative implementation blueprint** for building the Dynamic Money Transfer feature in HIGEST (Bagisto 2.4.x / Laravel). It translates the approved **Dynamic Money Transfer Architecture Specification v1.1** into precise, actionable technical specifications.

The implementation creates a self-contained custom Bagisto package (`packages/Webkul/DynamicBankTransfer/`) that enables store administrators to create and manage an **unlimited number of independent bank transfer payment methods**. Each dynamic method acts as a peer payment option during Checkout, registers deterministically at runtime via `DynamicPaymentRegistry`, enforces strict layered decoupling via `DynamicBankTransferService`, and embeds a **Complete Immutable Snapshot (v1.1)** inside `order_payment.additional`.

Zero files within Bagisto core (`packages/Webkul/{Admin,Shop,Payment,Sales,Core,Checkout}`) will be modified.

---

## 2. Implementation Goals

1. **Clean Package Encapsulation:** Build 100% of new components inside `packages/Webkul/DynamicBankTransfer/`.
2. **Deterministic Runtime Registration:** Implement `DynamicPaymentRegistry` with Tier-1 Boot Injection and Tier-2 Lazy Guard Protection.
3. **Strict Layer Decoupling:** Enforce `Payment Class -> Service -> Repository -> Model -> Database` execution flow, utilizing DTOs (`DynamicBankTransferDTO`) to keep the Payment class independent of DB/Cache logic.
4. **Data Integrity & Immutability:** Inject a complete JSON snapshot (v1.1) into `order_payment.additional` at order creation time, ensuring historical orders are immune to subsequent admin changes or soft deletes.
5. **Seamless Admin & Storefront Integration:** Provide a dedicated Bagisto DataGrid CRUD in Admin and leverage Bagisto's native `Payment::getPaymentMethods()` iteration for storefront Checkout.
6. **Robust Error Resilience & Caching:** Implement request-level and multi-tier application caching with automatic event-driven invalidation.

---

## 3. Package Structure

The feature will be housed in a new package following Bagisto's standard package conventions:

```
packages/
└── Webkul/
    └── DynamicBankTransfer/
        ├── composer.json
        └── src/
            ├── Config/
            │   ├── acl.php
            │   ├── admin-menu.php
            │   └── payment-methods.php
            ├── Contracts/
            │   └── DynamicBankTransfer.php
            ├── Database/
            │   ├── Factories/
            │   │   └── DynamicBankTransferFactory.php
            │   ├── Migrations/
            │   │   └── 2026_07_30_000001_create_dynamic_bank_transfers_table.php
            │   └── Seeders/
            │       └── DynamicBankTransferDatabaseSeeder.php
            ├── DataGrids/
            │   └── DynamicBankTransferDataGrid.php
            ├── DTOs/
            │   └── DynamicBankTransferDTO.php
            ├── Http/
            │   ├── Controllers/
            │   │   └── Admin/
            │   │       └── DynamicBankTransferController.php
            │   ├── Requests/
            │   │   ├── DynamicBankTransferCreateRequest.php
            │   │   └── DynamicBankTransferUpdateRequest.php
            │   └── Rules/
            │       └── IbanValidationRule.php
            ├── Listeners/
            │   ├── AutoInvoiceListener.php
            │   └── OrderPaymentSnapshotListener.php
            ├── Models/
            │   ├── DynamicBankTransfer.php
            │   └── DynamicBankTransferProxy.php
            ├── Payment/
            │   └── DynamicBankTransferMethod.php
            ├── Providers/
            │   ├── DynamicBankTransferServiceProvider.php
            │   ├── EventServiceProvider.php
            │   └── ModuleServiceProvider.php
            ├── Repositories/
            │   └── DynamicBankTransferRepository.php
            ├── Resources/
            │   ├── assets/
            │   │   └── images/
            │   │       └── default-bank-logo.png
            │   ├── lang/
            │   │   ├── ar/
            │   │   │   └── app.php
            │   │   └── en/
            │   │       └── app.php
            │   └── views/
            │       ├── admin/
            │       │   ├── create.blade.php
            │       │   ├── edit.blade.php
            │       │   └── index.blade.php
            │       └── shop/
            │           └── orders/
            │               └── payment-details.blade.php
            ├── Routes/
            │   └── admin-routes.php
            └── Services/
                ├── DynamicBankTransferService.php
                └── DynamicPaymentRegistry.php
```

---

## 4. Component Breakdown

| Component | Class / File | Primary Responsibility |
|:---|:---|:---|
| **Service Provider** | `DynamicBankTransferServiceProvider` | Bootstraps config merging, registers routes, views, translations, migrations, and triggers Tier-1 registration via `DynamicPaymentRegistry`. |
| **Module Provider** | `ModuleServiceProvider` | Registers Concord model proxies (`DynamicBankTransfer` model contract binding). |
| **Event Provider** | `EventServiceProvider` | Binds `checkout.order.save.before` to `OrderPaymentSnapshotListener` and `checkout.order.save.after` to `AutoInvoiceListener`. |
| **Registry Engine** | `DynamicPaymentRegistry` | Manages runtime registration into `config('payment_methods')` and `config('core')`. Provides `ensureRegistered()` Tier-2 Lazy Guard. |
| **Domain Service** | `DynamicBankTransferService` | Handles domain logic, DTO mapping, snapshot building, and application cache management (`dynamic_bank_transfers.active`). |
| **Data Transfer Object** | `DynamicBankTransferDTO` | Read-only object holding strongly-typed parameters of a dynamic bank transfer method. |
| **Payment Class** | `DynamicBankTransferMethod` | Extends `Webkul\Payment\Payment\Payment`. Overrides `getCode()`, `getTitle()`, `getDescription()`, `getImage()`, `getAdditionalDetails()`, `isAvailable()`. Delegates all data reads to `DynamicBankTransferService`. |
| **Eloquent Model** | `DynamicBankTransfer` | Maps to `dynamic_bank_transfers` table. Implements Contract, uses `HasFactory` and `SoftDeletes`. |
| **Repository** | `DynamicBankTransferRepository` | Extends `Webkul\Core\Eloquent\Repository`. Handles database queries and Concord model contracts. |
| **Admin Controller** | `DynamicBankTransferController` | Admin CRUD operations (index, create, store, edit, update, destroy, massUpdate, massDestroy). Invokes `DynamicBankTransferService`. |
| **DataGrid** | `DynamicBankTransferDataGrid` | Renders Bagisto DataGrid table for bank accounts with sorting, status toggles, and mass actions. |
| **Form Requests** | `DynamicBankTransferCreateRequest` / `UpdateRequest` | Server-side validation rules for creating/updating bank records (title, bank_name, iban, logo, statuses). |
| **IBAN Rule** | `IbanValidationRule` | Validates IBAN string checksum and formatting. |
| **Snapshot Listener**| `OrderPaymentSnapshotListener` | Intercepts `checkout.order.save.before` to inject the v1.1 complete immutable JSON snapshot into `$data['payment']['additional']`. |
| **Invoice Listener** | `AutoInvoiceListener` | Intercepts `checkout.order.save.after`, detects `dynamic_bank_transfer_` prefix, checks `generate_invoice` rule from Service, and calls `InvoiceRepository::create()`. |

---

## 5. Dependency Graph

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                          HTTP Request / API / CLI                           │
└──────────────────────────────────────┬──────────────────────────────────────┘
                                       │
                                       ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                  DynamicBankTransferServiceProvider (Boot)                  │
└──────────────────────────────────────┬──────────────────────────────────────┘
                                       │ Calls Tier-1 Registration
                                       ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                           DynamicPaymentRegistry                            │
│  - Formats entries into config('payment_methods') and config('core')        │
│  - Binds IoC resolution for DynamicBankTransferMethod                        │
└──────────────────────────────────────┬──────────────────────────────────────┘
                                       │ Fetches DTOs
                                       ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                          DynamicBankTransferService                         │
│  - Manages Application Cache ('dynamic_bank_transfers.active')              │
│  - Constructs DynamicBankTransferDTO instances                              │
│  - Constructs Order Payment Snapshot JSON                                   │
└──────────────────────────────────────┬──────────────────────────────────────┘
                                       │ Queries / Modifies Data
                                       ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                         DynamicBankTransferRepository                       │
└──────────────────────────────────────┬──────────────────────────────────────┘
                                       │ Interacts with Eloquent
                                       ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                     DynamicBankTransfer Eloquent Model                      │
└──────────────────────────────────────┬──────────────────────────────────────┘
                                       │ Persists to DB
                                       ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                   MySQL Table: dynamic_bank_transfers                       │
└─────────────────────────────────────────────────────────────────────────────┘

                               [ SEPARATE FLOW ]
┌─────────────────────────────────────────────────────────────────────────────┐
│         Bagisto Payment Manager (Webkul\Payment\Payment)                    │
│  - Iterates config('payment_methods')                                       │
│  - Resolves DynamicBankTransferMethod via IoC Container                     │
└──────────────────────────────────────┬──────────────────────────────────────┘
                                       │ Delegates data calls
                                       ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                         DynamicBankTransferMethod                           │
│  - NO Direct DB / Cache dependency                                          │
│  - Calls DynamicBankTransferService::getByCode($code)                       │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 6. Execution Order

Implementation will proceed in **7 strictly ordered phases**. Each phase builds upon the completed prerequisites of the prior phase:

```
[Phase 1: Foundation & Scaffold]
             │
             ▼
[Phase 2: Database & Domain Layer]
             │
             ▼
[Phase 3: Service & Registry Engine]
             │
             ▼
[Phase 4: Payment Class & Framework Binding]
             │
             ▼
[Phase 5: Admin Management UI (CRUD & DataGrid)]
             │
             ▼
[Phase 6: Order & Checkout Snapshot Listeners]
             │
             ▼
[Phase 7: Testing, Verification & CI Integration]
```

### Phase Rationale:
1. **Phase 1 (Scaffold):** Establishes the package directory, Service Providers, and Concord bindings so the package is recognized by Laravel and Bagisto.
2. **Phase 2 (Database & Domain):** Creates the DB schema, Model, Repository, and DTOs before business logic is written.
3. **Phase 3 (Service & Registry):** Implements the cache management, DTO conversions, and `DynamicPaymentRegistry` engine.
4. **Phase 4 (Payment Class):** Connects the dynamic registration engine with `DynamicBankTransferMethod` and Bagisto's `Payment` Facade.
5. **Phase 5 (Admin UI):** Builds the DataGrid, Form Requests, Controllers, and Views so administrators can manage bank records.
6. **Phase 6 (Order/Checkout):** Implements event listeners for snapshot generation and invoice auto-generation during order placement.
7. **Phase 7 (Testing & Verification):** Runs automated tests (Pest, Pint, Translation check) to ensure regression-free deployment.

---

## 7. Database Implementation Strategy

### 7.1 Migration Strategy

A single migration file: `2026_07_30_000001_create_dynamic_bank_transfers_table.php` will define the schema.

**Key Constraints & Indexes:**
- Primary Key: `id` (`UNSIGNED BIGINT` / `INCREMENTS`)
- Unique Key: `code` (`VARCHAR(50)`) — Format: `dynamic_bank_transfer_{id}`
- Composite Index: `idx_dbt_active_sort` on `(is_active, sort_order)` for fast filtering.
- Index: `deleted_at` for soft-delete queries.

### 7.2 Code Generation Hook

Since `code` depends on `id` (`dynamic_bank_transfer_{id}`), the model will populate `code` in an Eloquent `created` event hook if `code` is empty, ensuring every inserted record receives a deterministic code matching its primary key.

### 7.3 Default Data Seeding

The database seeder `DynamicBankTransferDatabaseSeeder` will check if existing `moneytransfer` configuration exists in `core_config` (`sales.payment_methods.moneytransfer.mailing_address`). If present, it will seed a default `DynamicBankTransfer` record using those details to ensure smooth migration for existing store setups.

---

## 8. Runtime Registration Implementation

### 8.1 `DynamicPaymentRegistry` Execution Strategy

`DynamicPaymentRegistry` encapsulates all dynamic configuration injection:

1. **`registerAll()` method:**
   - Invokes `DynamicBankTransferService::getActiveMethods()` (which reads from cache).
   - Iterates each DTO:
     - Appends an array to `config('payment_methods.' . $dto->code)` specifying `class = DynamicBankTransferMethod::class`, `code = $dto->code`, `title = $dto->title`, `active = $dto->isActive`, `sort = $dto->sortOrder`.
     - Appends a stub array to `config('core')` under `sales.payment_methods.{$dto->code}`.
     - Binds contextual parameters in Laravel's IoC container so when `app(DynamicBankTransferMethod::class)` is resolved, it receives the corresponding `$dto->code`.
   - Sets internal state `$isRegistered = true`.

2. **`ensureRegistered()` Tier-2 Lazy Guard:**
   - Checks if `$isRegistered` is `false`.
   - If `false`, executes `registerAll()`.
   - Called at the beginning of `DynamicBankTransferService::getByCode()` and as an event listener fallback on `payment.manager.init` (if present) to guarantee dynamic methods are loaded even if `boot()` sequence varied.

---

## 9. Admin Implementation Strategy

### 9.1 DataGrid (`DynamicBankTransferDataGrid`)
- Extends `Webkul\DataGrid\DataGrid`.
- Columns: `id`, `title`, `bank_name`, `iban`, `is_active` (status toggle), `sort_order`.
- Actions: `Edit` (route `admin.sales.dynamic_bank_transfers.edit`), `Delete` (route `admin.sales.dynamic_bank_transfers.delete`).
- Mass Actions: `Mass Update Status`, `Mass Delete`.

### 9.2 Controller & CRUD Actions
- `index()`: Renders DataGrid view.
- `create()`: Renders create Blade form.
- `store()`: Validates request via `DynamicBankTransferCreateRequest`, handles logo upload, delegates creation to `DynamicBankTransferService::createMethod()`, redirects with success flash message.
- `edit($id)`: Renders edit Blade form populated with record data.
- `update($id)`: Validates via `DynamicBankTransferUpdateRequest`, updates logo if uploaded, delegates to `DynamicBankTransferService::updateMethod()`, redirects with success message.
- `destroy($id)`: Delegates to `DynamicBankTransferService::deleteMethod()`. Throws exception if linked orders exist.

### 9.3 ACL & Menu Integration
- `Config/admin-menu.php`: Merges menu item under `sales` key titled `admin::app.sales.dynamic-bank-transfers.title`, route `admin.sales.dynamic_bank_transfers.index`, icon `icon-sales`.
- `Config/acl.php`: Merges ACL key `sales.dynamic_bank_transfers` with permissions for `index`, `create`, `edit`, `delete`.

### 9.4 File Upload & Image Handling
- Uploaded logos stored in `public/dynamic-bank-transfers/` disk via `Storage::disk('public')->putFile()`.
- Old logos deleted upon image update or record soft-delete.

---

## 10. Checkout Integration Strategy

### 10.1 Storefront Display Mechanism
- Checkout API endpoint `POST /api/v1/checkout/payment-methods` invokes `Payment::getPaymentMethods()`.
- Because `DynamicPaymentRegistry` registered dynamic methods into `config('payment_methods')`, Bagisto's Payment Manager returns them alongside `cashondelivery` and other gateways.
- The Vue 3 checkout component renders each method with its title, description, and logo URL.

### 10.2 Cart Method Selection
- Customer selects a dynamic bank transfer (e.g., `dynamic_bank_transfer_7`).
- API POST `shop.checkout.onepage.payment_methods.store` receives `payment.method = 'dynamic_bank_transfer_7'`.
- `Cart::savePaymentMethod()` writes `method = 'dynamic_bank_transfer_7'` to `cart_payment` table.

---

## 11. Order Integration Strategy

### 11.1 Complete Immutable Snapshot Injection (v1.1)

1. `OrderPaymentSnapshotListener` listens to `checkout.order.save.before`.
2. When `$data['payment']['method']` starts with `dynamic_bank_transfer_`:
   - Extracts method code.
   - Calls `DynamicBankTransferService::buildOrderSnapshot($code)`.
   - Overwrites `$data['payment']['additional']` with the full snapshot payload (v1.1 schema):

```json
{
  "snapshot_version": "1.1",
  "snapshot_created_at": "2026-07-30T21:45:00Z",
  "method_details": {
    "code": "dynamic_bank_transfer_7",
    "db_id": 7,
    "title": "Bank Transfer - Al Rajhi Bank",
    "description": "Direct bank transfer to our Al Rajhi corporate account.",
    "logo_url": "https://higest.com/storage/dynamic-bank-transfers/alrajhi-logo.png",
    "sort_order": 2,
    "generate_invoice": true,
    "invoice_status": "pending",
    "order_status": "pending_payment"
  },
  "bank_details": {
    "bank_name": "Al Rajhi Banking & Investment Corp",
    "account_holder_name": "HIGEST Trading Establishment",
    "account_number": "48200001000608010167519",
    "iban": "SA0380000000482000010006",
    "swift_code": "RJHISARIXXX",
    "transfer_instructions": "Please transfer exact amount and quote Order # in transfer notes."
  }
}
```

### 11.2 Auto-Invoice Generation
1. `AutoInvoiceListener` listens to `checkout.order.save.after`.
2. Checks if `$order->payment->method` starts with `dynamic_bank_transfer_`.
3. Queries `DynamicBankTransferService::getByCode($code)`.
4. If `$dto->generateInvoice` is true, calls `InvoiceRepository::create()` with `$dto->invoiceStatus` and `$dto->orderStatus`.

### 11.3 Order View Rendering
- Admin order view Blade template (`sales/orders/view.blade.php`) uses view render event `bagisto.admin.sales.order.payment-method.after`.
- The package injects a custom Blade partial that checks if `order_payment.additional` contains `snapshot_version`.
- If present, renders bank details, IBAN, account holder, and transfer instructions **strictly from the snapshot**, ensuring 100% historical immutability.

---

## 12. Cache Implementation Strategy

### 12.1 Request-Level Memory Cache
`DynamicBankTransferService` holds an internal private static array `$requestCache = []`.  
Repeated calls to `getByCode()` within the same HTTP request return immediately from memory without accessing Redis or DB.

### 12.2 Application-Level Cache
- Key: `dynamic_bank_transfers.active`
- Store: Redis / Laravel Default Cache
- TTL: Forever (`Cache::rememberForever()`)
- Value: Serialized Collection of `DynamicBankTransferDTO` objects.

### 12.3 Cache Invalidation Triggers
`DynamicBankTransferService` executes `Cache::forget('dynamic_bank_transfers.active')` on:
- `createMethod()`
- `updateMethod()`
- `deleteMethod()`
- Status toggle / Mass update actions.

---

## 13. Error Handling Strategy

| Scenario | Potential Failure | Error Handling Mechanism |
|:---|:---|:---|
| **Non-existent Bank Code** | Customer attempts checkout with code deleted by admin in parallel. | `DynamicBankTransferService::getByCode()` returns `null`. `DynamicBankTransferMethod::isAvailable()` returns `false`. Cart validation rejects payment method with user-friendly translated flash error. |
| **Deactivated Method** | Method disabled while customer is on checkout page. | `isAvailable()` evaluates `$dto->isActive`. Returns `false`. Checkout validation blocks order placement. |
| **Corrupted Application Cache** | Invalid data in Redis cache. | `DynamicBankTransferService` catches deserialization exceptions, flushes cache key, re-queries database, and re-populates cache seamlessly. |
| **Missing Logo File** | Uploaded logo image deleted from storage. | `DynamicBankTransferDTO::getLogoUrl()` checks `Storage::exists()`. Falls back to default asset `bagisto_asset('images/default-bank-logo.png', 'dynamic_bank_transfer')`. |
| **Incomplete Bank Details** | Mandatory fields missing in database record. | Server-side validation via `DynamicBankTransferCreateRequest` prevents saving incomplete records. Database column constraints enforce non-null titles and IBANs. |

---

## 14. Security Strategy

1. **Server-Side Validation:** `DynamicBankTransferCreateRequest` and `UpdateRequest` validate all fields. IBAN is validated via `IbanValidationRule` (regex + mod-97 checksum).
2. **Authorization & ACL:** Controller methods authorize against ACL key `sales.dynamic_bank_transfers` using `bouncer()->can()`.
3. **XSS Protection:** Input strings sanitized. All Blade views render text using escaped blade directives `{{ $value }}`.
4. **Upload Security:** Logo files validated for image MIME types (`bmp, jpeg, jpg, png, webp`), max 2048 KB. Saved with random UUID filenames.
5. **Data Protection:** No sensitive credit card or secret keys are stored in `dynamic_bank_transfers`. IBANs and account numbers are public transfer details.

---

## 15. Testing Strategy

All test implementations will adhere to Bagisto testing standards using **Pest PHP**:

### 15.1 Unit Tests
- `DynamicBankTransferDTOTest`: Verifies DTO instantiation and immutability.
- `IbanValidationRuleTest`: Tests valid and invalid IBAN strings across different formats.
- `DynamicPaymentRegistryTest`: Verifies config injection into `payment_methods` and IoC bindings.

### 15.2 Feature Tests
- `Admin/DynamicBankTransferCRUDTest`: Tests admin index, create, store, edit, update, soft-delete, and DataGrid responses.
- `Checkout/DynamicBankTransferCheckoutTest`: Tests fetching payment methods via API, saving payment method to cart, and placing order.
- `Order/OrderSnapshotTest`: Verifies full v1.1 JSON snapshot injection into `order_payment.additional` upon order creation.

### 15.3 Regression & Upgrade Tests
- `PintTest`: Enforces Laravel Pint code style compliance (`vendor/bin/pint --test`).
- `TranslationCheckTest`: Verifies translation keys exist across all 21 locale files (`php artisan bagisto:translations:check`).

---

## 16. Acceptance Criteria

| Phase | Milestone | Acceptance Criteria |
|:---|:---|:---|
| **Phase 1** | Package Scaffold | Package registered in `composer.json` path repository. Service Provider boots cleanly without errors. |
| **Phase 2** | DB & Repository | Migration creates `dynamic_bank_transfers` table. Repository and Model pass basic Pest unit tests. |
| **Phase 3** | Service & Registry | Active bank records dynamically registered into `config('payment_methods')` at runtime. Cache invalidation verified. |
| **Phase 4** | Payment Class | `Payment::getPaymentMethods()` returns active dynamic bank transfer methods as peer options. |
| **Phase 5** | Admin UI | Admin can list, create, edit, toggle status, reorder, and soft-delete bank methods via DataGrid. |
| **Phase 6** | Order & Snapshot | Order creation successfully embeds complete v1.1 JSON snapshot in `order_payment.additional`. Admin order view renders snapshot details. |
| **Phase 7** | Quality Assurance | `vendor/bin/pint --dirty` passes with 0 violations. `php artisan test` passes 100%. Translation check passes across 21 locales. |

---

## 17. Deployment Strategy

### 17.1 Execution Steps
1. Register `packages/Webkul/DynamicBankTransfer` in `composer.json` path repositories.
2. Execute `composer dump-autoload`.
3. Register `Webkul\DynamicBankTransfer\Providers\DynamicBankTransferServiceProvider::class` in `bootstrap/providers.php`.
4. Register Concord module in `config/concord.php`.
5. Run migrations: `php artisan migrate`.
6. Run seeders (if migrating existing moneytransfer config): `php artisan db:seed --class="Webkul\DynamicBankTransfer\Database\Seeders\DynamicBankTransferDatabaseSeeder"`.
7. Clear application caches: `php artisan config:clear && php artisan cache:clear`.

### 17.2 Rollback Strategy
1. Disable active dynamic bank transfer records in database or remove provider from `bootstrap/providers.php`.
2. Existing orders with dynamic bank transfer method codes remain intact and display historical payment details via their embedded JSON snapshot in `order_payment.additional`.
3. Run reverse migration if total feature removal is required: `php artisan migrate:rollback --path=packages/Webkul/DynamicBankTransfer/src/Database/Migrations`.

---

## 18. Risk Mitigation Plan

| Risk | Cause | Impact | Mitigation Strategy |
|:---|:---|:---|:---|
| **Duplicate Method Codes** | Parallel record creation by multiple admins | Unique constraint collision | Code generated from Primary Key (`dynamic_bank_transfer_{id}`) inside Eloquent `created` hook. |
| **Orphaned File Storage** | Logos left in storage after record deletion | Disk bloat | File deletion triggered in Repository/Service `deleteMethod()` hook. |
| **Missing Translations** | New fields missing in some of the 21 locales | UI fallback key displayed | Automated translation verification via `php artisan bagisto:translations:check` in CI pipeline. |
| **Order View Breakage** | Historical bank record soft-deleted | Admin order view error | Admin order view reads strictly from `order_payment.additional` snapshot payload, avoiding live DB queries. |

---

## 19. Implementation Checklist

- [ ] **Phase 1: Foundation & Package Setup**
  - [ ] Create package directory structure under `packages/Webkul/DynamicBankTransfer/`.
  - [ ] Create `composer.json` for package and register path repository in root `composer.json`.
  - [ ] Create `DynamicBankTransferServiceProvider.php` and register in `bootstrap/providers.php`.
  - [ ] Create `ModuleServiceProvider.php` and register Concord model proxy in `config/concord.php`.
  - [ ] Create `EventServiceProvider.php` for event bindings.

- [ ] **Phase 2: Database & Domain Models**
  - [ ] Create migration `2026_07_30_000001_create_dynamic_bank_transfers_table.php`.
  - [ ] Create Model `DynamicBankTransfer.php`, Proxy, and Contract.
  - [ ] Create Repository `DynamicBankTransferRepository.php`.
  - [ ] Create DTO `DynamicBankTransferDTO.php`.
  - [ ] Create Seeder `DynamicBankTransferDatabaseSeeder.php`.

- [ ] **Phase 3: Service Layer & Registry Engine**
  - [ ] Create `DynamicBankTransferService.php` with caching logic and snapshot builder.
  - [ ] Create `DynamicPaymentRegistry.php` with `registerAll()` and `ensureRegistered()` Tier-2 Lazy Guard.

- [ ] **Phase 4: Payment Class & Registration Binding**
  - [ ] Create `DynamicBankTransferMethod.php` extending `Webkul\Payment\Payment\Payment`.
  - [ ] Verify `Payment::getPaymentMethods()` returns dynamic methods as peer options.

- [ ] **Phase 5: Admin Panel & CRUD**
  - [ ] Create `Config/admin-menu.php` and `Config/acl.php`.
  - [ ] Create `DynamicBankTransferDataGrid.php`.
  - [ ] Create `DynamicBankTransferCreateRequest.php` and `UpdateRequest.php` with `IbanValidationRule.php`.
  - [ ] Create `DynamicBankTransferController.php`.
  - [ ] Create Blade views (`index.blade.php`, `create.blade.php`, `edit.blade.php`).
  - [ ] Create translation files (`lang/ar/app.php` and `lang/en/app.php` extended to 21 locales).

- [ ] **Phase 6: Order Snapshot & Auto-Invoice Integration**
  - [ ] Create `OrderPaymentSnapshotListener.php` to inject complete v1.1 snapshot into `order_payment.additional`.
  - [ ] Create `AutoInvoiceListener.php` for auto-generating invoices based on DTO rules.
  - [ ] Inject custom Blade partial in admin order detail view to render snapshot data.

- [ ] **Phase 7: Testing & Verification**
  - [ ] Run `vendor/bin/pint --dirty` to ensure 0 style violations.
  - [ ] Run `php artisan test` for unit and feature test pass.
  - [ ] Run `php artisan bagisto:translations:check` across all 21 locales.

---

## 20. Out of Scope

The following items are explicitly **out of scope** for this release version:
1. Dynamic payment gateways requiring real-time external HTTP API handshakes (e.g., Stripe Connect dynamic accounts).
2. Customer-side bank transfer receipt upload during checkout (may be added in a future extension).
3. Automated bank statement reconciliation via Open Banking APIs.

---

## 21. Architecture Clarification Required

**Status:** None.  
All architectural decisions in **Dynamic Money Transfer Architecture Specification v1.1** are unambiguous, final, and fully integrated into this implementation specification.

---

**End of Specification v1.0**  
*This document serves as the official implementation specification. Technical execution may begin upon formal approval.*
