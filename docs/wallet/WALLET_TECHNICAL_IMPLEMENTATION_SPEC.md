# HIGEST Wallet Technical Implementation Specification v1.0

> **المرجع:** WALLET_DOMAIN_SPECIFICATION.md + WALLET_IMPLEMENTATION_READINESS_AUDIT.md
> **نوع الوثيقة:** Technical Implementation Specification — مرجع المطوّر
> **الإصدار:** 1.0
> **تاريخ الإصدار:** 2026-08-03
> **الحالة:** معتمد — جاهز للتطوير

---

## Table of Contents

1. [Resolved Decisions](#1-resolved-decisions)
2. [Wallet Domain Model](#2-wallet-domain-model)
3. [WalletTransaction Types](#3-wallettransaction-types)
4. [Wallet Balance Lifecycle](#4-wallet-balance-lifecycle)
5. [Withdrawal State Machine](#5-withdrawal-state-machine)
6. [Top-Up Lifecycle](#6-top-up-lifecycle)
7. [Checkout Integration](#7-checkout-integration)
8. [Admin Operations](#8-admin-operations)
9. [Customer Portal](#9-customer-portal)
10. [Financial Rules](#10-financial-rules)
11. [Package Structure](#11-package-structure)

---

## 1. Resolved Decisions

القرارات التالية محسومة نهائياً ومبنية عليها هذه الوثيقة:

| القرار | النتيجة |
|---|---|
| بنية الـ Package | مستقل في `packages/Webkul/Wallet/` |
| مصدر الحقيقة المالية | `wallet_transactions` (Projection) |
| إنشاء المحفظة | Proactive عند `customer.create.after` |
| Refund → Gateway | ❌ مُعطَّل — كل Refunds للمحفظة بلا استثناء |
| الدفع الجزئي في V1 | ❌ غير مدعوم — دفع كامل من المحفظة أو لا |
| رصيد غير منتهٍ | ✅ لا يوجد expiry_date |
| تحويل بين المحافظ | ❌ ممنوع |
| الرصيد السالب | ❌ ممنوع — available_balance >= 0 دائماً |

---

## 2. Wallet Domain Model

### 2.1 WalletAccount

**الغرض:** يمثّل المحفظة الرئيسية للعميل. حاوية الأرصدة.

**Attributes:**

| الحقل | النوع | القيود | الوصف |
|---|---|---|---|
| `id` | bigint unsigned | PK | المعرّف |
| `customer_id` | int unsigned | UNIQUE, FK | مالك المحفظة |
| `total_balance` | decimal(12,4) | DEFAULT 0 | الكلي |
| `available_balance` | decimal(12,4) | DEFAULT 0 | المتاح للاستخدام |
| `held_balance` | decimal(12,4) | DEFAULT 0 | المحجوز |
| `currency_code` | varchar(3) | NOT NULL | رمز العملة |
| `status` | varchar(20) | DEFAULT 'active' | active / suspended |
| `created_at` | timestamp | | |
| `updated_at` | timestamp | | |

**قاعدة ثابتة:**

```
total_balance = available_balance + held_balance (دائماً)
available_balance >= 0                          (دائماً)
held_balance >= 0                               (دائماً)
```

**Relations:**

```
BelongsTo:  Customer          (customer_id)
HasMany:    WalletTransaction  (wallet_id)
HasMany:    WalletTopUp        (wallet_id)
HasMany:    WalletWithdrawalRequest (wallet_id)
```

**Statuses:**

| الحالة | الوصف |
|---|---|
| `active` | المحفظة نشطة — الإيداع والدفع والسحب مسموح |
| `suspended` | المحفظة موقوفة — القراءة فقط — لا عمليات |

---

### 2.2 WalletTransaction

**الغرض:** السجل الثابت لكل حركة مالية. مصدر الحقيقة الوحيد.

**Attributes:**

| الحقل | النوع | القيود | الوصف |
|---|---|---|---|
| `id` | bigint unsigned | PK | المعرّف |
| `wallet_id` | bigint unsigned | FK, NOT NULL | المحفظة المرتبطة |
| `type` | varchar(30) | NOT NULL | نوع العملية (انظر §3) |
| `amount` | decimal(12,4) | NOT NULL | المبلغ — موجب دائماً |
| `running_balance` | decimal(12,4) | NOT NULL | available_balance بعد العملية |
| `description` | varchar(500) | NULLABLE | وصف مقروء |
| `reference_type` | varchar(100) | NULLABLE | Polymorphic type |
| `reference_id` | bigint unsigned | NULLABLE | Polymorphic id |
| `created_by_type` | varchar(100) | NULLABLE | من أنشأ العملية (Customer/Admin) |
| `created_by_id` | bigint unsigned | NULLABLE | معرّف المنشئ |
| `meta` | json | NULLABLE | بيانات إضافية |
| `created_at` | timestamp | NOT NULL | وقت الإنشاء |
| `updated_at` | timestamp | | (للتوافق فقط) |

**قاعدة ثابتة:**

```
لا يُعدَّل أي سجل بعد إنشائه.
لا يُحذف أي سجل.
الأخطاء تُصحَّح بإدخال سجل ADJUSTMENT عكسي.
```

**Relations:**

```
BelongsTo: WalletAccount   (wallet_id)
MorphTo:   reference       (reference_type + reference_id)
           ← Order | Refund | WalletTopUp | WalletWithdrawalRequest
```

---

### 2.3 WalletTopUp

**الغرض:** يتتبع طلب إيداع رصيد من بوابة خارجية.

**Attributes:**

| الحقل | النوع | القيود | الوصف |
|---|---|---|---|
| `id` | bigint unsigned | PK | المعرّف |
| `wallet_id` | bigint unsigned | FK, NOT NULL | المحفظة المستفيدة |
| `amount` | decimal(12,4) | NOT NULL | المبلغ |
| `currency_code` | varchar(3) | NOT NULL | العملة |
| `payment_method` | varchar(100) | NULLABLE | طريقة الدفع |
| `payment_transaction_id` | varchar(255) | NULLABLE, UNIQUE | معرّف البوابة |
| `status` | varchar(30) | DEFAULT 'pending' | انظر State Machine |
| `admin_user_id` | int unsigned | FK → admins.id, NULLABLE | صاحب القرار |
| `admin_notes` | text | NULLABLE | ملاحظات الإدارة |
| `approved_at` | timestamp | NULLABLE | وقت القرار |
| `meta` | json | NULLABLE | بيانات البوابة |
| `created_at` | timestamp | | |
| `updated_at` | timestamp | | |

**State Machine:**

```
PENDING ──→ PAYMENT_RECEIVED ──→ APPROVED  ✅ (Wallet credited)
                              └──→ REJECTED  ❌ (Manual external refund)
PENDING ──→ EXPIRED ⏱ (لم يكتمل الدفع)
```

**الانتقالات المسموحة:**

| من | إلى | من يقوم بها |
|---|---|---|
| `pending` | `payment_received` | System (Webhook/Callback) |
| `pending` | `expired` | System (بعد X ساعة) |
| `payment_received` | `approved` | Admin |
| `payment_received` | `rejected` | Admin |

---

### 2.4 WalletWithdrawalRequest

**الغرض:** يتتبع طلب سحب رصيد لحساب بنكي خارجي.

**Attributes:**

| الحقل | النوع | القيود | الوصف |
|---|---|---|---|
| `id` | bigint unsigned | PK | المعرّف |
| `wallet_id` | bigint unsigned | FK, NOT NULL | المحفظة المصدر |
| `amount` | decimal(12,4) | NOT NULL | المبلغ |
| `currency_code` | varchar(3) | NOT NULL | العملة |
| `status` | varchar(30) | DEFAULT 'pending' | انظر State Machine |
| `bank_details` | json (encrypted) | NOT NULL | بيانات الحساب البنكي |
| `admin_user_id` | int unsigned | FK → admins.id, NULLABLE | المنفِّذ |
| `bank_transaction_reference` | varchar(255) | NULLABLE | رقم العملية البنكية |
| `transferred_at` | timestamp | NULLABLE | تاريخ التحويل الفعلي |
| `admin_notes` | text | NULLABLE | ملاحظات التنفيذ |
| `rejected_at` | timestamp | NULLABLE | وقت الرفض |
| `rejection_reason` | varchar(500) | NULLABLE | سبب الرفض |
| `created_at` | timestamp | | |
| `updated_at` | timestamp | | |

**State Machine:**

```
PENDING ──→ COMPLETED ✅ (Admin executes bank transfer)
        └──→ REJECTED  ❌ (Hold released back to available)
```

**الانتقالات المسموحة:**

| من | إلى | من يقوم بها | يُنشئ Transaction |
|---|---|---|---|
| `pending` | `completed` | Admin | DEBIT_WITHDRAWAL |
| `pending` | `rejected` | Admin | RELEASE_HOLD |

---

### 2.5 WalletPayment (Payment Method Class)

**الغرض:** تسجيل المحفظة كـ Payment Method في Bagisto.

**يرث من:** `Webkul\Payment\Payment\Payment`

**المسؤوليات:**

```
getCode()          → 'wallet'
getTitle()         → 'HIGEST Wallet'
getRedirectUrl()   → null  (الدفع فوري، لا redirect)

isAvailable():
  ← config('active') = true
  ← auth()->guard('customer')->check()
  ← WalletAccount::forCustomer()->available_balance > 0
  ← order_grand_total <= available_balance  (V1: لا دفع جزئي)
```

**V1 Constraint:**

```
إذا كان available_balance < order_grand_total:
  → isAvailable() = false
  → المحفظة لا تظهر كخيار دفع
```

---

## 3. WalletTransaction Types

### 3.1 جدول الأنواع الكاملة

| النوع (Code) | التأثير على available | التأثير على held | قابل للعكس؟ | من يُنشئه؟ |
|---|---|---|---|---|
| `CREDIT_TOPUP` | +amount | — | لا | System (بعد Admin Approval) |
| `CREDIT_REFUND` | +amount | — | لا | System (Refund Listener) |
| `CREDIT_CANCEL` | +amount | — | لا | System (Order Cancel Listener) |
| `DEBIT_PAYMENT` | -amount | — | لا* | System (Checkout) |
| `HOLD_PAYMENT` | -amount | +amount | نعم (→ RELEASE) | System (غير مستخدم في V1) |
| `DEBIT_WITHDRAWAL` | — | -amount | لا | System (بعد Admin Approval) |
| `HOLD_WITHDRAWAL` | -amount | +amount | نعم (→ RELEASE) | System (عند إنشاء طلب السحب) |
| `RELEASE_HOLD` | +amount | -amount | لا | System (عند رفض السحب / فشل الدفع) |
| `ADJUSTMENT` | ±amount | — | نعم | Admin only |
| `SUSPENSION_FREEZE` | -amount | +amount | نعم (→ RELEASE) | System (عند تجميد المحفظة) |

*ملاحظة: DEBIT_PAYMENT غير قابل للعكس مباشرة — يُعكس عبر CREDIT_CANCEL أو CREDIT_REFUND.

### 3.2 قواعد كل نوع

**CREDIT_TOPUP:**

```
يُنشأ عند: Admin → Approve WalletTopUp
المبلغ:    topup.amount
المصدر:    reference_type = WalletTopUp, reference_id = topup.id
الأثر:     available_balance += amount, total_balance += amount
العكس:     مستحيل — إن كان هناك خطأ يُنشأ ADJUSTMENT سالب
```

**CREDIT_REFUND:**

```
يُنشأ عند: sales.refund.save.after
المبلغ:    refund.base_grand_total
المصدر:    reference_type = Refund, reference_id = refund.id
الأثر:     available_balance += amount, total_balance += amount
العكس:     مستحيل (الـ Refund نفسه غير قابل للعكس)
الشرط:     يُنشأ حتى لو كان طريقة الدفع الأصلية ليست Wallet
```

**CREDIT_CANCEL:**

```
يُنشأ عند: sales.order.cancel.after (إذا كان الطلب مدفوعاً بالمحفظة)
المبلغ:    المبلغ المخصوم فعلاً من المحفظة (من DEBIT_PAYMENT السابق)
المصدر:    reference_type = Order, reference_id = order.id
الأثر:     available_balance += amount, total_balance += amount
الشرط:     فقط إذا كان order.payment.method = 'wallet'
```

**DEBIT_PAYMENT:**

```
يُنشأ عند: checkout.order.save.after (إذا طريقة الدفع = wallet)
المبلغ:    order.base_grand_total (أو صفر إذا كانت قيمة الطلب صفراً)
المصدر:    reference_type = Order, reference_id = order.id
الأثر:     available_balance -= amount, total_balance -= amount
الشرط:     available_balance >= amount (مُتحقَّق قبل الإنشاء)
```

**HOLD_WITHDRAWAL:**

```
يُنشأ عند: إنشاء WalletWithdrawalRequest
المبلغ:    withdrawal.amount
المصدر:    reference_type = WalletWithdrawalRequest, reference_id = withdrawal.id
الأثر:     available_balance -= amount, held_balance += amount
الشرط:     available_balance >= amount
```

**DEBIT_WITHDRAWAL:**

```
يُنشأ عند: Admin → Complete WalletWithdrawalRequest
المبلغ:    withdrawal.amount
المصدر:    reference_type = WalletWithdrawalRequest, reference_id = withdrawal.id
الأثر:     held_balance -= amount, total_balance -= amount
```

**RELEASE_HOLD:**

```
يُنشأ عند: Admin → Reject WalletWithdrawalRequest
المبلغ:    withdrawal.amount
المصدر:    reference_type = WalletWithdrawalRequest, reference_id = withdrawal.id
الأثر:     held_balance -= amount, available_balance += amount
```

**ADJUSTMENT:**

```
يُنشأ عند: Admin تصحيح يدوي
المبلغ:    أي قيمة موجبة
الأثر:     available_balance += amount (credit) أو -= amount (debit)
الصلاحية: wallet.adjust فقط
السجل:     يُسجَّل created_by_type = Admin, created_by_id = admin.id
```

---

## 4. Wallet Balance Lifecycle

### 4.1 الرصيد كـ Projection

```
الرصيد الحقيقي لا يُحسَب من wallet_accounts.available_balance وحدها.
wallet_accounts.available_balance هي Cache محسوب للأداء.
المصدر الحقيقي = SUM(wallet_transactions) بالنوع الصحيح.

في حالة خلاف بين الاثنين:
  wallet_transactions هو الأصح دائماً.
```

### 4.2 إضافة رصيد (Credit)

**سيناريو: Refund يصل للمحفظة**

```
[input] refund.base_grand_total = 75.00

DB::transaction():
  wallet = WalletAccount::lockForUpdate()->find(wallet_id)
  
  WalletTransaction::create([
    type            => CREDIT_REFUND,
    amount          => 75.0000,
    running_balance => wallet.available_balance + 75.0000,
    reference_type  => Refund::class,
    reference_id    => refund.id,
    description     => 'Refund for Order #ORD-2026-0891',
  ])
  
  wallet->increment('available_balance', 75.0000)
  wallet->increment('total_balance', 75.0000)
  
COMMIT

[output]
  before: available=50.00 | held=0.00 | total=50.00
  after:  available=125.00 | held=0.00 | total=125.00
```

### 4.3 الدفع (Debit)

**سيناريو: دفع طلب بالمحفظة**

```
[input] order.base_grand_total = 150.00

[check قبل إنشاء الطلب]
  wallet.available_balance >= 150.00? → نعم
  
[داخل DB Transaction إنشاء الطلب]
  wallet = WalletAccount::lockForUpdate()->find(wallet_id)
  
  if wallet.available_balance < 150.00:
    throw InsufficientWalletBalanceException
  
  WalletTransaction::create([
    type            => DEBIT_PAYMENT,
    amount          => 150.0000,
    running_balance => wallet.available_balance - 150.0000,
    reference_type  => Order::class,
    reference_id    => order.id,
    description     => 'Payment for Order #ORD-2026-1105',
  ])
  
  wallet->decrement('available_balance', 150.0000)
  wallet->decrement('total_balance', 150.0000)

[output]
  before: available=200.00 | held=0.00 | total=200.00
  after:  available=50.00 | held=0.00 | total=50.00
```

### 4.4 طلب قيمته صفر

```
[input] order.base_grand_total = 0.00

[check]
  لا حاجة لـ lockForUpdate
  لا يُنشأ WalletTransaction
  
[action]
  الطلب يُنشأ ويُعامَل كمدفوع
  order.payment.method = 'wallet'
  لا خصم فعلي من المحفظة
```

---

## 5. Withdrawal State Machine

### 5.1 الحالات والانتقالات

```
                    ┌─────────────────────────────────┐
                    │         PENDING                 │
                    │  (Hold created immediately)     │
                    └─────────────┬───────────────────┘
                                  │
                    ┌─────────────┴───────────────────┐
                    │                                 │
                    ▼                                 ▼
          ┌─────────────────┐               ┌────────────────┐
          │   COMPLETED ✅  │               │   REJECTED ❌   │
          │ Admin records:  │               │ Hold released  │
          │ - reference     │               │ back to        │
          │ - transferred_at│               │ available      │
          │ - admin_notes   │               └────────────────┘
          └─────────────────┘
```

**الانتقالات المسموحة فقط:**

| من | إلى | الشرط | الأثر على المحفظة |
|---|---|---|---|
| `pending` | `completed` | Admin يُسجِّل البيانات البنكية | DEBIT_WITHDRAWAL: held -= amount, total -= amount |
| `pending` | `rejected` | Admin يُدخل سبب الرفض | RELEASE_HOLD: held -= amount, available += amount |

**الانتقالات الممنوعة:**

```
completed → أي حالة أخرى  (لا يمكن التراجع)
rejected  → أي حالة أخرى  (لا يمكن إعادة الفتح)
```

### 5.2 بيانات الحساب البنكي (bank_details JSON)

```json
{
  "beneficiary_name": "اسم المستفيد",
  "bank_name": "اسم البنك",
  "iban": "SA0380000000608010167519",
  "account_number": "608010167519",
  "swift_code": "ARNBSARI"
}
```

التخزين: `encrypted` cast في قاعدة البيانات.
العرض: IBAN مُقنَّع في واجهة العميل: `SA********************7519`

---

## 6. Top-Up Lifecycle

### 6.1 التدفق الكامل

```
[1] العميل يفتح صفحة إيداع الرصيد
         │  input: amount + payment_method
         ↓
[2] WalletTopUpController::initiate()
    → يتحقق من: amount > 0, wallet.status = active
    → يُنشئ WalletTopUp (status = PENDING)
    → يُعيد URL للبوابة
         │
         ↓
[3] العميل يُكمل الدفع في بوابة الدفع
         │
         ├──→ [فشل / إلغاء]:
         │      WalletTopUp يبقى PENDING أو يُعيَّن EXPIRED
         │
         ↓
[4] بوابة الدفع → Callback/Webhook
    WalletTopUpController::callback()
    → يُتحقق من صحة الـ payment_transaction_id
    → WalletTopUp.status = PAYMENT_RECEIVED
    → WalletTopUp.payment_transaction_id = البوابة ID
         │
         ↓
[5] Admin يرى الطلب في Dashboard
         │
         ├──→ [REJECTED]:
         │      WalletTopUp.status = REJECTED
         │      الإدارة تُعيد المال خارجياً (خارج النظام)
         │
         ↓
[6] [APPROVED]:
    WalletTopUp.status = APPROVED
    WalletTopUp.admin_user_id = auth()->id()
    WalletTopUp.approved_at = now()
    
    DB::transaction():
      wallet = WalletAccount::lockForUpdate()->find(wallet_id)
      
      WalletTransaction::create([
        type           => CREDIT_TOPUP,
        amount         => topup.amount,
        running_balance => wallet.available_balance + topup.amount,
        reference_type => WalletTopUp::class,
        reference_id   => topup.id,
      ])
      
      wallet->increment('available_balance', topup.amount)
      wallet->increment('total_balance', topup.amount)
    
COMMIT
         │
         ↓
[7] إشعار للعميل (Email + Database Notification)
```

### 6.2 ما لا يفعله Top-Up

```
❌ لا يمر عبر Cart
❌ لا يمر عبر Checkout
❌ لا ينشئ Order
❌ لا يُضيف رصيداً قبل موافقة الإدارة
```

---

## 7. Checkout Integration

### 7.1 قرار V1: لا دفع جزئي

```
DECISION (V1): NO PARTIAL PAYMENT

المبرر:
  1. CartPayment علاقة HasOne (method واحدة فقط في النظام الحالي)
  2. تقليل التعقيد في V1
  3. يمكن إضافة الدفع الجزئي في V2

القاعدة:
  إذا available_balance >= order.grand_total → المحفظة تظهر كخيار دفع ✅
  إذا available_balance <  order.grand_total → المحفظة لا تظهر ❌
```

### 7.2 تدفق الدفع عبر المحفظة

```
[1] العميل في Checkout يرى "HIGEST Wallet"
    (يظهر فقط إذا كان الرصيد يغطي الطلب كاملاً)
         │
         ↓
[2] العميل يختار "HIGEST Wallet" + يضغط "Place Order"
         │
         ↓
[3] CheckoutController → Cart::placeOrder()
    → OrderRepository::create()
    → DB::beginTransaction()
    → Event::dispatch('checkout.order.save.before', [$data])
    → order = Order::create()
    → order->payment()->create(['method' => 'wallet'])
    → Event::dispatch('checkout.order.save.after', $order)
         │
         ↓
[4] Wallet Listener (يستمع على checkout.order.save.after):
    WalletPaymentListener::handle($order):
      if order.payment.method !== 'wallet': return
      
      wallet = WalletAccount::lockForUpdate()->forCustomer($order->customer_id)
      
      if wallet.available_balance < order.base_grand_total:
        throw InsufficientWalletBalanceException
      
      WalletTransaction::create([
        type           => DEBIT_PAYMENT,
        amount         => order.base_grand_total,
        running_balance => wallet.available_balance - order.base_grand_total,
        reference_type => Order::class,
        reference_id   => order.id,
      ])
      
      wallet->decrement('available_balance', order.base_grand_total)
      wallet->decrement('total_balance', order.base_grand_total)
         │
         ↓
[5] الطلب يكمل مساره الطبيعي (Invoice → Notification)
```

**ملاحظة:** WalletTransaction يُنشأ داخل نفس DB Transaction الخاص بإنشاء الطلب.
إذا فشل أي شيء → Rollback يشمل المحفظة والطلب معاً.

### 7.3 الدفع المحمي من Race Condition

```
السيناريو الخطر:
  نافذتان مفتوحتان → كلتاهما تقرأ available_balance = 100
  كلتاهما تحاول خصم 100
  
الحل المطبَّق:
  lockForUpdate() قبل أي قراءة تسبق خصماً
  
  DB::transaction():
    wallet = WalletAccount::lockForUpdate()->find(id)
    ← الآن أي query أخرى على نفس الصف تنتظر
    if wallet.available_balance < amount: throw exception
    [الخصم]
  COMMIT ← يرفع القفل
```

---

## 8. Admin Operations

### 8.1 صفحات الإدارة المطلوبة

| الصفحة | Route | DataGrid | Controller Method |
|---|---|---|---|
| **قائمة المحافظ** | `admin.wallet.accounts.index` | WalletAccountsDataGrid | `AccountController::index()` |
| **تفاصيل محفظة** | `admin.wallet.accounts.show` | WalletTransactionsDataGrid (مُفلتَر) | `AccountController::show()` |
| **طلبات الإيداع** | `admin.wallet.deposits.index` | WalletTopUpsDataGrid | `WalletController::deposits()` |
| **الموافقة على إيداع** | `admin.wallet.deposits.approve` | — | `WalletController::approveDeposit()` |
| **رفض إيداع** | `admin.wallet.deposits.reject` | — | `WalletController::rejectDeposit()` |
| **طلبات السحب** | `admin.wallet.withdrawals.index` | WalletWithdrawalsDataGrid | `WalletController::withdrawals()` |
| **تنفيذ سحب** | `admin.wallet.withdrawals.complete` | — | `WalletController::completeWithdrawal()` |
| **رفض سحب** | `admin.wallet.withdrawals.reject` | — | `WalletController::rejectWithdrawal()` |
| **التعديل اليدوي** | `admin.wallet.accounts.adjust` | — | `AccountController::adjust()` |
| **الإعدادات** | `admin.wallet.settings.index` | — | `WalletController::settings()` |

### 8.2 صلاحيات الإدارة (ACL Keys)

```
wallet                          → عرض القائمة الرئيسية
wallet.accounts                 → إدارة حسابات المحافظ
wallet.accounts.view            → عرض تفاصيل المحفظة
wallet.accounts.adjust          → تعديل يدوي على الرصيد (ADJUSTMENT)

wallet.deposits                 → قسم الإيداع
wallet.deposits.view            → عرض طلبات الإيداع
wallet.deposits.approve         → الموافقة على الإيداع
wallet.deposits.reject          → رفض الإيداع

wallet.withdrawals              → قسم السحب
wallet.withdrawals.view         → عرض طلبات السحب
wallet.withdrawals.process      → تنفيذ السحب وإدخال البيانات البنكية
wallet.withdrawals.reject       → رفض السحب

wallet.settings                 → إعدادات المحفظة
```

### 8.3 إعدادات المحفظة في system.php

```
Group: sales.wallet

sales.wallet.active                    → boolean  (تفعيل/تعطيل المحفظة كاملاً)
sales.wallet.enable_withdrawal         → boolean  (تفعيل/تعطيل السحب)
sales.wallet.enable_topup              → boolean  (تفعيل/تعطيل الإيداع)
sales.wallet.min_topup_amount          → decimal  (الحد الأدنى للإيداع)
sales.wallet.min_withdrawal_amount     → decimal  (الحد الأدنى للسحب)
sales.wallet.max_withdrawal_amount     → decimal  (الحد الأقصى للسحب — 0 = بلا حد)
```

### 8.4 DataGrid WalletTopUpsDataGrid — الأعمدة المطلوبة

```
id, customer_name, wallet_id, amount, currency_code,
payment_method, payment_transaction_id, status,
admin_name (nullable), approved_at, created_at
```

**الـ Actions:**
```
[View]                      ← لكل الحالات
[Approve] [Reject]          ← فقط إذا status = payment_received
```

### 8.5 DataGrid WalletWithdrawalsDataGrid — الأعمدة المطلوبة

```
id, customer_name, wallet_id, amount, currency_code,
status, bank_name (من bank_details JSON),
iban_masked, admin_name (nullable), created_at
```

**الـ Actions:**
```
[View]                                       ← لكل الحالات
[Complete (إدخال بيانات التحويل)] [Reject]  ← فقط إذا status = pending
```

---

## 9. Customer Portal

### 9.1 الصفحات المطلوبة

| الصفحة | Route | الوصف |
|---|---|---|
| **محفظتي** | `shop.customer.wallet.index` | الصفحة الرئيسية: الرصيد + آخر المعاملات |
| **سجل المعاملات** | `shop.customer.wallet.transactions` | DataTable كامل مع فلترة |
| **إيداع رصيد** | `shop.customer.wallet.topup` | نموذج الإيداع + اختيار البوابة |
| **Callback الإيداع** | `shop.customer.wallet.topup.callback` | استقبال رد البوابة |
| **طلب سحب** | `shop.customer.wallet.withdrawal.create` | نموذج السحب + بيانات البنك |
| **سجل السحوبات** | `shop.customer.wallet.withdrawal.index` | قائمة طلبات السحب + حالاتها |

### 9.2 صفحة "محفظتي" — المعلومات المعروضة

```
┌─────────────────────────────────────────────────────────┐
│  💳  HIGEST Wallet                                       │
│                                                         │
│  الرصيد المتاح              الرصيد المحجوز              │
│  SAR 1,250.00               SAR 200.00                 │
│                                                         │
│  [إيداع رصيد]  [طلب سحب]  [سجل المعاملات]             │
│                                                         │
│  ─────────────────────────────────────────────────      │
│  آخر المعاملات:                                         │
│  [تاريخ] [النوع] [المبلغ] [المصدر]                     │
└─────────────────────────────────────────────────────────┘
```

### 9.3 عرض المعاملات — الأعمدة

```
created_at  → تاريخ العملية
type        → نوع مُترجَم (إيداع / خصم / استرداد / ...)
description → وصف العملية
amount      → المبلغ (+/-) بلون مختلف
running_balance → الرصيد بعد العملية
```

### 9.4 نموذج السحب — الحقول

```
amount          → المبلغ المطلوب (>= الحد الأدنى, <= available_balance)
beneficiary_name → اسم المستفيد
bank_name        → اسم البنك
iban             → رقم الآيبان
account_number   → رقم الحساب (اختياري)
swift_code       → سويفت (اختياري)
```

### 9.5 صلاحيات العميل

```
يجب أن يكون العميل:
  ← مُسجَّل دخول (auth()->guard('customer')->check())
  ← غير موقوف (customer.status = 1)
  ← محفظته بحالة active (wallet.status = 'active')

لعرض خيار السحب:
  ← config('sales.wallet.enable_withdrawal') = true
  ← wallet.available_balance > config('sales.wallet.min_withdrawal_amount')
```

---

## 10. Financial Rules

### 10.1 القواعد المحسومة

```
[R-001] الرصيد لا ينتهي (لا يوجد expiry_date)

[R-002] لا يوجد تحويل بين المحافظ

[R-003] الرصيد السالب محظور:
         available_balance >= 0 في كل الأوقات
         يُطبَّق بـ lockForUpdate + check قبل كل خصم

[R-004] V1: لا دفع جزئي (Wallet فقط أو Gateway فقط)

[R-005] كل Refunds تذهب للمحفظة بغض النظر عن طريقة الدفع الأصلية

[R-006] رصيد Refund = رصيد Top-Up = نفس المعاملة (لا فرق في الاستخدام)

[R-007] رصيد Refund قابل للسحب فوراً (لا فترة انتظار)

[R-008] رصيد Top-Up قابل للسحب فور الموافقة عليه

[R-009] الرصيد المحجوز (hold) لا يُستخدَم في الدفع أو السحب

[R-010] طلبات القيمة الصفرية مع طريقة دفع wallet:
         تُنشئ الطلب كمدفوع، لا يُنشأ WalletTransaction
```

### 10.2 القواعد تحتاج حسماً (قبل Sprint 3)

```
[R-011] الحد الأدنى للإيداع:
         DECISION PENDING (D-005) — مقترح: قابل للضبط من الإعدادات

[R-012] الحد الأدنى للسحب:
         DECISION PENDING (D-005) — مقترح: قابل للضبط من الإعدادات

[R-013] الحد الأقصى للسحب:
         DECISION PENDING — مقترح: قابل للضبط (0 = بلا حد)

[R-014] رسوم السحب:
         DECISION PENDING — هل توجد رسوم ثابتة أو نسبة؟
         مقترح V1: لا رسوم
```

### 10.3 قاعدة السلامة المالية (المطبَّقة في كل مكان)

```
قبل أي خصم أو حجز:

DB::transaction(function() use ($walletId, $amount) {
    $wallet = WalletAccount::lockForUpdate()->findOrFail($walletId);
    
    if ($wallet->status !== 'active') {
        throw new WalletSuspendedException();
    }
    
    if ($wallet->available_balance < $amount) {
        throw new InsufficientWalletBalanceException();
    }
    
    // إنشاء Transaction + تحديث الرصيد
});
```

---

## 11. Package Structure

### 11.1 الهيكل الكامل للـ Package

```
packages/Webkul/Wallet/
├── src/
│   ├── Config/
│   │   ├── payment-methods.php     ← تسجيل WalletPayment
│   │   ├── acl.php                 ← ACL entries
│   │   ├── menu.php                ← Menu entries
│   │   └── system.php              ← Wallet settings
│   │
│   ├── Contracts/
│   │   ├── WalletAccount.php
│   │   ├── WalletTransaction.php
│   │   ├── WalletTopUp.php
│   │   └── WalletWithdrawalRequest.php
│   │
│   ├── Database/
│   │   └── Migrations/
│   │       ├── 2026_08_xx_000001_create_wallet_accounts_table.php
│   │       ├── 2026_08_xx_000002_create_wallet_transactions_table.php
│   │       ├── 2026_08_xx_000003_create_wallet_topups_table.php
│   │       └── 2026_08_xx_000004_create_wallet_withdrawal_requests_table.php
│   │
│   ├── Exceptions/
│   │   ├── InsufficientWalletBalanceException.php
│   │   ├── WalletSuspendedException.php
│   │   └── InvalidWalletTransitionException.php
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/
│   │   │   │   ├── WalletAccountController.php
│   │   │   │   ├── WalletTopUpController.php
│   │   │   │   └── WalletWithdrawalController.php
│   │   │   └── Shop/
│   │   │       ├── WalletController.php
│   │   │       ├── WalletTopUpController.php
│   │   │       └── WalletWithdrawalController.php
│   │   └── Requests/
│   │       ├── Admin/
│   │       │   ├── ApproveTopUpRequest.php
│   │       │   ├── RejectTopUpRequest.php
│   │       │   ├── CompleteWithdrawalRequest.php
│   │       │   └── RejectWithdrawalRequest.php
│   │       └── Shop/
│   │           ├── CreateTopUpRequest.php
│   │           └── CreateWithdrawalRequest.php
│   │
│   ├── Listeners/
│   │   ├── CreateWalletOnCustomerRegistered.php   ← customer.create.after
│   │   ├── DebitWalletOnOrderCreated.php          ← checkout.order.save.after
│   │   ├── CreditWalletOnOrderCanceled.php        ← sales.order.cancel.after
│   │   └── CreditWalletOnRefundCreated.php        ← sales.refund.save.after
│   │
│   ├── Models/
│   │   ├── WalletAccount.php
│   │   ├── WalletAccountProxy.php
│   │   ├── WalletTransaction.php
│   │   ├── WalletTransactionProxy.php
│   │   ├── WalletTopUp.php
│   │   ├── WalletTopUpProxy.php
│   │   ├── WalletWithdrawalRequest.php
│   │   └── WalletWithdrawalRequestProxy.php
│   │
│   ├── Payment/
│   │   └── WalletPayment.php                      ← extends Payment
│   │
│   ├── Providers/
│   │   ├── WalletServiceProvider.php
│   │   ├── EventServiceProvider.php
│   │   └── ModuleServiceProvider.php
│   │
│   ├── Repositories/
│   │   ├── WalletAccountRepository.php
│   │   ├── WalletTransactionRepository.php
│   │   ├── WalletTopUpRepository.php
│   │   └── WalletWithdrawalRequestRepository.php
│   │
│   ├── Resources/
│   │   ├── lang/
│   │   │   ├── ar/app.php
│   │   │   ├── en/app.php
│   │   │   └── ... (21 locales)
│   │   └── views/
│   │       ├── admin/
│   │       └── shop/
│   │
│   ├── Routes/
│   │   ├── admin-wallet-routes.php
│   │   └── shop-wallet-routes.php
│   │
│   └── Services/
│       └── WalletService.php                      ← Core service: debit/credit/hold/release
│
└── tests/
    ├── Unit/
    │   ├── WalletServiceTest.php
    │   └── WalletTransactionTest.php
    └── Feature/
        ├── WalletCheckoutTest.php
        ├── WalletRefundTest.php
        └── WalletWithdrawalTest.php
```

### 11.2 WalletService — Interface العام

```
WalletService::credit(WalletAccount $wallet, float $amount, string $type, array $meta): WalletTransaction
WalletService::debit(WalletAccount $wallet, float $amount, string $type, array $meta): WalletTransaction
WalletService::hold(WalletAccount $wallet, float $amount, string $type, array $meta): WalletTransaction
WalletService::release(WalletAccount $wallet, float $amount, string $type, array $meta): WalletTransaction
WalletService::adjust(WalletAccount $wallet, float $amount, string $direction, string $reason): WalletTransaction
WalletService::getBalance(WalletAccount $wallet): array
```

كل هذه الـ methods تعمل داخل DB Transaction مع lockForUpdate.

---

## Pre-Development Mandatory Checklist

يجب تنفيذ هذه المهام قبل بدء Sprint 0:

```
[ ] D-001: ✅ محسوم — Proactive (customer.create.after)
[ ] D-003: ✅ محسوم — تعطيل Admin\Listeners\Refund::refundOrder()
[ ] إضافة wallet entries في packages/Webkul/Admin/src/Config/menu.php
[ ] إضافة wallet permissions في packages/Webkul/Admin/src/Config/acl.php
[ ] إضافة wallet keys في en/app.php و ar/app.php و19 لغة أخرى
[ ] تسجيل WalletServiceProvider في bootstrap/providers.php
[ ] تسجيل WalletAccount Model في config/concord.php
```

---

*نهاية الوثيقة — HIGEST Wallet Technical Implementation Specification v1.0*