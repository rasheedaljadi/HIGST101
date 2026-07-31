# Dynamic Money Transfer Interface Contracts v1.0

**Project:** HIGEST (Bagisto 2.4.x / Laravel)  
**Document Type:** Enterprise Interface Contract Specification  
**Classification:** Internal — Engineering Reference  
**Status:** Official Interface Contract Blueprint v1.0  
**Author Role:** Principal Software Architect & Lead Software Engineer  
**Reference Specification:** Dynamic Money Transfer Architecture Specification v1.1  
**Date:** 2026-07-30  

---

## Table of Contents

1. [Executive Summary](#1-executive-summary)
2. [Contract Conventions & Type Safety Principles](#2-contract-conventions--type-safety-principles)
3. [Service Layer Contract: `DynamicBankTransferServiceContract`](#3-service-layer-contract-dynamicbanktransferservicecontract)
4. [Registry Engine Contract: `DynamicPaymentRegistryContract`](#4-registry-engine-contract-dynamicpaymentregistrycontract)
5. [Repository Contract: `DynamicBankTransferRepositoryContract`](#5-repository-contract-dynamicbanktransferrepositorycontract)
6. [Payment Method Class Contract: `DynamicBankTransferMethodContract`](#6-payment-method-class-contract-dynamicbanktransfermethodcontract)
7. [DTO Specification: `DynamicBankTransferDTO`](#7-dto-specification-dynamicbanktransferdto)
8. [Event Listener Contracts](#8-event-listener-contracts)
9. [Form Request & Validation Rule Contracts](#9-form-request--validation-rule-contracts)
10. [Bagisto Extension Points Map & Resilience Matrix](#10-bagisto-extension-points-map--resilience-matrix)
11. [Architectural Clarification & Future Evolution Record](#11-architectural-clarification--future-evolution-record)

---

## 1. Executive Summary

This document defines the **formal, binding interface contracts** for all core services, repositories, registry engines, DTOs, and event listeners within the `DynamicBankTransfer` package. 

It establishes strict type hints, return types, nullability boundaries, exceptions thrown, and side effects for every public method. By formalizing these contracts before code execution, all engineers working on the HIGEST platform share an unambiguous blueprint that guarantees Clean Architecture principles, full IDE static analysis safety, and zero regression against Bagisto 2.4.x.

---

## 2. Contract Conventions & Type Safety Principles

1. **Strict Typing:** All method contracts mandate strict return types (`string`, `array`, `bool`, `Collection`, `void`) and parameter types.
2. **DTO Immutability:** Data passed between the Repository/Service layer and the Payment/Admin/Checkout layers uses read-only Data Transfer Objects (`DynamicBankTransferDTO`). Eloquent models shall not be exposed outside the Repository layer.
3. **Explicit Exception Contracts:** Every method documents its possible exception types. Silent null fallbacks or swallowed exceptions are strictly prohibited.
4. **Side-Effect Documentation:** Methods that alter in-memory configuration (`config('payment_methods')`), mutate Redis application cache, or write storage files explicitly state their side effects.

---

## 3. Service Layer Contract: `DynamicBankTransferServiceContract`

**Namespace:** `Webkul\DynamicBankTransfer\Contracts\DynamicBankTransferServiceContract`  
**Target Class:** `Webkul\DynamicBankTransfer\Services\DynamicBankTransferService`

---

### 3.1 `getActiveMethods()`

* **Purpose:** Retrieves all active dynamic bank transfer payment methods for storefront display and runtime registration.
* **Input Parameters:**  
  * None.
* **Return Type:** `Illuminate\Support\Collection<Webkul\DynamicBankTransfer\DTOs\DynamicBankTransferDTO>` (Always returns a Collection, empty if no active records).
* **Exceptions Thrown:**  
  * `Webkul\DynamicBankTransfer\Exceptions\CacheServiceException` — Thrown if Redis/Cache fails to read or deserialize.
* **Nullability Rules:** Non-nullable.
* **Side Effects:** Queries application cache (`dynamic_bank_transfers.active`). If cache miss occurs, queries Repository and populates application cache.

---

### 3.2 `getByCode(string $code)`

* **Purpose:** Fetches a single dynamic bank transfer method by its system code (e.g., `dynamic_bank_transfer_7`).
* **Input Parameters:**  
  * `code` (`string`, Non-nullable): The unique method code.
* **Return Type:** `?Webkul\DynamicBankTransfer\DTOs\DynamicBankTransferDTO` (Nullable DTO instance).
* **Exceptions Thrown:**  
  * `InvalidArgumentException` — Thrown if `$code` format is invalid (does not start with `dynamic_bank_transfer_`).
* **Nullability Rules:** Returns `null` if method code does not exist or is soft-deleted.
* **Side Effects:** Uses request-level memory cache first, then application cache, then Repository. Executes `DynamicPaymentRegistryContract::ensureRegistered()` Tier-2 guard if needed.

---

### 3.3 `createMethod(array $data)`

* **Purpose:** Validates, creates a new bank transfer record, handles logo file storage, generates code, and flushes cache.
* **Input Parameters:**  
  * `data` (`array`, Non-nullable): Validated input array containing `title`, `bank_name`, `iban`, `account_holder_name`, `account_number`, `swift_code`, `transfer_instructions`, `logo`, `generate_invoice`, `invoice_status`, `order_status`, `sort_order`, `is_active`, `channel_ids`.
* **Return Type:** `Webkul\DynamicBankTransfer\DTOs\DynamicBankTransferDTO` (Created instance DTO).
* **Exceptions Thrown:**  
  * `Webkul\DynamicBankTransfer\Exceptions\MethodCreationException` — Thrown if database insertion fails.
  * `Illuminate\Http\Exceptions\PostTooLargeException` / `FileException` — Thrown if logo file upload fails.
* **Nullability Rules:** Non-nullable.
* **Side Effects:** Writes row to `dynamic_bank_transfers` DB table. Writes logo to `public/dynamic-bank-transfers/` storage. Triggers `Cache::forget('dynamic_bank_transfers.active')`. Triggers runtime re-registration in `DynamicPaymentRegistry`.

---

### 3.4 `updateMethod(int $id, array $data)`

* **Purpose:** Updates an existing dynamic bank transfer method, manages logo updates, and flushes cache.
* **Input Parameters:**  
  * `id` (`int`, Non-nullable): Primary key ID of the record.
  * `data` (`array`, Non-nullable): Array of updated fields.
* **Return Type:** `Webkul\DynamicBankTransfer\DTOs\DynamicBankTransferDTO` (Updated DTO).
* **Exceptions Thrown:**  
  * `Illuminate\Database\Eloquent\ModelNotFoundException` — Thrown if ID does not exist.
  * `Webkul\DynamicBankTransfer\Exceptions\MethodUpdateException` — Thrown if update query fails.
* **Nullability Rules:** Non-nullable.
* **Side Effects:** Mutates database record. Replaces old logo in storage if new image provided. Triggers `Cache::forget('dynamic_bank_transfers.active')`.

---

### 3.5 `deleteMethod(int $id)`

* **Purpose:** Soft-deletes a dynamic bank transfer method after verifying no hard constraints are broken.
* **Input Parameters:**  
  * `id` (`int`, Non-nullable): Primary key ID of the record.
* **Return Type:** `bool` (`true` on success).
* **Exceptions Thrown:**  
  * `Illuminate\Database\Eloquent\ModelNotFoundException` — Thrown if ID does not exist.
  * `Webkul\DynamicBankTransfer\Exceptions\MethodDeletionRestrictedException` — Thrown if hard delete is attempted on a record linked to pending active carts.
* **Nullability Rules:** Non-nullable.
* **Side Effects:** Sets `deleted_at` timestamp in DB. Triggers `Cache::forget('dynamic_bank_transfers.active')`. Removes registration from `config('payment_methods')`.

---

### 3.6 `buildOrderSnapshot(string $code)`

* **Purpose:** Constructs the **Complete Immutable Snapshot v1.1 JSON payload** for injection into `order_payment.additional`.
* **Input Parameters:**  
  * `code` (`string`, Non-nullable): The unique payment method code.
* **Return Type:** `array` (Structured key-value snapshot array conforming to v1.1 schema).
* **Exceptions Thrown:**  
  * `Webkul\DynamicBankTransfer\Exceptions\InvalidPaymentMethodCodeException` — Thrown if method code does not exist.
* **Nullability Rules:** Non-nullable array.
* **Side Effects:** None (Pure calculation / mapping function).

---

## 4. Registry Engine Contract: `DynamicPaymentRegistryContract`

**Namespace:** `Webkul\DynamicBankTransfer\Contracts\DynamicPaymentRegistryContract`  
**Target Class:** `Webkul\DynamicBankTransfer\Services\DynamicPaymentRegistry`

---

### 4.1 `registerAll()`

* **Purpose:** Iterates active dynamic bank transfer DTOs and registers them into Laravel/Bagisto config arrays (`config('payment_methods')` and `config('core')`), binding container parameters.
* **Input Parameters:**  
  * None.
* **Return Type:** `void`.
* **Exceptions Thrown:**  
  * `Webkul\DynamicBankTransfer\Exceptions\RegistryInjectionException` — Thrown if config injection fails.
* **Nullability Rules:** None.
* **Side Effects:** Mutates `config('payment_methods')` in memory. Mutates `config('core')` in memory. Registers IoC container bindings for `DynamicBankTransferMethod`. Sets internal state `$isRegistered = true`.

---

### 4.2 `ensureRegistered()`

* **Purpose:** Tier-2 Lazy Guard. Evaluates internal registration flag `$isRegistered`. If `false`, calls `registerAll()`.
* **Input Parameters:**  
  * None.
* **Return Type:** `void`.
* **Exceptions Thrown:**  
  * None.
* **Nullability Rules:** None.
* **Side Effects:** Executes `registerAll()` if registration has not yet executed in current request lifecycle.

---

### 4.3 `isRegistered()`

* **Purpose:** Queries whether dynamic registration has executed in the current request lifecycle.
* **Input Parameters:**  
  * None.
* **Return Type:** `bool` (`true` if registered, `false` otherwise).
* **Exceptions Thrown:**  
  * None.
* **Nullability Rules:** Non-nullable.
* **Side Effects:** Read-only inspection of internal boolean flag.

---

## 5. Repository Contract: `DynamicBankTransferRepositoryContract`

**Namespace:** `Webkul\DynamicBankTransfer\Contracts\DynamicBankTransferRepositoryContract`  
**Target Class:** `Webkul\DynamicBankTransfer\Repositories\DynamicBankTransferRepository`

---

### 5.1 `getActiveRecords()`

* **Purpose:** Queries database for all non-deleted, active bank records ordered by `sort_order`.
* **Input Parameters:**  
  * None.
* **Return Type:** `Illuminate\Database\Eloquent\Collection<Webkul\DynamicBankTransfer\Models\DynamicBankTransfer>`
* **Exceptions Thrown:**  
  * `Illuminate\Database\QueryException` — Thrown on DB connection failure.
* **Nullability Rules:** Non-nullable Collection.
* **Side Effects:** Executes database SELECT query.

---

### 5.2 `findByCode(string $code)`

* **Purpose:** Finds model instance by unique code column.
* **Input Parameters:**  
  * `code` (`string`, Non-nullable): The method code.
* **Return Type:** `?Webkul\DynamicBankTransfer\Models\DynamicBankTransfer`
* **Exceptions Thrown:**  
  * None.
* **Nullability Rules:** Returns `null` if not found.
* **Side Effects:** Executes indexed database query.

---

## 6. Payment Method Class Contract: `DynamicBankTransferMethodContract`

**Namespace:** `Webkul\DynamicBankTransfer\Contracts\DynamicBankTransferMethodContract`  
**Target Class:** `Webkul\DynamicBankTransfer\Payment\DynamicBankTransferMethod` (Extends `Webkul\Payment\Payment\Payment`)

---

### 6.1 `getCode()`

* **Purpose:** Returns the dynamic payment method code assigned to this instance.
* **Input Parameters:** None.
* **Return Type:** `string` (e.g., `"dynamic_bank_transfer_7"`).
* **Exceptions Thrown:** None.
* **Nullability Rules:** Non-nullable string.
* **Side Effects:** Read-only getter.

---

### 6.2 `getTitle()`

* **Purpose:** Returns customer-facing method title by querying `DynamicBankTransferService`.
* **Input Parameters:** None.
* **Return Type:** `string`.
* **Exceptions Thrown:** None.
* **Nullability Rules:** Returns default translated string if DTO title is empty.
* **Side Effects:** Calls `DynamicBankTransferService::getByCode()`.

---

### 6.3 `getDescription()`

* **Purpose:** Returns customer-facing method description.
* **Input Parameters:** None.
* **Return Type:** `string`.
* **Exceptions Thrown:** None.
* **Nullability Rules:** Returns empty string `""` if description is null.
* **Side Effects:** Calls `DynamicBankTransferService::getByCode()`.

---

### 6.4 `getImage()`

* **Purpose:** Returns complete logo URL for storefront rendering.
* **Input Parameters:** None.
* **Return Type:** `string` (Full URL to logo or default fallback asset).
* **Exceptions Thrown:** None.
* **Nullability Rules:** Non-nullable URL string.
* **Side Effects:** Checks logo path validity via Storage.

---

### 6.5 `getAdditionalDetails()`

* **Purpose:** Returns structured array of bank details for Admin Order View display.
* **Input Parameters:** None.
* **Return Type:** `array` (Structured key-value title/value pairs).
* **Exceptions Thrown:** None.
* **Nullability Rules:** Returns empty array if method DTO is null.
* **Side Effects:** Calls `DynamicBankTransferService::getByCode()`.

---

### 6.6 `isAvailable()`

* **Purpose:** Evaluates whether this specific bank transfer method is active and available for current cart channel/currency.
* **Input Parameters:** None.
* **Return Type:** `bool` (`true` if available, `false` otherwise).
* **Exceptions Thrown:** None.
* **Nullability Rules:** Non-nullable boolean.
* **Side Effects:** Calls `DynamicBankTransferService::getByCode()`, checks `$dto->isActive` and channel restrictions against `Cart::getCart()`.

---

## 7. DTO Specification: `DynamicBankTransferDTO`

**Namespace:** `Webkul\DynamicBankTransfer\DTOs\DynamicBankTransferDTO`

### 7.1 Class Properties & Nullability

```
class DynamicBankTransferDTO
{
    public int $id;                      // Non-nullable DB primary key
    public string $code;                 // Non-nullable unique code
    public bool $isActive;               // Non-nullable status flag
    public string $title;                // Non-nullable display title
    public ?string $description;         // Nullable description
    public string $bankName;             // Non-nullable bank name
    public string $accountHolderName;    // Non-nullable account owner
    public ?string $accountNumber;       // Nullable account number
    public string $iban;                 // Non-nullable IBAN
    public ?string $swiftCode;           // Nullable BIC/SWIFT
    public ?string $transferInstructions;// Nullable instructions
    public ?string $logoPath;            // Nullable storage path
    public ?string $logoUrl;             // Nullable full HTTP URL
    public bool $generateInvoice;        // Non-nullable auto-invoice flag
    public string $invoiceStatus;        // Non-nullable target invoice status
    public string $orderStatus;          // Non-nullable target order status
    public int $sortOrder;               // Non-nullable sort weight
    public ?array $channelIds;           // Nullable array of channel IDs
}
```

### 7.2 Instantiation & Factory Contract

* **Method:** `DynamicBankTransferDTO::fromArray(array $attributes): self`
* **Method:** `DynamicBankTransferDTO::fromModel(DynamicBankTransfer $model): self`
* **Method:** `DynamicBankTransferDTO::toArray(): array`

---

## 8. Event Listener Contracts

### 8.1 `OrderPaymentSnapshotListener`

* **Target Event:** `checkout.order.save.before`
* **Method:** `handle(array &$data): void`
* **Behavior:** Checks `$data['payment']['method']`. If code starts with `dynamic_bank_transfer_`, calls `DynamicBankTransferService::buildOrderSnapshot($code)` and assigns output to `$data['payment']['additional']`.
* **Exceptions Thrown:** `Webkul\DynamicBankTransfer\Exceptions\OrderSnapshotException` if snapshot build fails.
* **Side Effects:** Mutates order creation `$data` array in-place before DB persist.

---

### 8.2 `AutoInvoiceListener`

* **Target Event:** `checkout.order.save.after`
* **Method:** `handle(Order $order): void`
* **Behavior:** Checks `$order->payment->method`. If code starts with `dynamic_bank_transfer_`, calls `DynamicBankTransferService::getByCode()`. If `$dto->generateInvoice` is true, calls `InvoiceRepository::create()`.
* **Exceptions Thrown:** Catches and logs `Exception` to Laravel log without breaking order save completion.
* **Side Effects:** Inserts `invoices` and `invoice_items` DB rows. Updates order status.

---

## 9. Form Request & Validation Rule Contracts

### 9.1 `IbanValidationRule`

* **Namespace:** `Webkul\DynamicBankTransfer\Http\Rules\IbanValidationRule`
* **Implements:** `Illuminate\Contracts\Validation\ValidationRule`
* **Method:** `validate(string $attribute, mixed $value, Closure $fail): void`
* **Behavior:** Validates string against regex format `^[A-Z]{2}[0-9]{2}[A-Z0-9]{11,30}$` and performs IBAN MOD-97 checksum calculation.

---

## 10. Bagisto Extension Points Map & Resilience Matrix

The following matrix documents every point where the `DynamicBankTransfer` package integrates with Bagisto 2.4.x, along with its fallback strategy in case Bagisto modifies core contracts in future releases:

| Extension Point | Type | Objective | Bagisto Contract Used | Fallback Strategy on Core Change |
|:---|:---|:---|:---|:---|
| `config('payment_methods')` | Config Array | Register dynamic payment methods | `Illuminate\Support\Facades\Config` | If Bagisto converts to DB payment registry, implement a Custom Provider adapter mapping to `payment_methods` table. |
| `config('core')` | Config Array | Expose system config fields for Admin | `Webkul\Core\SystemConfig` | Override `DynamicBankTransferMethod::getConfigData()` to read strictly from DTO service. |
| `checkout.order.save.before` | Event | Inject v1.1 Complete Immutable Snapshot | `Illuminate\Support\Facades\Event` | Intercept via custom `OrderRepository` decorator or Eloquent Model `creating` hook on `OrderPayment`. |
| `checkout.order.save.after` | Event | Auto-generate invoice based on DTO rules | `Illuminate\Support\Facades\Event` | Bind to `sales.order.save.after` or Order Observer `created` event. |
| `bagisto.admin.sales.order.payment-method.after` | View Render Event | Render historical bank snapshot in Order View | Bagisto Blade View Event | Override Admin Order View partial `sales/orders/view.blade.php` via package view namespace priority. |
| `Payment::getPaymentMethods()` | Facade | Storefront payment method listing | `Webkul\Payment\Facades\Payment` | Intercept Checkout API response via Middleware or API Controller Decorator. |
| `menu.admin` | Config Array | Register Admin Sidebar Navigation | `Webkul\Admin\Config\menu.php` | Inject menu item dynamically via `Event::listen('bagisto.admin.menu.init')`. |
| `acl` | Config Array | Register Access Control Permissions | `Webkul\Admin\Config\acl.php` | Bind permissions via custom Bouncer gate policy in Service Provider. |

---

## 11. Architectural Clarification & Future Evolution Record

This section records formal engineering decisions for potential future evolution scenarios:

### Q1: Will `Payment::getPaymentMethods()` remain Bagisto's official contract in future versions?
* **Architectural Decision:** Yes. `Payment::getPaymentMethods()` is Bagisto's fundamental payment contract used across all storefront themes and APIs. However, by abstracting registration inside `DynamicPaymentRegistry`, if Bagisto alters this contract in v3.0, only `DynamicPaymentRegistry` will require an adapter update; all domain services and repositories remain untouched.

### Q2: What happens if Bagisto's One-Page Checkout UI is replaced by a multi-step or Headless Checkout?
* **Architectural Decision:** Dynamic bank transfer methods register as native top-level payment methods. Headless REST/GraphQL APIs query `Payment::getPaymentMethods()`, which automatically includes dynamic bank transfer objects. Headless checkouts consume the method code (`dynamic_bank_transfer_7`) identically to native checkouts.

### Q3: How will external API clients (Mobile Apps / Third-Party Integrations) consume dynamic bank details?
* **Architectural Decision:** The Checkout Summary API (`CartResource`) returns `payment_method` code. When order is completed, the Order Detail API (`OrderResource`) exposes `payment.additional` JSON. External clients render bank details directly from `payment.additional`.

### Q4: Should Import / Export functionality be supported for Bank Transfer records?
* **Architectural Decision:** The architecture supports import/export via Bagisto's DataTransfer package (`packages/Webkul/DataTransfer`). A dedicated `DynamicBankTransferImporter` and `Exporter` can be added in a future phase by creating an importer adapter that calls `DynamicBankTransferService::createMethod()`.

---

**End of Interface Contracts Specification v1.0**  
*This document constitutes the official, binding interface contract blueprint. Implementation shall strictly adhere to all method signatures, types, exceptions, and extension points defined herein.*
