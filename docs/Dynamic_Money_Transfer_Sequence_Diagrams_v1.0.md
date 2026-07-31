# Dynamic Money Transfer Sequence Diagrams v1.0

**Project:** HIGEST (Bagisto 2.4.x / Laravel)  
**Document Type:** Enterprise Technical Sequence Specification  
**Classification:** Internal — Engineering Reference  
**Status:** Approved Sequence Diagram Blueprint v1.0 (Includes Failure Sequences, Authority Precedence & Transaction Boundaries)  
**Author Role:** Principal Software Architect & Lead Software Engineer  
**Reference Specification:** Dynamic Money Transfer Architecture Specification v1.1  
**Date:** 2026-07-30  

---

## Table of Contents

1. [Executive Summary, Authority Precedence & Transaction Boundaries](#1-executive-summary-authority-precedence--transaction-boundaries)
2. [Sequence Notation & Actor Definitions](#2-sequence-notation--actor-definitions)
3. [Flow 1: Create Bank Transfer Payment Method (Admin)](#3-flow-1-create-bank-transfer-payment-method-admin)
4. [Flow 2: Load Checkout Payment Methods (Storefront Customer)](#4-flow-2-load-checkout-payment-methods-storefront-customer)
5. [Flow 3: Select Payment Method & Save Cart Payment (Storefront Customer)](#5-flow-3-select-payment-method--save-cart-payment-storefront-customer)
6. [Flow 4: Create Order & Inject Complete Immutable Snapshot (Order Pipeline)](#6-flow-4-create-order--inject-complete-immutable-snapshot-order-pipeline)
7. [Flow 5: Auto-Generate Invoice & Update Order Status (Event Listener)](#7-flow-5-auto-generate-invoice--update-order-status-event-listener)
8. [Flow 6: Soft-Delete Bank Transfer Payment Method (Admin)](#8-flow-6-soft-delete-bank-transfer-payment-method-admin)
9. [Flow 7: Deactivate / Toggle Status of Bank Transfer Payment Method (Admin)](#9-flow-7-deactivate--toggle-status-of-bank-transfer-payment-method-admin)
10. [Flow 8: Multi-Tier Cache Refresh & Invalidation Lifecycle](#10-flow-8-multi-tier-cache-refresh--invalidation-lifecycle)
11. [Flow 9: Tier-2 Lazy Guard Registration Fallback Execution](#11-flow-9-tier-2-lazy-guard-registration-fallback-execution)
12. [Flow 10: [Failure Path] Snapshot Creation Exception Handling](#12-flow-10-failure-path-snapshot-creation-exception-handling)
13. [Flow 11: [Failure Path] Auto-Invoice Creation Exception & Resilience](#13-flow-11-failure-path-auto-invoice-creation-exception--resilience)
14. [Flow 12: [Failure Path] Cache Miss & Database Connection Outage](#14-flow-12-failure-path-cache-miss--database-connection-outage)
15. [Flow 13: [Failure Path] Active Cart Conflict on Method Deletion](#15-flow-13-failure-path-active-cart-conflict-on-method-deletion)
16. [Flow 14: [Failure Path] Logo File Upload Exception & Rollback](#16-flow-14-failure-path-logo-file-upload-exception--rollback)
17. [Flow 15: [Failure Path] Registry Configuration Injection Exception](#17-flow-15-failure-path-registry-configuration-injection-exception)

---

## 1. Executive Summary, Authority Precedence & Transaction Boundaries

### 1.1 Executive Summary

This document provides **formal, visually verifiable sequence diagrams** using standard Mermaid syntax for both successful (happy path) and failure (resilience path) execution flows of the `DynamicBankTransfer` system.

### 1.2 Document Authority & Precedence Hierarchy

These sequence diagrams provide **illustrative behavioral representations** of component interactions during runtime. To prevent this document from becoming a competing source of truth, the following **Strict Authority Precedence Hierarchy** is enforced across the HIGEST project:

```
┌───────────────────────────────────────────────────────────────────────────┐
│     1. Dynamic Money Transfer Architecture Specification v1.1            │
│        (HIGHEST AUTHORITY — Defines architectural boundaries & models)   │
└─────────────────────────────────────┬─────────────────────────────────────┘
                                      │ Overrides if conflict exists
                                      ▼
┌───────────────────────────────────────────────────────────────────────────┐
│     2. Dynamic Money Transfer Implementation Specification v1.0           │
│        (SECOND AUTHORITY — Defines component structure & execution order) │
└─────────────────────────────────────┬─────────────────────────────────────┘
                                      │ Overrides if conflict exists
                                      ▼
┌───────────────────────────────────────────────────────────────────────────┐
│     3. Dynamic Money Transfer Interface Contracts v1.0                     │
│        (THIRD AUTHORITY — Defines method signatures, types & exceptions) │
└─────────────────────────────────────┬─────────────────────────────────────┘
                                      │ Overrides if conflict exists
                                      ▼
┌───────────────────────────────────────────────────────────────────────────┐
│     4. Dynamic Money Transfer Sequence Diagrams v1.0                      │
│        (BEHAVIORAL VIEW — Illustrative runtime flow diagrams)              │
└───────────────────────────────────────────────────────────────────────────┘
```

In the event of any contradiction or ambiguity between documents, engineering teams shall defer strictly to the document with the higher precedence rank.

---

### 1.3 Database Transaction Boundaries & Concurrency Policy

To prevent database deadlocks, uncommitted dirty reads, or unintended transaction rollbacks, all system interactions strictly adhere to the following **Transaction Boundary Rules**:

#### 1. Inside Database Transactions (`DB::beginTransaction()` to `DB::commit()`):
* **Order Creation Pipeline (Flow 4):** Insertion of `orders`, `order_payment` (with Complete Snapshot v1.1 JSON), `order_items`, `order_addresses`, and inventory updates **MUST** occur inside a single atomic DB transaction. If any step fails, `DB::rollBack()` reverts the entire order.
* **Bank Method Creation / Update (Flow 1 & 7):** Insertion or modification of `dynamic_bank_transfers` row and system code assignment MUST occur within an atomic DB transaction.

#### 2. Outside Database Transactions (Post-Commit / Asynchronous Operations):
* **Auto-Invoice Generation (Flow 5 & 11):** Executes **after** order transaction commits (`checkout.order.save.after`). Auto-invoicing runs in its own separate transaction (`InvoiceRepository::create()`). A failure in auto-invoicing **MUST NOT** roll back the completed order.
* **Application Cache Invalidation (Flow 1, 6, 7, 8):** Redis/Cache operations (`Cache::forget()`, `Cache::rememberForever()`) run strictly **outside** DB transactions to avoid cache/DB state desynchronization if a DB transaction rolls back.
* **Storage File Uploads (Flow 1 & 14):** Disk file writes (`Storage::putFile()`) run **before** DB transaction start. If DB insertion fails, file cleanup is handled via exception catch blocks.
* **Dynamic Payment Registry Injection (Flow 9):** In-memory config array mutation (`config('payment_methods')`) runs outside DB transactions.

---

## 2. Sequence Notation & Actor Definitions

* **Admin User:** Store Administrator interacting with Admin Panel.
* **Customer User:** Storefront Customer navigating Checkout.
* **AdminController:** `Webkul\DynamicBankTransfer\Http\Controllers\Admin\DynamicBankTransferController`
* **OnepageController:** `Webkul\Shop\Http\Controllers\API\OnepageController`
* **DynamicBankTransferService:** Domain Service layer handling caching and business rules.
* **DynamicPaymentRegistry:** Dynamic config injection engine.
* **DynamicBankTransferRepository:** Eloquent Repository accessing DB.
* **PaymentManager:** Bagisto core Facade (`Webkul\Payment\Payment`).
* **OrderRepository:** Bagisto core Sales Order Repository (`Webkul\Sales\Repositories\OrderRepository`).
* **Cache System:** Redis / Application Cache (`dynamic_bank_transfers.active`).
* **Database:** MySQL relational DB.

---

## 3. Flow 1: Create Bank Transfer Payment Method (Admin)

```mermaid
sequenceDiagram
    autonumber
    actor Admin as Admin User
    participant Controller as AdminController
    participant Request as CreateFormRequest
    participant Service as DynamicBankTransferService
    participant Repo as DynamicBankTransferRepository
    participant Registry as DynamicPaymentRegistry
    participant Cache as Cache System
    participant Storage as File Storage
    participant DB as Database (MySQL)

    Admin->>Controller: POST /admin/sales/dynamic-bank-transfers/store
    Controller->>Request: Validate Input (Title, IBAN, Logo...)
    alt Validation Fails
        Request-->>Controller: Validation Exception
        Controller-->>Admin: 422 Unprocessable Entity (Errors)
    else Validation Passes
        Request-->>Controller: Validated Data Array
    end

    note over Service, Storage: Outside Transaction: Handle File Upload
    opt Logo File Uploaded
        Service->>Storage: putFile('public/dynamic-bank-transfers', logo)
        Storage-->>Service: logoPath string
    end

    Controller->>Service: createMethod(data)
    
    note over Service, DB: DB TRANSACTION START
    Service->>Repo: create(attributes)
    Repo->>DB: DB::beginTransaction()
    Repo->>DB: INSERT INTO dynamic_bank_transfers
    DB-->>Repo: Model Instance (ID = 7)
    Service->>DB: UPDATE dynamic_bank_transfers SET code = 'dynamic_bank_transfer_7' WHERE id = 7
    Repo->>DB: DB::commit()
    note over Service, DB: DB TRANSACTION END (Committed)

    note over Service, Cache: Outside Transaction: Cache & Registry Mutate
    Service->>Cache: forget('dynamic_bank_transfers.active')
    Cache-->>Service: Cache Cleared

    Service->>Registry: registerAll()
    Registry->>Service: getActiveMethods()
    Service->>Cache: get('dynamic_bank_transfers.active')
    Cache-->>Service: null (Cache Miss)
    Service->>Repo: getActiveRecords()
    Repo->>DB: SELECT * FROM dynamic_bank_transfers WHERE is_active=1 AND deleted_at IS NULL
    DB-->>Repo: Collection of Models
    Repo-->>Service: Collection of Models
    Service->>Cache: rememberForever('dynamic_bank_transfers.active', DTOs)
    Service-->>Registry: Collection<DynamicBankTransferDTO>
    
    Registry->>Registry: Inject config('payment_methods.dynamic_bank_transfer_7')
    Registry->>Registry: Inject config('core.sales.payment_methods.dynamic_bank_transfer_7')
    Registry->>Registry: Bind Container IoC Contextual Parameters

    Service-->>Controller: DynamicBankTransferDTO
    Controller-->>Admin: 302 Redirect to Index + Success Flash Message
```

---

## 4. Flow 2: Load Checkout Payment Methods (Storefront Customer)

```mermaid
sequenceDiagram
    autonumber
    actor Customer as Storefront Customer
    participant CheckoutUI as Checkout Vue Component
    participant API as OnepageController (API)
    participant PaymentMgr as PaymentManager (Bagisto Core)
    participant Registry as DynamicPaymentRegistry
    participant Service as DynamicBankTransferService
    participant Cache as Cache System
    participant PaymentClass as DynamicBankTransferMethod

    note over Customer, PaymentClass: Read-Only Operations (No DB Transaction)
    Customer->>CheckoutUI: Navigates to Payment Step
    CheckoutUI->>API: POST /api/v1/checkout/payment-methods
    API->>PaymentMgr: getSupportedPaymentMethods()
    
    PaymentMgr->>Registry: ensureRegistered()
    alt Is Already Registered
        Registry-->>PaymentMgr: void (Registered = True)
    else Needs Registration
        Registry->>Registry: registerAll()
    end

    PaymentMgr->>PaymentMgr: Config::get('payment_methods')
    note over PaymentMgr: Array includes native methods + dynamic_bank_transfer_7

    loop For Each Registered Payment Method
        PaymentMgr->>PaymentClass: app(DynamicBankTransferMethod::class)
        PaymentMgr->>PaymentClass: isAvailable()
        PaymentClass->>Service: getByCode('dynamic_bank_transfer_7')
        Service->>Cache: get('dynamic_bank_transfers.active')
        Cache-->>Service: Collection<DynamicBankTransferDTO>
        Service-->>PaymentClass: DynamicBankTransferDTO (id=7)
        PaymentClass->>PaymentClass: Check $dto->isActive & Channel Rules
        PaymentClass-->>PaymentMgr: bool (true)
        
        PaymentMgr->>PaymentClass: getTitle()
        PaymentClass-->>PaymentMgr: "Bank Transfer - Al Rajhi Bank"
        PaymentMgr->>PaymentClass: getImage()
        PaymentClass-->>PaymentMgr: "https://higest.com/storage/alrajhi-logo.png"
    end

    PaymentMgr->>PaymentMgr: Sort methods by 'sort' order
    PaymentMgr-->>API: Array of Payment Method Objects
    API-->>CheckoutUI: 200 OK (Json JSONResource)
    CheckoutUI-->>Customer: Renders Peer Radio Buttons (COD, Rajhi, SNB, Stripe)
```

---

## 5. Flow 3: Select Payment Method & Save Cart Payment (Storefront Customer)

```mermaid
sequenceDiagram
    autonumber
    actor Customer as Storefront Customer
    participant CheckoutUI as Checkout Vue Component
    participant API as OnepageController (API)
    participant CartFacade as Cart Facade (Bagisto Core)
    participant CartPayment as CartPayment Model
    participant DB as Database (MySQL)

    Customer->>CheckoutUI: Selects "Bank Transfer - Al Rajhi Bank"
    CheckoutUI->>API: POST /api/v1/checkout/payment-methods { payment: { method: 'dynamic_bank_transfer_7' } }
    
    API->>CartFacade: savePaymentMethod(['method' => 'dynamic_bank_transfer_7'])
    
    note over CartFacade, DB: DB TRANSACTION START (Atomic Cart Payment Save)
    CartFacade->>DB: DB::beginTransaction()
    opt Cart Payment Record Exists
        CartFacade->>CartPayment: delete existing cart_payment row
        CartPayment->>DB: DELETE FROM cart_payment WHERE cart_id = X
    end

    CartFacade->>CartPayment: new CartPayment()
    CartFacade->>CartPayment: $cartPayment->method = 'dynamic_bank_transfer_7'
    CartFacade->>CartPayment: $cartPayment->method_title = core()->getConfigData('sales.payment_methods.dynamic_bank_transfer_7.title')
    CartFacade->>DB: INSERT INTO cart_payment (cart_id, method, method_title)
    CartFacade->>DB: DB::commit()
    note over CartFacade, DB: DB TRANSACTION END (Committed)
    
    CartFacade->>CartFacade: collectTotals()
    CartFacade-->>API: Cart Model Instance
    API-->>CheckoutUI: 200 OK { cart: CartResource }
    CheckoutUI-->>Customer: Highlights Selection & Enables "Place Order" Button
```

---

## 6. Flow 4: Create Order & Inject Complete Immutable Snapshot (Order Pipeline)

```mermaid
sequenceDiagram
    autonumber
    actor Customer as Storefront Customer
    participant CheckoutUI as Checkout Vue Component
    participant API as OnepageController (API)
    participant OrderResource as OrderResource Transformer
    participant OrderRepo as OrderRepository (Bagisto Core)
    participant EventMgr as Laravel Event Manager
    participant Listener as OrderPaymentSnapshotListener
    participant Service as DynamicBankTransferService
    participant DB as Database (MySQL)

    Customer->>CheckoutUI: Clicks "Place Order"
    CheckoutUI->>API: POST /api/v1/checkout/save-order
    API->>API: validateOrder()
    API->>OrderResource: (new OrderResource($cart))->jsonSerialize()
    OrderResource-->>API: $orderData Array

    note over OrderRepo, DB: ATOMIC ORDER CREATION DB TRANSACTION START
    API->>OrderRepo: create($orderData)
    OrderRepo->>DB: DB::beginTransaction()
    
    OrderRepo->>EventMgr: dispatch('checkout.order.save.before', [$orderData])
    EventMgr->>Listener: handle($orderData)
    
    Listener->>Service: buildOrderSnapshot('dynamic_bank_transfer_7')
    Service->>Service: Fetch DTO from Memory / Cache
    Service-->>Listener: Complete Snapshot Array (v1.1 Schema)

    Listener->>Listener: Mutate $orderData['payment']['additional'] = Snapshot Array

    OrderRepo->>DB: INSERT INTO orders ...
    DB-->>OrderRepo: Order Instance (id = 1050)
    OrderRepo->>DB: INSERT INTO order_payment (order_id, method, method_title, additional)
    note over DB: additional column receives Complete Snapshot JSON v1.1
    
    OrderRepo->>DB: INSERT INTO order_items, order_addresses ...
    OrderRepo->>DB: DB::commit()
    note over OrderRepo, DB: DB TRANSACTION END (Committed)

    note over OrderRepo, EventMgr: Outside Order Transaction: Post-Save Event Dispatched
    OrderRepo->>EventMgr: dispatch('checkout.order.save.after', $order)

    OrderRepo-->>API: Order Instance
    API-->>CheckoutUI: 200 OK { redirect: true, redirect_url: '/checkout/onepage/success' }
    CheckoutUI-->>Customer: Redirects to Success Page
```

---

## 7. Flow 5: Auto-Generate Invoice & Update Order Status (Event Listener)

```mermaid
sequenceDiagram
    autonumber
    participant EventMgr as Laravel Event Manager
    participant Listener as AutoInvoiceListener
    participant Service as DynamicBankTransferService
    participant InvoiceRepo as InvoiceRepository (Bagisto Core)
    participant DB as Database (MySQL)

    note over EventMgr, DB: Outside Main Order Transaction (Separate Invoice Transaction)
    EventMgr->>Listener: handle($order) [Triggered by 'checkout.order.save.after']
    
    Listener->>Listener: Extract $methodCode = $order->payment->method
    
    alt Method Code starts with 'dynamic_bank_transfer_'
        Listener->>Service: getByCode($methodCode)
        Service-->>Listener: DynamicBankTransferDTO
        
        alt $dto->generateInvoice == true
            Listener->>Listener: prepareInvoiceData($order)
            note over InvoiceRepo, DB: INDEPENDENT INVOICE DB TRANSACTION START
            Listener->>InvoiceRepo: create(data, $dto->invoiceStatus, $dto->orderStatus)
            InvoiceRepo->>DB: DB::beginTransaction()
            InvoiceRepo->>DB: INSERT INTO invoices (order_id, state...)
            InvoiceRepo->>DB: INSERT INTO invoice_items ...
            InvoiceRepo->>DB: UPDATE orders SET status = $dto->orderStatus
            InvoiceRepo->>DB: DB::commit()
            note over InvoiceRepo, DB: INDEPENDENT INVOICE DB TRANSACTION END (Committed)
            InvoiceRepo-->>Listener: Invoice Instance
        else $dto->generateInvoice == false
            Listener-->>EventMgr: Do Nothing (Manual Invoice Required)
        end
    else Other Payment Method
        Listener-->>EventMgr: Skip Listener
    end
```

---

## 8. Flow 6: Soft-Delete Bank Transfer Payment Method (Admin)

```mermaid
sequenceDiagram
    autonumber
    actor Admin as Admin User
    participant Controller as AdminController
    participant Service as DynamicBankTransferService
    participant Repo as DynamicBankTransferRepository
    participant Registry as DynamicPaymentRegistry
    participant Cache as Cache System
    participant DB as Database (MySQL)

    Admin->>Controller: DELETE /admin/sales/dynamic-bank-transfers/7
    Controller->>Service: deleteMethod(7)
    
    Service->>Repo: find(7)
    Repo->>DB: SELECT * FROM dynamic_bank_transfers WHERE id = 7
    DB-->>Repo: Model Instance
    
    Service->>Service: Check active cart constraints
    
    note over Service, DB: DB TRANSACTION START
    Service->>Repo: delete(7)
    Repo->>DB: DB::beginTransaction()
    Repo->>DB: UPDATE dynamic_bank_transfers SET deleted_at = NOW() WHERE id = 7
    Repo->>DB: DB::commit()
    note over Service, DB: DB TRANSACTION END (Committed)
    
    note over Service, Cache: Outside Transaction: Cache Clear & Registry Refresh
    Service->>Cache: forget('dynamic_bank_transfers.active')
    Cache-->>Service: Cache Cleared
    
    Service->>Registry: registerAll()
    Registry->>Cache: get('dynamic_bank_transfers.active')
    Cache-->>Registry: null
    Registry->>Repo: getActiveRecords()
    Repo->>DB: SELECT * FROM dynamic_bank_transfers WHERE is_active=1 AND deleted_at IS NULL
    DB-->>Repo: Collection (Excludes ID 7)
    
    Registry->>Registry: Re-inject config('payment_methods') without ID 7
    
    Service-->>Controller: true
    Controller-->>Admin: 302 Redirect + Success Flash Message ("Bank Account Removed")
```

---

## 9. Flow 7: Deactivate / Toggle Status of Bank Transfer Payment Method (Admin)

```mermaid
sequenceDiagram
    autonumber
    actor Admin as Admin User
    participant Controller as AdminController
    participant Service as DynamicBankTransferService
    participant Repo as DynamicBankTransferRepository
    participant Cache as Cache System
    participant DB as Database (MySQL)

    Admin->>Controller: POST /admin/sales/dynamic-bank-transfers/7/toggle-status
    Controller->>Service: updateMethod(7, ['is_active' => false])
    
    note over Service, DB: DB TRANSACTION START
    Service->>Repo: update(['is_active' => false], 7)
    Repo->>DB: DB::beginTransaction()
    Repo->>DB: UPDATE dynamic_bank_transfers SET is_active = 0 WHERE id = 7
    Repo->>DB: DB::commit()
    note over Service, DB: DB TRANSACTION END (Committed)
    
    note over Service, Cache: Outside Transaction: Cache Clear
    Service->>Cache: forget('dynamic_bank_transfers.active')
    Cache-->>Service: Cache Cleared
    
    Service-->>Controller: DynamicBankTransferDTO (isActive = false)
    Controller-->>Admin: 200 OK { success: true, is_active: false }
```

---

## 10. Flow 8: Multi-Tier Cache Refresh & Invalidation Lifecycle

```mermaid
sequenceDiagram
    autonumber
    participant App as Application Request
    participant Service as DynamicBankTransferService
    participant MemCache as Request-Level Memory Cache
    participant Redis as Application Cache (Redis)
    participant Repo as DynamicBankTransferRepository
    participant DB as Database (MySQL)

    App->>Service: getByCode('dynamic_bank_transfer_7')
    
    alt In Request-Level Memory Cache
        Service-->>App: DynamicBankTransferDTO (0ms overhead)
    else Not in Memory Cache
        Service->>Redis: Cache::get('dynamic_bank_transfers.active')
        
        alt Redis Cache Hit
            Redis-->>Service: Serialized DTO Collection
            Service->>MemCache: Populate $requestCache['dynamic_bank_transfer_7']
            Service-->>App: DynamicBankTransferDTO (< 2ms overhead)
        else Redis Cache Miss
            Service->>Repo: getActiveRecords()
            Repo->>DB: SELECT * FROM dynamic_bank_transfers WHERE is_active=1 AND deleted_at IS NULL
            DB-->>Repo: Collection of Eloquent Models
            Repo-->>Service: Collection of Eloquent Models
            
            Service->>Service: Map Models to DynamicBankTransferDTOs
            Service->>Redis: Cache::rememberForever('dynamic_bank_transfers.active', DTOs)
            Service->>MemCache: Populate $requestCache
            Service-->>App: DynamicBankTransferDTO (< 10ms miss penalty)
        end
    end
```

---

## 11. Flow 9: Tier-2 Lazy Guard Registration Fallback Execution

```mermaid
sequenceDiagram
    autonumber
    participant App as Middleware / Early Listener
    participant PaymentMgr as PaymentManager (Bagisto Core)
    participant Registry as DynamicPaymentRegistry
    participant Service as DynamicBankTransferService

    note over App, PaymentMgr: Scenario: Early package accesses PaymentManager before ServiceProvider::boot() completes

    App->>PaymentMgr: Payment::getPaymentMethods()
    PaymentMgr->>Registry: ensureRegistered()
    
    Registry->>Registry: Check boolean $isRegistered
    
    alt $isRegistered == true
        Registry-->>PaymentMgr: Skip (Already Registered)
    else $isRegistered == false
        note over Registry: Trigger Emergency Fallback Registration
        Registry->>Service: getActiveMethods()
        Service-->>Registry: Collection<DynamicBankTransferDTO>
        Registry->>Registry: Inject config('payment_methods')
        Registry->>Registry: Inject config('core')
        Registry->>Registry: Bind Container IoC Rules
        Registry->>Registry: Set $isRegistered = true
        Registry-->>PaymentMgr: Fallback Registration Complete
    end

    PaymentMgr->>PaymentMgr: Safely iterate config('payment_methods') [Dynamic methods guaranteed present]
    PaymentMgr-->>App: Collection of Payment Methods
```

---

## 12. Flow 10: [Failure Path] Snapshot Creation Exception Handling

```mermaid
sequenceDiagram
    autonumber
    actor Customer as Storefront Customer
    participant API as OnepageController (API)
    participant OrderRepo as OrderRepository (Bagisto Core)
    participant EventMgr as Laravel Event Manager
    participant Listener as OrderPaymentSnapshotListener
    participant Service as DynamicBankTransferService
    participant Log as Laravel Log System
    participant DB as Database (MySQL)

    Customer->>API: POST /api/v1/checkout/save-order
    
    note over OrderRepo, DB: ATOMIC ORDER TRANSACTION START
    API->>OrderRepo: create($orderData)
    OrderRepo->>DB: DB::beginTransaction()
    OrderRepo->>EventMgr: dispatch('checkout.order.save.before', [$orderData])
    EventMgr->>Listener: handle($orderData)
    
    Listener->>Service: buildOrderSnapshot('dynamic_bank_transfer_999')
    Service-->>Listener: throws InvalidPaymentMethodCodeException
    
    Listener->>Log: Log::error("Snapshot creation failed for method code: dynamic_bank_transfer_999")
    Listener-->>OrderRepo: throws OrderSnapshotException
    
    note over OrderRepo, DB: TRANSACTION ROLLBACK TRIGGERED
    OrderRepo->>DB: DB::rollBack()
    note over OrderRepo, DB: DB TRANSACTION END (Rolled Back - No DB Changes)
    
    OrderRepo-->>API: throws OrderSnapshotException
    API-->>Customer: 500 Internal Error ("Payment method details could not be verified. Please refresh checkout.")
```

---

## 13. Flow 11: [Failure Path] Auto-Invoice Creation Exception & Resilience

```mermaid
sequenceDiagram
    autonumber
    participant EventMgr as Laravel Event Manager
    participant Listener as AutoInvoiceListener
    participant Service as DynamicBankTransferService
    participant InvoiceRepo as InvoiceRepository (Bagisto Core)
    participant Log as Laravel Log System
    participant DB as Database (MySQL)

    note over EventMgr, DB: Main Order Transaction ALREADY COMMITTED
    EventMgr->>Listener: handle($order) [checkout.order.save.after]
    Listener->>Service: getByCode('dynamic_bank_transfer_7')
    Service-->>Listener: DynamicBankTransferDTO (generateInvoice = true)
    
    note over InvoiceRepo, DB: SEPARATE INVOICE TRANSACTION START
    Listener->>InvoiceRepo: create(data, 'paid', 'processing')
    InvoiceRepo->>DB: DB::beginTransaction()
    InvoiceRepo->>DB: INSERT INTO invoices ...
    DB-->>InvoiceRepo: DB Exception (e.g., deadlock or constraint violation)
    InvoiceRepo->>DB: DB::rollBack()
    note over InvoiceRepo, DB: INVOICE TRANSACTION ROLLED BACK
    InvoiceRepo-->>Listener: throws QueryException
    
    note over Listener: Resilient Exception Handling (Order Remains Valid & Created)
    Listener->>Log: Log::error("Auto-invoice generation failed for order #1050: " . $e->getMessage())
    Listener-->>EventMgr: Handled Gracefully (Order remains created in 'pending_payment' status)
```

---

## 14. Flow 12: [Failure Path] Cache Miss & Database Connection Outage

```mermaid
sequenceDiagram
    autonumber
    participant App as Storefront Checkout
    participant Service as DynamicBankTransferService
    participant Redis as Application Cache (Redis)
    participant Repo as DynamicBankTransferRepository
    participant Log as Laravel Log System
    participant DB as Database (MySQL)

    App->>Service: getByCode('dynamic_bank_transfer_7')
    Service->>Redis: Cache::get('dynamic_bank_transfers.active')
    Redis-->>Service: null (Cache Miss)
    
    Service->>Repo: getActiveRecords()
    Repo->>DB: SELECT * FROM dynamic_bank_transfers ...
    DB-->>Repo: Database Connection Timeout / Exception
    Repo-->>Service: throws QueryException
    
    Service->>Log: Log::emergency("Database connection down during dynamic payment retrieval")
    Service-->>App: returns null (Method isAvailable() returns false)
    note over App: Storefront degrades gracefully by excluding affected bank method from checkout list
```

---

## 15. Flow 13: [Failure Path] Active Cart Conflict on Method Deletion

```mermaid
sequenceDiagram
    autonumber
    actor Admin as Admin User
    participant Controller as AdminController
    participant Service as DynamicBankTransferService
    participant Repo as DynamicBankTransferRepository
    participant DB as Database (MySQL)

    Admin->>Controller: DELETE /admin/sales/dynamic-bank-transfers/7
    Controller->>Service: deleteMethod(7)
    
    Service->>Repo: hasActivePendingCarts('dynamic_bank_transfer_7')
    Repo->>DB: SELECT COUNT(*) FROM cart_payment WHERE method = 'dynamic_bank_transfer_7'
    DB-->>Repo: Count = 15 (Active carts in checkout)
    
    Service-->>Controller: throws MethodDeletionRestrictedException("Cannot delete: 15 active carts are currently using this bank method.")
    Controller-->>Admin: 422 Unprocessable Entity (User Flash Warning: "Deactivate the method first instead of deleting.")
```

---

## 16. Flow 14: [Failure Path] Logo File Upload Exception & Rollback

```mermaid
sequenceDiagram
    autonumber
    actor Admin as Admin User
    participant Controller as AdminController
    participant Request as CreateFormRequest
    participant Service as DynamicBankTransferService
    participant Storage as File Storage
    participant DB as Database (MySQL)

    Admin->>Controller: POST /admin/sales/dynamic-bank-transfers/store
    Controller->>Request: Validate Input
    Request-->>Controller: Validated Data Array
    
    Controller->>Service: createMethod(data)
    note over Service, Storage: Storage Operation Starts Before DB Transaction
    Service->>Storage: putFile('public/dynamic-bank-transfers', invalid_file)
    Storage-->>Service: throws FileException ("Disk full or permission denied")
    
    note over Service: Rollback storage operation
    Service->>Storage: delete temp files if any
    Service-->>Controller: throws FileUploadException
    Controller-->>Admin: 422 Unprocessable Entity ("Failed to save logo image. Please try again.")
```

---

## 17. Flow 15: [Failure Path] Registry Configuration Injection Exception

```mermaid
sequenceDiagram
    autonumber
    participant Provider as ServiceProvider
    participant Registry as DynamicPaymentRegistry
    participant Service as DynamicBankTransferService
    participant Log as Laravel Log System

    Provider->>Registry: registerAll()
    Registry->>Service: getActiveMethods()
    Service-->>Registry: throws CacheServiceException ("Redis connection failed")
    
    Registry->>Log: Log::error("DynamicPaymentRegistry: Failed to fetch active methods for registration")
    Registry->>Registry: Fallback to empty array registration
    Registry-->>Provider: Handled Gracefully (App boots, dynamic methods hidden until Redis recovers)
```

---

**End of Sequence Diagrams Specification v1.0**  
*This document constitutes the official sequence diagram specification. Implementation shall strictly follow the message ordering, authority precedence hierarchy, database transaction boundaries, cache invalidation cycles, and failure handling pathways defined herein.*
