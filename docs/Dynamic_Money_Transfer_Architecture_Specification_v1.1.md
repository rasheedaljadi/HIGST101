# Dynamic Money Transfer Architecture Specification v1.1

**Project:** HIGEST (Bagisto 2.4.x / Laravel)  
**Document Type:** Enterprise Architecture Specification  
**Classification:** Internal — Engineering Reference  
**Status:** Approved Architecture Base — Final Specification v1.1  
**Author Role:** Principal Software Architect  
**Date:** 2026-07-30  

---

## Table of Contents

1. [Executive Summary](#1-executive-summary)
2. [Business Requirements](#2-business-requirements)
3. [Functional Requirements](#3-functional-requirements)
4. [Non-Functional Requirements](#4-non-functional-requirements)
5. [Current Bagisto Architecture](#5-current-bagisto-architecture)
6. [Target Architecture & Layered Service Pattern](#6-target-architecture--layered-service-pattern)
7. [Domain Model & Service Abstraction](#7-domain-model--service-abstraction)
8. [Database Strategy](#8-database-strategy)
9. [Dynamic Payment Registration Strategy](#9-dynamic-payment-registration-strategy)
10. [Runtime Registration Strategy & Execution Lifecycle](#10-runtime-registration-strategy--execution-lifecycle)
11. [Admin Architecture](#11-admin-architecture)
12. [Checkout Architecture](#12-checkout-architecture)
13. [Order Architecture & Complete Snapshot Specification](#13-order-architecture--complete-snapshot-specification)
14. [Future Extensibility](#14-future-extensibility)
15. [Upgrade Compatibility](#15-upgrade-compatibility)
16. [Security Considerations](#16-security-considerations)
17. [Performance Considerations](#17-performance-considerations)
18. [Caching Strategy](#18-caching-strategy)
19. [Risk Analysis](#19-risk-analysis)
20. [Alternatives Considered](#20-alternatives-considered)
21. [Final Recommendation](#21-final-recommendation)
22. [Implementation Prerequisites](#22-implementation-prerequisites)

---

## 1. Executive Summary

HIGEST requires store administrators to create, configure, and manage an **unlimited number of independent bank transfer payment methods**, each representing a distinct bank account with its own title, IBAN, SWIFT code, logo, activation status, and operational settings.

The current Bagisto 2.4.x platform provides exactly one hard-coded Money Transfer payment method (`moneytransfer`), storing bank details as a single unstructured text field (`mailing_address`) in the `core_config` table.

This document presents the **v1.1 Enterprise Architecture Specification** for dynamic money transfers. In this version, the architecture is refined to enforce strict separation of concerns, deterministic lifecycle registration, complete snapshot immutability for orders, and general future extensibility:

1. **Runtime Registration Strategy:** Uses a dedicated `DynamicPaymentRegistry` service to guarantee deterministic registration before Bagisto's Payment Manager evaluates active methods.
2. **Strict Layered Decoupling:** The `DynamicBankTransferMethod` Payment class does not interact with Eloquent, Repositories, or Cache directly. It delegates all data fetching to `DynamicBankTransferService`.
3. **Complete Order Snapshot:** Persists an exhaustive immutable JSON payload (`method_code`, `title`, `description`, `bank_name`, `iban`, `swift`, `logo`, `invoice_status`, `order_status`, `version`, etc.) into `order_payment.additional`.
4. **Future Extensibility Engine:** The underlying registry and service architecture is decoupled from bank transfers, allowing HIGEST to dynamically register future custom payment providers (e.g., dynamic gateways, wallets) without modifying core infrastructure.

No Bagisto core files are modified. All functionality resides within a self-contained custom package (`packages/Webkul/DynamicBankTransfer/`).

---

## 2. Business Requirements

| ID | Requirement |
|:---|:---|
| **BR-01** | The platform shall support an unlimited number of bank transfer payment methods. |
| **BR-02** | Each bank transfer shall be independently configurable through the Admin Panel without developer involvement or code deployments. |
| **BR-03** | Each bank transfer shall appear as a separate, top-level payment option during Checkout — not as a nested sub-option. |
| **BR-04** | The store administrator shall be able to activate, deactivate, reorder, create, edit, and soft-delete bank transfer methods at any time. |
| **BR-05** | Deleting or modifying a bank transfer method shall not alter historical order records. Orders must retain a complete, immutable snapshot of the method state at order placement. |
| **BR-06** | The system shall support Multi-Channel and Multi-Locale configurations for each bank transfer method. |
| **BR-07** | The solution shall not impede future Bagisto version upgrades and must strictly preserve Clean Architecture principles. |

---

## 3. Functional Requirements

| ID | Requirement | Detail |
|:---|:---|:---|
| **FR-01** | Each bank transfer method shall store: Status, Title, Description, Logo, Bank Name, Account Holder Name, Account Number, IBAN, SWIFT Code (optional), Transfer Instructions, Generate Invoice flag, Invoice Status, Order Status, and Sort Order. | Configurable per method instance. |
| **FR-02** | Title, Description, and Transfer Instructions shall support per-locale translation. | Compatible with Bagisto's localization model. |
| **FR-03** | Status and Sort Order shall support per-channel configuration. | Channel-based availability support. |
| **FR-04** | Each bank transfer method shall be assigned a unique, system-generated, immutable code (e.g., `dynamic_bank_transfer_7`). | Used across `cart_payment`, `order_payment`, event listeners, and REST/GraphQL APIs. |
| **FR-05** | During Checkout, active bank transfer methods shall appear as peer payment options alongside native methods (Cash On Delivery, PayPal, etc.). | Rendered seamlessly by Bagisto storefront. |
| **FR-06** | Order creation shall record a **Complete Snapshot** of all payment method metadata and bank parameters in `order_payment.additional`. | Includes versioning metadata (`snapshot_version: "1.1"`). |
| **FR-07** | Automatic invoice generation shall be supported per method, reading rules from `DynamicBankTransferService`. | Handled via event listeners checking prefix matches. |
| **FR-08** | Admin order views shall display payment details directly from the historical snapshot rather than live config. | Eliminates drift between live settings and past orders. |
| **FR-09** | Admin shall provide a dedicated DataGrid for managing bank transfer methods with inline toggle, drag/order, and CRUD actions. | Standard Bagisto UI component. |
| **FR-10** | Server-side validation shall verify IBAN formats upon entry. | Custom validation rule. |

---

## 4. Non-Functional Requirements

| ID | Category | Requirement |
|:---|:---|:---|
| **NFR-01** | Clean Architecture | Zero modification to `packages/Webkul/` core. Strict separation between Payment Class, Service Layer, Repository, and DB. |
| **NFR-02** | Extensibility | The registry engine must support registering arbitrary dynamic payment providers beyond bank transfers in future phases. |
| **NFR-03** | Performance | Dynamic registration must execute deterministically with O(1) query overhead via multi-tier caching (In-Memory + Redis). |
| **NFR-04** | Upgrade Safety | Interacts exclusively through Laravel Service Providers, Facades, Event Listeners, and Bagisto Config Contracts. |
| **NFR-05** | Data Integrity | Hard deletion of bank transfer records is prohibited if linked to existing orders. Soft-delete is enforced. |
| **NFR-06** | Localization | All admin and storefront strings must support Bagisto's 21 locales. |
| **NFR-07** | Security | All text fields sanitized against XSS. Logo uploads restricted to validated image MIME types. IBAN validated. |

---

## 5. Current Bagisto Architecture

### 5.1 Static Registration Pipeline

In Bagisto 2.4.x, payment methods are statically registered during application initialization:

1. **Config Merging:** Packages call `$this->mergeConfigFrom(..., 'payment_methods')` inside `register()`. This populates `config('payment_methods')`.
2. **Payment Manager (`Webkul\Payment\Payment`):** When `Payment::getPaymentMethods()` is invoked, it iterates `config('payment_methods')`, resolves each class string via `app()`, checks `isAvailable()`, and sorts the result.
3. **Configuration Storage:** Admin configuration options are statically mapped in `packages/Webkul/Admin/src/Config/system.php` and stored in the `core_config` table.
4. **Order Processing:** `Cart::savePaymentMethod()` writes the selected method code to `cart_payment`. On order creation, `OrderResource` maps this to `order_payment`, storing a minimal `additional` payload.

### 5.2 Key Limitations of Current Architecture

- **Static Configuration:** Requires hard-coded PHP config entries for every payment method.
- **Unstructured Text:** `moneytransfer` uses a single free-form textarea (`mailing_address`) for bank data.
- **No Multi-Account Support:** Bagisto assumes 1 method code = 1 payment gateway class = 1 configuration tree.

---

## 6. Target Architecture & Layered Service Pattern

### 6.1 Architectural Principle: Separation of Concerns

To resolve coupling issues identified in v1.0, v1.1 enforces a strict 4-tier layered architecture for dynamic payment methods:

```
┌───────────────────────────────────────────────────────────────────────────┐
│                          1. PAYMENT CLASS LAYER                           │
│  DynamicBankTransferMethod (Extends Webkul\Payment\Payment\Payment)       │
│  - Implements Bagisto Payment Contract                                   │
│  - NO Eloquent / NO Repositories / NO Cache logic                         │
└─────────────────────────────────────┬─────────────────────────────────────┘
                                      │ Invokes methods
                                      ▼
┌───────────────────────────────────────────────────────────────────────────┐
│                           2. SERVICE LAYER                                │
│  DynamicBankTransferService                                               │
│  - Encapsulates Domain Logic                                             │
│  - Manages DTO Conversions & Business Rules                              │
│  - Handles Cache Strategy (Get / Invalidate)                             │
└─────────────────────────────────────┬─────────────────────────────────────┘
                                      │ Queries data
                                      ▼
┌───────────────────────────────────────────────────────────────────────────┐
│                         3. REPOSITORY LAYER                               │
│  DynamicBankTransferRepository (Extends Webkul\Core\Eloquent\Repository)  │
│  - Handles Database Access & Eloquent Queries                            │
└─────────────────────────────────────┬─────────────────────────────────────┘
                                      │ Persists / Reads
                                      ▼
┌───────────────────────────────────────────────────────────────────────────┐
│                          4. DATABASE LAYER                                │
│  MySQL Table: dynamic_bank_transfers                                      │
└───────────────────────────────────────────────────────────────────────────┘
```

### 6.2 Data Flow Responsibility Matrix

| Layer | Component | Allowed Dependencies | Prohibited Dependencies |
|:---|:---|:---|:---|
| **Payment Class** | `DynamicBankTransferMethod` | `DynamicBankTransferService`, `Cart` | Eloquent Models, Repositories, `DB`, `Cache` |
| **Service Layer** | `DynamicBankTransferService` | `DynamicBankTransferRepository`, Cache Facade | HTTP Requests, Vue/Blade Views |
| **Repository Layer**| `DynamicBankTransferRepository`| Eloquent Model, Container | Business Logic, HTTP Layer |
| **Registry Layer** | `DynamicPaymentRegistry` | `DynamicBankTransferService`, Config Facade | Direct DB Queries |

---

## 7. Domain Model & Service Abstraction

### 7.1 Entity: DynamicBankTransfer DTO

To prevent Eloquent Models from leaking into the Payment Class layer, `DynamicBankTransferService` returns immutable **Data Transfer Objects (DTOs)**:

```
DynamicBankTransferDTO {
    public int $id;
    public string $code;
    public bool $isActive;
    public string $title;
    public string $description;
    public string $bankName;
    public string $accountHolderName;
    public ?string $accountNumber;
    public string $iban;
    public ?string $swiftCode;
    public ?string $transferInstructions;
    public ?string $logoPath;
    public ?string $logoUrl;
    public bool $generateInvoice;
    public string $invoiceStatus;
    public string $orderStatus;
    public int $sortOrder;
    public ?array $channelIds;
}
```

### 7.2 Service Contract (`DynamicBankTransferService`)

The service exposes high-level domain operations:

- `getActiveMethods(): Collection<DynamicBankTransferDTO>` — Fetches active methods from cache/DB.
- `getByCode(string $code): ?DynamicBankTransferDTO` — Retrieves DTO for a specific method code.
- `createMethod(array $data): DynamicBankTransferDTO` — Creates record and flushes cache.
- `updateMethod(int $id, array $data): DynamicBankTransferDTO` — Updates record and flushes cache.
- `deleteMethod(int $id): bool` — Soft-deletes record and flushes cache.
- `buildOrderSnapshot(string $code): array` — Constructs the complete immutable snapshot for `order_payment.additional`.

---

## 8. Database Strategy

### 8.1 Schema: `dynamic_bank_transfers`

| Column | Type | Nullable | Default | Index | Purpose |
|:---|:---|:---|:---|:---|:---|
| `id` | `UNSIGNED INT` | No | auto | PK | System Primary Key |
| `code` | `VARCHAR(50)` | No | — | UNIQUE | Immutable Code: `dynamic_bank_transfer_{id}` |
| `is_active` | `BOOLEAN` | No | `false` | INDEX | Fast availability filter |
| `title` | `VARCHAR(255)` | No | — | — | Customer-facing title |
| `description` | `TEXT` | Yes | `NULL` | — | Customer-facing description |
| `bank_name` | `VARCHAR(255)` | No | — | — | Financial institution name |
| `account_holder_name` | `VARCHAR(255)` | No | — | — | Account owner name |
| `account_number` | `VARCHAR(50)` | Yes | `NULL` | — | Account number |
| `iban` | `VARCHAR(34)` | No | — | — | International Bank Account Number |
| `swift_code` | `VARCHAR(11)` | Yes | `NULL` | — | BIC/SWIFT code |
| `transfer_instructions` | `TEXT` | Yes | `NULL` | — | Post-checkout instructions |
| `logo_path` | `VARCHAR(255)` | Yes | `NULL` | — | File path in storage |
| `generate_invoice` | `BOOLEAN` | No | `false` | — | Invoice auto-gen flag |
| `invoice_status` | `VARCHAR(20)` | No | `'pending'` | — | Target invoice status |
| `order_status` | `VARCHAR(20)` | No | `'pending'` | — | Target order status |
| `sort_order` | `INT` | No | `0` | INDEX | Ordering weight |
| `channel_ids` | `JSON` | Yes | `NULL` | — | Channel restrictions |
| `created_at` | `TIMESTAMP` | Yes | — | — | Record creation time |
| `updated_at` | `TIMESTAMP` | Yes | — | — | Record modification time |
| `deleted_at` | `TIMESTAMP` | Yes | `NULL` | INDEX | Soft-delete timestamp |

### 8.2 Database Rules & Integrity

- **No Alteration to Bagisto Tables:** `cart_payment`, `order_payment`, and `core_config` schemas remain untouched.
- **Soft Delete Enforcement:** Hard deletion is blocked if any `order_payment` record matches the method code.
- **Logical Relation:** `order_payment.method` stores the code string. `order_payment.additional` stores the complete snapshot.

---

## 9. Dynamic Payment Registration Strategy

### 9.1 The `DynamicPaymentRegistry` Engine

To prevent raw `Config::set()` calls scattered inside Service Providers, we introduce a dedicated **Registry Service**: `DynamicPaymentRegistry`.

```
                  ┌──────────────────────────────┐
                  │    DynamicPaymentRegistry    │
                  └──────────────┬───────────────┘
                                 │
         ┌───────────────────────┴───────────────────────┐
         ▼                                               ▼
Registers Payment Methods                      Registers System Configs
into config('payment_methods')                  into config('core')
```

### 9.2 Registration Responsibilities

The `DynamicPaymentRegistry`:

1. Interacts with `DynamicBankTransferService` to retrieve active DTOs.
2. Formats and registers entries into `config('payment_methods')`.
3. Binds method codes to IoC Container resolution rules for `DynamicBankTransferMethod`.
4. Registers system config stubs into `config('core')` so Admin UI breadcrumbs and core config helpers resolve without throwing errors.

---

## 10. Runtime Registration Strategy & Execution Lifecycle

### 10.1 Deterministic Initialization Problem Statement

In Laravel, Service Provider `boot()` execution order can vary depending on package registration sequence. If `Payment::getPaymentMethods()` is invoked early (e.g., by a third-party package middleware or early boot listener) before `boot()` completes, dynamic methods might be missed.

### 10.2 The Solution: Early-Stage Execution & Lazy Guard Interceptor

To guarantee that dynamic payment methods are registered **before** any consumer queries `Payment Manager`, the registration strategy employs a **Two-Tier Initialization Guarantee**:

```
 ┌───────────────────────────────────────────────────────────────────────────┐
 │                        TIER 1: Boot-Phase Injection                       │
 │  DynamicBankTransferServiceProvider::boot()                               │
 │  - Calls DynamicPaymentRegistry::registerAll()                            │
 └─────────────────────────────────────┬─────────────────────────────────────┘
                                       │
                                       ▼
 ┌───────────────────────────────────────────────────────────────────────────┐
 │                       TIER 2: Lazy Guard Interceptor                      │
 │  DynamicPaymentRegistry::ensureRegistered()                               │
 │  - Event Listener on 'payment.manager.init' OR Lazy Guard check inside    │
 │    DynamicBankTransferService                                             │
 │  - Uses an internal boolean flag ($isRegistered = true)                   │
 │  - If Payment Manager is accessed early, ensureRegistered() executes      │
 └───────────────────────────────────────────────────────────────────────────┘
```

### 10.3 Execution Sequence Timeline

```
[Request Start]
       │
       ▼
[Laravel Boot Lifecycle]
       │
       ├─► Core Service Providers Boot
       │
       ├─► DynamicBankTransferServiceProvider::boot()
       │        │
       │        └─► DynamicPaymentRegistry::registerAll()
       │                 │
       │                 ├─► Fetch Active DTOs from DynamicBankTransferService
       │                 ├─► Inject entries into config('payment_methods')
       │                 └─► Set $isRegistered = true
       │
       ▼
[Route Execution / API Request]
       │
       ├─► Checkout API invoked (/api/v1/checkout/payment-methods)
       │        │
       │        └─► Payment::getPaymentMethods()
       │                 │
       │                 ├─► (Guard Check: DynamicPaymentRegistry::ensureRegistered())
       │                 └─► Iterates config('payment_methods') [Includes All Dynamic Banks]
       │
[Response Returned]
```

This guarantees 100% reliability regardless of framework startup sequence or package loading order.

---

## 11. Admin Architecture

### 11.1 Dedicated Navigation & UI

The dynamic bank transfer management interface is placed under:  
`Sales -> Bank Transfer Methods` (`admin.sales.dynamic_bank_transfers.index`).

### 11.2 DataGrid Component

Extends `Webkul\DataGrid\DataGrid` with:
- **Columns:** ID, Title, Bank Name, IBAN, Status (Toggle), Sort Order, Actions (Edit/Delete).
- **Mass Actions:** Mass Update Status, Mass Delete (Soft).

### 11.3 Form & Controller Flow

Admin actions interact strictly with `DynamicBankTransferService`:

```
Admin Form Submission -> Controller -> DynamicBankTransferService -> Repository -> Cache Clear -> Response
```

Admin UI changes instantly invalidate application cache, ensuring updated settings take effect immediately across all storefront requests.

---

## 12. Checkout Architecture

### 12.1 Storefront Rendering

Dynamic bank transfer methods are returned as native objects by `Payment::getPaymentMethods()`.  
The Vue 3 checkout component (`<v-payment-methods>`) in `Shop/checkout/onepage/payment.blade.php` renders them seamlessly as peer payment options:

```
[ Radio ] Cash On Delivery
[ Radio ] Bank Transfer - Al Rajhi Bank (Logo + Description + Title)
[ Radio ] Bank Transfer - SNB Al Ahli (Logo + Description + Title)
[ Radio ] Credit Card (Stripe)
```

No Vue code modifications or custom checkout templates are required.

---

## 13. Order Architecture & Complete Snapshot Specification

### 13.1 Complete Immutable Snapshot Requirement

To ensure historical orders remain **100% immutable and independent** of future admin edits, soft-deletes, or version changes, the `order_payment.additional` column shall store a **Complete Snapshot Payload**.

### 13.2 Complete Snapshot Payload Schema (`order_payment.additional`)

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

### 13.3 Snapshot Lifecycle & Order View Display

1. **Order Creation Event:** Listener on `checkout.order.save.before` invokes `DynamicBankTransferService::buildOrderSnapshot($code)` and attaches the result to `data['payment']['additional']`.
2. **Order Detail Render (Admin & Customer):** Admin and Storefront order detail Blade views inspect `order_payment.additional`. If `snapshot_version` exists, all payment metadata and bank details are rendered **strictly from the snapshot**, ignoring live database records.

---

## 14. Future Extensibility

### 14.1 Beyond Bank Transfers: Dynamic Payment Engine

The architecture introduced in v1.1 is designed as a **Generalized Dynamic Payment Engine**. While the initial implementation targets Bank Transfers (`DynamicBankTransfer`), the underlying contracts are intentionally abstracted:

```
                        ┌─────────────────────────────────┐
                        │     DynamicPaymentRegistry      │
                        └────────────────┬────────────────┘
                                         │
        ┌────────────────────────────────┼────────────────────────────────┐
        ▼                                ▼                                ▼
DynamicBankTransfer           DynamicGatewayProvider             DynamicDigitalWallet
(Bank Accounts)               (Custom PSPs)                      (Local e-Wallets)
```

### 14.2 Extensibility Design Proofs

1. **Generic Code Prefixing:** All dynamic methods register with structured namespaces (`dynamic_{type}_{id}`).
2. **Provider Pattern:** `DynamicPaymentRegistry` accepts any service implementing `DynamicPaymentProviderInterface`.
3. **Zero Core Modifications:** Future dynamic payment types (e.g., local buy-now-pay-later, regional custom gateways) can be added as micro-packages or sub-modules without touching the core registry logic.

---

## 15. Upgrade Compatibility

### 15.1 Core Protection Strategy

- **Zero Core Editing:** No changes inside `packages/Webkul/Admin`, `Shop`, `Payment`, `Sales`, `Core`, or `Checkout`.
- **Pure Custom Package:** All code resides in `packages/Webkul/DynamicBankTransfer/`.
- **Standard Bagisto Hooks:** Interacts solely via `mergeConfigFrom`, `Config::set()`, Laravel Service Providers, Event Listeners, and DataGrid contracts.

### 15.2 Bagisto Core Version Upgrades

When Bagisto is upgraded (e.g., v2.4.x → v2.5.x), the `DynamicBankTransfer` package remains 100% compatible because it relies on standard Laravel Service Provider boot cycles and public Bagisto event contracts.

---

## 16. Security Considerations

| Security Domain | Threat / Risk | Mitigation Strategy |
|:---|:---|:---|
| **Input Sanitization** | XSS injection in bank instructions or title | Strict input sanitization in Controller & Service; escaped output rendering via `{{ }}`. |
| **File Upload Security** | Malicious file upload via Logo field | Restricted to `bmp, jpeg, jpg, png, webp` MIME types. Max size 2MB. Stored in public disk with generated UUID filenames. |
| **Access Control** | Unauthorized access to Bank CRUD | Protected by dedicated ACL key (`sales.dynamic_bank_transfers`). |
| **Data Immutability** | Alteration of past order bank details | Order snapshot stored as immutable JSON in `order_payment.additional`. |
| **CSRF Protection** | Unauthenticated form submissions | Enforced by Laravel default web/admin CSRF middleware. |

---

## 17. Performance Considerations

| Metric | Target | Architecture Design |
|:---|:---|:---|
| **Boot Query Overhead** | 0 Queries (Cache Hit) | Active methods cached in Redis/Application Cache. |
| **Cache Miss Recovery** | < 5ms | Single indexed SELECT query on `dynamic_bank_transfers`. |
| **Checkout Render Overhead** | < 1ms | In-memory array iteration inside Payment Manager. |
| **Memory Footprint** | < 50KB | Minimal DTO instances stored in memory per request. |

---

## 18. Caching Strategy

### 18.1 Multi-Tier Cache Design

```
[Storefront Request] ──► [In-Memory Request Cache] ──► [Redis/App Cache ('dynamic_bank_transfers.active')] ──► [Database]
```

1. **Tag / Key:** `dynamic_bank_transfers.active`
2. **TTL:** Forever (until explicit invalidation)
3. **Invalidation Events:** Automatically triggered by `DynamicBankTransferService` on Create, Update, Soft-Delete, or Status Toggle.

---

## 19. Risk Analysis

| # | Risk | Probability | Severity | Mitigation |
|:---|:---|:---|:---|:---|
| **R1** | Early boot access to `Payment Manager` before Service Provider `boot()` completes. | Low | High | Resolved by Tier-2 Lazy Guard in `DynamicPaymentRegistry::ensureRegistered()`. |
| **R2** | Admin edits bank details after order is placed. | Medium | High | Resolved by Complete Immutable Snapshot v1.1 stored in `order_payment.additional`. |
| **R3** | Hard deletion of bank record breaks foreign order views. | Low | Medium | Enforced Soft Delete + Order view renders strictly from snapshot. |
| **R4** | Cache desynchronization across multi-server setup. | Low | Medium | Standard Redis cache clearing via Service layer on modification events. |

---

## 20. Alternatives Considered

| Approach | Scalability | Clean Architecture | Order Immutability | Decision |
|:---|:---|:---|:---|:---|
| **Static Config Duplication** | Poor | Poor | Poor | **Rejected** |
| **Sub-Account Dropdown in Checkout** | Medium | Medium | Medium | **Rejected** |
| **Dynamic Payment Provider (v1.1)** | **Unlimited** | **Clean Architecture** | **100% Immutable** | **ACCEPTED (Recommended)** |

---

## 21. Final Recommendation

**Architecture Specification v1.1 is approved for implementation.**

**Key Architecture Decisions Re-confirmed:**
1. Deterministic Runtime Registration via `DynamicPaymentRegistry` with Lazy Guard Protection.
2. Complete Decoupling of Payment Class from DB/Cache via `DynamicBankTransferService` DTOs.
3. Complete Immutable Order Snapshot v1.1 stored in `order_payment.additional`.
4. Extensible Design capable of supporting future dynamic payment providers.

---

## 22. Implementation Prerequisites

The following technical components must be created in the implementation phase:

### 22.1 Package & Structural Components
- `packages/Webkul/DynamicBankTransfer/src/Providers/DynamicBankTransferServiceProvider.php`
- `packages/Webkul/DynamicBankTransfer/src/Services/DynamicPaymentRegistry.php`
- `packages/Webkul/DynamicBankTransfer/src/Services/DynamicBankTransferService.php`
- `packages/Webkul/DynamicBankTransfer/src/DTOs/DynamicBankTransferDTO.php`
- `packages/Webkul/DynamicBankTransfer/src/Payment/DynamicBankTransferMethod.php`

### 22.2 Database & Repository Components
- Migration: `create_dynamic_bank_transfers_table`
- Model: `Webkul\DynamicBankTransfer\Models\DynamicBankTransfer`
- Repository: `Webkul\DynamicBankTransfer\Repositories\DynamicBankTransferRepository`

### 22.3 Admin & Event Components
- DataGrid: `Webkul\DynamicBankTransfer\DataGrids\DynamicBankTransferDataGrid`
- Controller: `Webkul\DynamicBankTransfer\Http\Controllers\Admin\DynamicBankTransferController`
- Event Listeners for Order Snapshot & Auto-Invoice Generation.
- Blade Views & Admin Route definitions.

---

**End of Specification v1.1**  
*This document represents the final approved architecture specification. Implementation specification and execution plans may proceed based on this document.*
