# Offline Payments Implementation Specification
### Version 2.0 (Approved Reference)  
**Status:** Frozen  
**Derived From:** Offline Payments Architecture Specification v2.0  

---

> ## ❝ One Method, Many Accounts ❞

---

## 1. Executive Summary & Purpose
This document translates the approved **Offline Payments Architecture Specification v2.0** into actionable technical and code layouts. It defines exact PHP class names, directory mappings, database tables, validation requirements, and integration hooks for HIGEST (Single Merchant Dropshipping). 

Every developer must follow this specification strictly to prevent architectural drift or the leakage of business logic into view/checkout controllers.

---

## 2. Architectural Boundaries & Responsibility Alignment

```
[Storefront Checkout]
       ↓
[OfflinePayments (Payment Method Class)]
       ↓
[OfflinePaymentAccountResolver] (ADR-004)
       ↓
[OfflinePaymentAccountRepository]
       ↓
[Database (offline_payment_accounts)]
```

* **OfflinePayments (Payment Method):** Governs checkout visibility, localized global title/instructions, and post-checkout workflows (auto-invoicing, status triggers).
* **OfflinePaymentAccountResolver:** Decouples checkout from query parameters. Holds business criteria for selecting active accounts by channel and currency.
* **OfflinePaymentAccount (Model):** Represents the data payload. Employs type-based validation to match BANK, WALLET, or PAYMENT_POINT destinations.
* **PaymentSnapshot:** Encapsulates the historical payment account data inside the placed order to maintain transactional integrity.

---

## 3. Database Schema Specification

### 3.1. Unified Table: `offline_payment_accounts`
All account types share a single flat table. No type-specific tables are allowed.

| Column | Type | Nullable | Description / Reference |
| :--- | :--- | :--- | :--- |
| `id` | `bigint` | No | Primary Key |
| `code` | `string` | No | Unique system code (e.g., `offline_acc_123`) |
| `type` | `string` | No | Account type (`BANK`, `WALLET`, `PAYMENT_POINT`) |
| `currency_id` | `unsignedBigInteger`| No | Foreign key referencing Bagisto `currencies.id` |
| `is_active` | `boolean` | No | Status flag (default `true`) |
| `display_name` | `string` | No | Administrative name for identification |
| `recipient_name` | `string` | No | Full legal recipient/beneficiary name |
| `account_identifier`| `string` | No | Account number, Wallet mobile number, or Remittance ID |
| `provider_name` | `string` | No | Bank Name, Wallet Provider (e.g., Yalla), Exchange (e.g., Al-Najm) |
| `logo_path` | `string` | Yes | Path to custom bank/wallet provider logo |
| `transfer_instructions` | `text` | Yes | Specific instructions displayed to client during Checkout |
| `channel_ids` | `json` | No | Array of channel IDs where the account is active |
| `sort_order` | `integer` | No | Ordering priority (default `0`) |
| `created_at` | `timestamp` | Yes | Standard timestamp |
| `updated_at` | `timestamp` | Yes | Standard timestamp |

---

## 4. Domain & Service Layer Specification

### 4.1. Account Constants (Value Types)
To prevent string literal duplication, the account types are registered as constants on the Model:

```php
namespace Webkul\OfflinePayments\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\OfflinePayments\Contracts\OfflinePaymentAccount as OfflinePaymentAccountContract;

class OfflinePaymentAccount extends Model implements OfflinePaymentAccountContract
{
    public const TYPE_BANK          = 'BANK';
    public const TYPE_WALLET        = 'WALLET';
    public const TYPE_PAYMENT_POINT = 'PAYMENT_POINT';
}
```

### 4.2. The Resolver: `OfflinePaymentAccountResolver`
Implementing **ADR-004 (Business Logic Isolation)**, this domain service encapsulates all filtration rules. The Repository is strictly a data-access layer and must not contain any filtering business rules.

`OfflinePaymentAccountResolver` serves as the central hub for future extension policies (availability windows, customer group restrictions, payment limits, and minimum rules).

* **Design Note for Future Scale:** If the selection criteria grow complex, rules can be refactored into composed specifications (e.g., `ActiveSpecification`, `CurrencySpecification`, `ChannelSpecification`) using the **Specification Pattern** inside this Resolver layer.

```php
namespace Webkul\OfflinePayments\Services;

use Webkul\OfflinePayments\Repositories\OfflinePaymentAccountRepository;
use Webkul\Checkout\Models\Cart;

class OfflinePaymentAccountResolver
{
    public function __construct(
        protected OfflinePaymentAccountRepository $accountRepository
    ) {}

    /**
     * Resolves all active accounts matching cart criteria.
     */
    public function resolveAvailableAccounts(Cart $cart): \Illuminate\Support\Collection
    {
        $channelId = $cart->channel_id;
        $currencyId = $cart->currency_id; // Reference to configured currency

        return $this->accountRepository->scopeQuery(function($query) use ($channelId, $currencyId) {
            return $query->where('is_active', true)
                         ->where('currency_id', $currencyId)
                         ->whereJsonContains('channel_ids', $channelId)
                         ->orderBy('sort_order', 'asc');
        })->get();
    }
}
```

---

## 5. Checkout UX & Integration Flow

### 5.1. The Gateway Method Class
The payment class `Webkul\OfflinePayments\Payment\OfflinePayments` receives the resolver dependency via Constructor Dependency Injection to keep bindings explicit and easily testable:

```php
namespace Webkul\OfflinePayments\Payment;

use Webkul\Payment\Payment\Payment;
use Webkul\OfflinePayments\Services\OfflinePaymentAccountResolver;

class OfflinePayments extends Payment
{
    protected $code = 'offline_payments';

    public function __construct(
        protected OfflinePaymentAccountResolver $accountResolver
    ) {
        parent::__construct();
    }

    public function isAvailable(): bool
    {
        if (! parent::isAvailable()) {
            return false;
        }

        $cart = $this->getCart();
        return $this->accountResolver->resolveAvailableAccounts($cart)->isNotEmpty();
    }
}
```

### 5.2. Checkout UX Sequence & Payload
1. The customer selects the main payment option: **Offline Payments** (التحويل اليدوي).
2. The UI queries available accounts resolved for the active cart.
3. The checkout page renders a sub-selector interface.
4. Once selected, details (instructions, recipient name, provider) are displayed.
5. On order placement, the payload contains:
   `payment[selected_offline_account_id] = {id}`
   This ID is captured and written to the Cart Payment's `additional` field by the payment controller *before* checkout completion.

---

## 6. Admin Panel UI & Dynamic Validation

### 6.1. UI conditional presentation
The account form displays fields dynamically based on the selected type dropdown (`BANK`, `WALLET`, `PAYMENT_POINT`).

```
[Select Account Type]
  ├─ BANK:          Show SWIFT, IBAN, Bank Name, Account Holder, Account Number.
  ├─ WALLET:        Show Wallet Provider (e.g. Yalla Pay), Holder Name, Mobile Number. (Hide SWIFT/IBAN).
  └─ PAYMENT_POINT: Show Remittance Network Name, Recipient Name. (Hide SWIFT/IBAN/Account Number).
```

### 6.2. Validation Request: `OfflinePaymentAccountRequest`
Dynamic server-side validation rules are applied based on type input using model constants:

```php
namespace Webkul\OfflinePayments\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\OfflinePayments\Models\OfflinePaymentAccount;

class OfflinePaymentAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $types = implode(',', [
            OfflinePaymentAccount::TYPE_BANK,
            OfflinePaymentAccount::TYPE_WALLET,
            OfflinePaymentAccount::TYPE_PAYMENT_POINT
        ]);

        $baseRules = [
            'type'                 => 'required|in:' . $types,
            'currency_id'          => 'required|exists:currencies,id',
            'display_name'         => 'required|string|max:255',
            'recipient_name'       => 'required|string|max:255',
            'account_identifier'   => 'required|string|max:255',
            'provider_name'        => 'required|string|max:255',
            'channel_ids'          => 'required|array',
            'channel_ids.*'        => 'exists:channels,id',
            'logo'                 => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
            'transfer_instructions'=> 'nullable|string',
        ];

        // Conditional validation using class constants
        if ($this->input('type') === OfflinePaymentAccount::TYPE_BANK) {
            $baseRules['swift_code'] = 'nullable|string|max:11';
            $baseRules['iban']       = 'nullable|string|max:34';
        }

        return $baseRules;
    }
}
```

---

## 7. Historical Integrity: Snapshot Mechanism
To ensure absolute accounting history safety, details are frozen in the DB on order checkout. The snapshot is fully self-contained (capturing currency metadata directly) so it does not rely on external currency relations.

### 7.1. Event Listener: `SavePaymentSnapshot`

```php
namespace Webkul\OfflinePayments\Listeners;

use Webkul\Sales\Models\Order;
use Webkul\OfflinePayments\Repositories\OfflinePaymentAccountRepository;

class SavePaymentSnapshot
{
    public function __construct(
        protected OfflinePaymentAccountRepository $accountRepository
    ) {}

    public function handle(Order $order): void
    {
        $orderPayment = $order->payment;

        if ($orderPayment->method !== 'offline_payments') {
            return;
        }

        // Selected account ID is read from payment metadata, NOT request()
        $accountId = $orderPayment->additional['selected_offline_account_id'] ?? null;

        if (! $accountId) {
            logger()->error("Offline payment execution failed: Missing selected_offline_account_id on order {$order->id}");
            return;
        }

        $account = $this->accountRepository->find($accountId);

        // Compile immutable snapshot with fully self-contained currency info
        $snapshot = [
            'type'               => $account->type,
            'display_name'       => $account->display_name,
            'currency_code'      => $account->currency->code, // E.g., USD
            'currency_name'      => $account->currency->name, // E.g., US Dollar
            'provider_name'      => $account->provider_name,
            'recipient_name'     => $account->recipient_name,
            'account_identifier' => $account->account_identifier,
            'instructions'       => $account->transfer_instructions,
            'logo_path'          => $account->logo_path,
        ];

        // Append snapshot data and save
        $additional = $orderPayment->additional ?? [];
        $additional['offline_payment_snapshot'] = $snapshot;

        $orderPayment->additional = $additional;
        $orderPayment->save();
    }
}
```

---

## 8. Technical Package Layout
The folder structure is organized cleanly to align with Bagisto package standards:

```
packages/Webkul/OfflinePayments/src/
├── Config/
│   ├── acl.php
│   ├── admin-menu.php
│   └── payment_methods.php
├── Contracts/
│   └── OfflinePaymentAccount.php
├── Database/
│   ├── Migrations/
│   │   └── 2026_07_31_000001_create_offline_payment_accounts_table.php
│   └── Seeders/
├── DataGrids/
│   └── OfflinePaymentAccountDataGrid.php
├── Http/
│   ├── Controllers/
│   │   └── Admin/
│   │       └── OfflinePaymentAccountController.php
│   ├── Requests/
│   │   └── OfflinePaymentAccountRequest.php
│   └── Middleware/
├── Models/
│   └── OfflinePaymentAccount.php
├── Payment/
│   └── OfflinePayments.php      # Base payment method class
├── Providers/
│   ├── OfflinePaymentsServiceProvider.php
│   └── EventServiceProvider.php # Event maps for snapshotting
├── Repositories/
│   └── OfflinePaymentAccountRepository.php
├── Services/
│   └── OfflinePaymentAccountResolver.php # Business selector service
├── Resources/
│   ├── lang/
│   └── views/
│       ├── admin/
│       │   └── accounts/
│       │       ├── create.blade.php
│       │       └── edit.blade.php
│       └── shop/
│           └── checkout/
│               └── selector.blade.php
└── Routes/
    └── admin-routes.php
```

---

## 9. QA & Verification Protocol
1. **Dynamic Form Test:** Select BANK, WALLET, and PAYMENT_POINT in admin account form and verify fields are conditionally displayed without page refresh.
2. **Dynamic Validation Test:** Post a WALLET account form missing the IBAN and verify validation passes. Post a BANK account and verify standard rules.
3. **Currency Filtering Test:** Set Cart currency to USD. Ensure only USD payment accounts are retrieved by `OfflinePaymentAccountResolver` and rendered at checkout.
4. **Historical Snapshot Test:** Complete order checkout, modify/delete the payment account record in admin, and verify that the order detail page in admin still renders the accurate, old account details from the payment snapshot database entry.
5. **No Matching Accounts Test:** Set cart currency to YER and verify that if no accounts exist for YER, the "Offline Payments" method is completely hidden on the checkout screen.
6. **Invalid Selection Test:** Post a checkout request containing an invalid/non-existent account ID, and verify the checkout returns validation errors.
7. **Deactivation Race Condition Test:** Access checkout page, deactivate an account in the admin panel, complete checkout with that account, and verify validation intercepts the request and blocks the transaction since the account is no longer active.
8. **Snapshot Integrity Verification:** Place an order, verify the snapshot exists inside `order_payment.additional`. Delete the original account from the DataGrid, and confirm the admin order view displays the captured account details without errors.
