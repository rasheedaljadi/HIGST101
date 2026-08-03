# HIGEST Wallet Implementation Readiness Audit

> **تاريخ التدقيق:** 2026-08-03
> **النظام:** HIGEST / Bagisto 2.4.x / Laravel 12 / PHP 8.3
> **المراجع:** HIGEST Wallet Domain Discovery Audit + WALLET_DOMAIN_SPECIFICATION.md

---

## Executive Summary

بعد إجراء تدقيق معماري وتقني شامل يغطي 12 منطقة من النظام الحالي، الخلاصة:

**الحكم النهائي: READY WITH CONDITIONS**

النظام يمتلك الأساس المعماري الصحيح لبناء Wallet Domain. لا يوجد أي Blocker حرج يمنع التطوير، لكن توجد 4 شروط يجب تحقيقها موازياً مع بداية التطوير وقرار واحد يجب حسمه قبل كتابة أول سطر كود.

| منطقة التدقيق | الحالة |
|---|---|
| Payment Architecture | READY |
| Customer Integration | READY WITH CONDITIONS |
| Refund Lifecycle | NEEDS CHANGE |
| Order Cancellation | NEEDS DEFINITION |
| Wallet Balance Safety | READY (نمط موجود) |
| Database Compatibility | READY |
| Admin Panel | READY WITH CONDITIONS |
| Notification System | READY (محدود) |
| Payment Gateway Compatibility | READY |
| Security | READY (نمط موجود) |
| Package Architecture | READY |
| Translations | NEEDS CHANGE |

---

## Current Architecture Findings

### A. ما هو موجود ويصلح كـ Foundation

```
1. Payment Method System:
   - Payment abstract class موجود ومرن
   - Config-based Registry جاهز لإضافة WalletPayment
   - mergeConfigFrom pattern مفهوم وموثق في Paypal/Stripe

2. Wallet Admin Skeleton:
   - wallet-routes.php مُحمَّل فعلاً في web.php (السطر 35)
   - WalletController موجود بـ 3 methods
   - الـ Routes مسجَّلة تحت middleware ['admin']
   - Views placeholder عاملة

3. Events System:
   - كل أحداث الطلبات والـ Refunds موجودة وتعمل
   - EventServiceProvider pattern مفهوم في Admin وShop
   - customer.create.after مُطلَق في مكانين:
     - Shop\RegistrationController (السطر 96)
     - Admin\CustomerController (السطر 125)

4. DB Transaction Pattern:
   - OrderRepository, RefundRepository, InvoiceRepository, ShipmentRepository
   - كلها تستخدم DB::beginTransaction() / commit() / rollBack()
   - هذا النمط جاهز للتطبيق في WalletService

5. lockForUpdate Pattern:
   - مستخدم في CartRule\Listeners\Order (السطور 64, 80, 122, 152)
   - مستخدم في Fulfillment\Services\OutboxEventProcessor
   - النمط موجود وصحيح للتطبيق على wallet_accounts

6. Encrypted Cast:
   - ProviderAccount.php يستخدم 'encrypted' cast على app_secret, access_token, refresh_token
   - نفس النمط يصلح لتشفير bank_details في wallet_withdrawal_requests

7. Notification Infrastructure:
   - Notification Model + Repository موجود (type, order_id, read)
   - Admin Email system عبر Mail::queue() في Base Listener
   - Shop Email system عبر Listeners متخصصة

8. DataGrid Pattern:
   - OrderRefundDataGrid يُعدّ النموذج المثالي للتطبيق
   - يرث من Webkul\DataGrid\DataGrid
   - يُستدعى عبر datagrid(ClassName::class)->process()
   - يستخدم bouncer()->hasPermission() للتحقق من الصلاحيات
```

### B. ما يحتاج تعديلاً أو قراراً

```
1. Admin\Listeners\Refund::refundOrder():
   - يُعيد المال لـ PayPal تلقائياً عند كل Refund
   - يتعارض مع قرار "كل Refunds تذهب للمحفظة"
   - يجب تعطيل هذا السلوك أو تشريطه

2. ترجمات Wallet:
   - موجودة فقط في ar/app.php (4 مفاتيح)
   - غائبة كلياً عن en/app.php
   - يجب إضافة 4 مفاتيح للـ en وللـ 21 لغة حسب القواعد

3. Wallet في menu.php:
   - غائب كلياً من ملف menu.php!
   - wallet-routes.php مُحمَّل لكن لا يوجد قيد menu يُعرض في Sidebar
   - يجب إضافة قيد 'wallet' في menu.php

4. Wallet ACL:
   - لا يوجد أي قيد wallet في acl.php
   - WalletController الحالي لا يتحقق من الصلاحيات
   - يجب تسجيل أذونات wallet في acl.php

5. Customer.php لا يملك wallet() HasOne:
   - العلاقة غير موجودة حالياً
   - يجب إضافتها (الطريقة المثلى عبر Concord Proxy)
```

---

## Integration Points

### 1. Payment Architecture Integration

**الوضع الحالي (مُثبَت من الكود):**

```
Config::get('payment_methods') ← يجمع configs من كل Package عبر mergeConfigFrom
                │
                ↓
Payment::getPaymentMethods()  ← يُكرّر على كل entry, يستدعي app($class)
                │
                ↓
$paymentMethod->isAvailable() ← يتحقق من core()->getConfigData('active')
                │
                ↓
يُضاف للقائمة إذا كان متاحاً
```

**كيف يُضاف WalletPayment:**

```
1. packages/Webkul/Wallet/src/Config/payment-methods.php:
   return [
       'wallet' => [
           'class'  => WalletPayment::class,
           'code'   => 'wallet',
           'title'  => 'Wallet',
           'active' => true,
           'sort'   => 10,
       ],
   ];

2. WalletServiceProvider::registerConfig():
   $this->mergeConfigFrom(dirname(__DIR__).'/Config/payment-methods.php', 'payment_methods');

3. WalletPayment::isAvailable():
   - Check: config active
   - Check: auth()->guard('customer')->check()
   - Check: wallet()->available_balance > 0
```

**نتيجة التدقيق:** `Payment Integration Status: READY`

لا يحتاج تعديل Core. Package مستقل بالكامل.

---

### 2. Customer Integration

**الوضع الحالي (مُثبَت من الكود):**

```
Customer Model موجودة في:
packages/Webkul/Customer/src/Models/Customer.php

العلاقات الموجودة:
- group()           BelongsTo
- addresses()       HasMany
- default_address() HasOne
- invoices()        HasManyThrough
- wishlist_items()  HasMany
- all_carts()       HasMany
- orders()          HasMany
- reviews()         HasMany
- notes()           HasMany
- subscription()    HasOne
- channel()         BelongsTo

العلاقة المطلوبة (غير موجودة):
- wallet()          HasOne  ← MISSING
```

**كيف تُضاف العلاقة بدون تعديل Customer Package:**

النمط الصحيح في Bagisto هو عبر `CustomerProxy` و Concord Module System.
لكن الطريقة الأبسط والمتوافقة هي إضافة wallet() مباشرة في Customer Model عبر Macro أو Observer.

**حدث إنشاء المحفظة:**

`customer.create.after` متاح في مكانين مؤكدَين:
- `Shop\RegistrationController` line 96
- `Admin\CustomerController` line 125

**نتيجة التدقيق:** `Customer Integration Status: READY WITH CONDITIONS`

الشرط: إضافة wallet() علاقة في Customer Model (Open Decision OQ-001 يجب حسمه أولاً).

---

### 3. Refund Lifecycle

**التتبع الكامل للـ Refund Flow (مُثبَت من الكود):**

```
Admin → RefundController::store()
              │
              ↓
        RefundRepository::create()
              │ DB::beginTransaction()
              │ Event::dispatch('sales.refund.save.before', $data)
              │ [إنشاء refund + items + تحديث مخزون + totals + حالة الطلب]
              │ Event::dispatch('sales.refund.save.after', $refund)
              │ DB::commit()
              │
              ↓ Event: sales.refund.save.after
              │
        ┌─────┴──────────────────────────────┐
        │                                    │
  Admin\Listeners\Refund::afterCreated()     Shop\Listeners\Refund::afterCreated()
        │                                    │
        ├── prepareMail() [Email للإدارة]    └── prepareMail() [Email للعميل]
        │
        └── refundOrder()  ← ⚠️ التعارض الحرج
               │
               └── if (payment == 'paypal_smart_button'):
                       smartButton->refundOrder()  ← يُعيد المال لـ PayPal
```

**التعارض الحرج:**

القرار المتخذ في الـ Specification: "جميع Refunds تذهب للمحفظة".
لكن `Admin\Listeners\Refund::refundOrder()` يُعيد المال لـ PayPal تلقائياً.

**Refund Integration Decision:**

```
المطلوب:
  1. تعطيل أو تشريط سلوك PayPal refund في refundOrder()
  2. إضافة Wallet Listener يستمع على sales.refund.save.after
  3. ترتيب تنفيذ الـ Listeners يجب مراجعته
     (Wallet Listener يجب أن يعمل بعد انتهاء الـ RefundRepository DB Transaction)

حل مقترح:
  Wallet EventServiceProvider يُسجَّل:
    'sales.refund.save.after' => [WalletCreditOnRefund::class, 'handle']
  
  WalletCreditOnRefund::handle($refund):
    - يجلب WalletAccount للعميل (أو ينشئها)
    - يُضيف credit بقيمة refund.base_grand_total
```

**نتيجة التدقيق:** `Refund Integration: NEEDS CHANGE`

التعارض مع PayPal يجب معالجته قبل تفعيل Wallet Refund.

---

### 4. Order Cancellation Scenarios

| الحالة | السلوك الحالي | إجراء المحفظة المطلوب | نقطة التكامل |
|---|---|---|---|
| **Pending → Canceled** (لم يُدفع) | يُلغى الطلب فقط، لا إعادة مال | لا شيء — لم يُخصَم رصيد | لا شيء |
| **Pending Payment → Canceled** (بدأ الدفع ولم يكتمل) | يُلغى الطلب | لا شيء — hold يُلغى | WalletTransaction(release) إن كان hold نشطاً |
| **Processing → Canceled** (مدفوع بالمحفظة) | `sales.order.cancel.after` يُطلَق | credit للمحفظة بقيمة ما خُصِم | `sales.order.cancel.after` |
| **Processing → Canceled** (مدفوع بـ Gateway) | لا إعادة مال تلقائية | credit للمحفظة (حسب الـ Spec) | `sales.order.cancel.after` |
| **Refund Created** (أي طلب) | `sales.refund.save.after` يُطلَق | credit للمحفظة | `sales.refund.save.after` |
| **Completed → Return** (RMA) | يمر عبر RMA Package | حسب نتيجة RMA → Refund | `sales.refund.save.after` |

**ملاحظة حرجة:** عند إلغاء طلب مدفوع بالمحفظة (Scenario 3)، يجب أن يعرف الـ Listener بأن الطلب كان مدفوعاً بالمحفظة وبأي مبلغ. هذه المعلومة موجودة في `order->payment->method` وفي `wallet_transactions`.

**Open Decision OQ-002 (من الـ Spec):** هل الإلغاء يُضيف الرصيد فوراً؟ القرار غير محسوم.

---

## Compatibility Assessment

### Payment System Compatibility

```
✅ Payment Abstract Class:
   - abstract getRedirectUrl() ← سيُعيد null للـ Wallet (الدفع فوري)
   - isAvailable() override ← يتحقق من الرصيد والحالة
   - getConfigData() يقرأ من config('sales.payment_methods.wallet.*')
   - لا يوجد interface إجباري إضافي — فقط الـ abstract class

✅ Config Registration:
   - PaypalServiceProvider يُسجِّل في 'payment_methods' عبر mergeConfigFrom
   - نفس النمط بالضبط لـ WalletServiceProvider

✅ OrderPayment:
   - cart->payment() HasOne → CartPayment (method, additional)
   - order->payment()->create($data['payment']) في OrderRepository::create()
   - method سيُحفظ كـ 'wallet' أو 'wallet_partial'

⚠️ الدفع الجزئي (Wallet + Gateway):
   - النظام الحالي لا يدعم طريقتي دفع لطلب واحد
   - cart->payment() هو HasOne → يُخزِّن method واحدة
   - سيحتاج تفكيراً إضافياً في تخزين "wallet_amount" + "gateway_method"
   - الحل المقترح: additional JSON field في cart_payments / order_payments
```

### Database Architecture Compatibility

```
✅ Naming Convention:
   - الجداول المقترحة (wallet_accounts, wallet_transactions, wallet_topups, wallet_withdrawal_requests)
   - تتطابق مع نمط التسمية: snake_case, plural
   - مثال: order_transactions, ledger_entries, procurement_sagas

✅ Migration Structure:
   - كل Package له Database/Migrations/ خاصة بها
   - migrations تُحمَّل عبر loadMigrationsFrom() في ServiceProvider

✅ Foreign Keys:
   - النمط: customer_id → customers.id (ON DELETE RESTRICT) ← مثال: WalletAccount
   - النمط: admin_user_id → admins.id (ON DELETE SET NULL) ← مثال: TopUp, Withdrawal

✅ Financial Precision:
   - النظام يستخدم decimal(12,4) في كل الجداول المالية
   - wallet_accounts و wallet_transactions تتبع نفس النمط

✅ Existing Financial Tables (لا تعارض):
   - order_transactions: خاص بـ Gateway transactions → لا تداخل
   - ledger_entries: خاص بـ Dropshipping → لا تداخل
   - financial_timeline: خاص بـ Dropshipping orders → لا تداخل
```

---

## Security Assessment

### Race Conditions

**مستوى الخطر: عالٍ إن لم يُعالَج**

```
مشكلة:
  عميل يفتح نافذتين ويحاول الدفع في نفس الوقت:
  - Window A: يقرأ available_balance = 100
  - Window B: يقرأ available_balance = 100
  - Window A: يخصم 100 → available_balance = 0
  - Window B: يخصم 100 → available_balance = -100 (كارثة!)

الحل الموثَّق (موجود في الكود):
  CartRule\Listeners\Order.php يستخدم:
    DB::transaction(function() {
        $rule = CartRule::lockForUpdate()->find($id);
        // تحقق وتعديل آمن
    });

  هذا بالضبط ما يجب تطبيقه في WalletService:
    DB::transaction(function() {
        $wallet = WalletAccount::lockForUpdate()->find($walletId);
        if ($wallet->available_balance < $amount) {
            throw new InsufficientBalanceException();
        }
        // إجراء العملية
        WalletTransaction::create([...]);
        $wallet->decrement('available_balance', $amount);
    });
```

**نتيجة:** النمط الصحيح موجود في الكود. يجب تطبيقه في WalletService.

### Data Protection

```
✅ Encrypted Cast:
   Fulfillment\Models\ProviderAccount يستخدم:
   'app_secret'    => 'encrypted',
   'access_token'  => 'encrypted',
   'refresh_token' => 'encrypted',

   نفس النمط يُطبَّق على:
   wallet_withdrawal_requests.bank_details → 'encrypted:json' cast

✅ Audit Trail:
   wallet_transactions غير قابلة للتعديل (Immutability) ← موثَّق في الـ Spec
   كل تغيير إداري مُسجَّل مع admin_user_id + timestamps

✅ ACL:
   bouncer()->hasPermission() مستخدم في DataGrids الحالية
   نفس النمط يُطبَّق على Wallet DataGrids
```

**Financial Integrity Status:** `READY — النمط موجود، يحتاج تطبيقاً`

---

## Database Assessment

### الجداول المقترحة vs الأنماط الموجودة

| الجدول المقترح | متوافق؟ | ملاحظة |
|---|---|---|
| `wallet_accounts` | ✅ | يتبع نمط customers + orders |
| `wallet_transactions` | ✅ | يتبع نمط order_transactions |
| `wallet_topups` | ✅ | لا يوجد تعارض |
| `wallet_withdrawal_requests` | ✅ | لا يوجد تعارض |

### التحقق من عدم التعارض مع الجداول الموجودة

```
✅ لا يوجد جدول wallet_* في النظام حالياً
✅ customer_id غير مستخدم كـ UNIQUE FK في أي جدول آخر (محفظة واحدة فقط)
✅ decimal(12,4) متوافق مع جميع الجداول المالية في المشروع
```

---

## Admin Panel Assessment

### ما هو موجود فعلاً (مُثبَت)

| العنصر | الحالة |
|---|---|
| `wallet-routes.php` | ✅ موجود ومُحمَّل في web.php السطر 35 |
| `WalletController` | ✅ موجود — 3 methods جاهزة للتوسيع |
| `Views (coming-soon)` | ✅ موجودة تعمل |
| قيد Wallet في `menu.php` | ❌ **غائب تماماً** |
| قيد Wallet في `acl.php` | ❌ **غائب تماماً** |
| ترجمة `en/app.php` | ❌ **غائبة** |
| ترجمة `ar/app.php` | ✅ 4 مفاتيح موجودة |

### ما يجب إضافته في Admin Panel

```
1. menu.php:
   [
     'key'   => 'wallet',
     'name'  => 'admin::app.components.layouts.sidebar.wallet',
     'route' => 'admin.wallet.deposits.index',
     'sort'  => 8,
     'icon'  => 'icon-wallet',
   ],
   [
     'key'   => 'wallet.deposits',
     'name'  => 'admin::app.components.layouts.sidebar.wallet-deposits',
     'route' => 'admin.wallet.deposits.index',
     'sort'  => 1,
   ],
   // + withdrawals + settings

2. acl.php:
   ['key' => 'wallet', ...],
   ['key' => 'wallet.deposits', ...],
   ['key' => 'wallet.deposits.view', ...],
   ['key' => 'wallet.deposits.approve', ...],
   ['key' => 'wallet.deposits.reject', ...],
   ['key' => 'wallet.withdrawals', ...],
   ['key' => 'wallet.withdrawals.view', ...],
   ['key' => 'wallet.withdrawals.process', ...],
   ['key' => 'wallet.withdrawals.reject', ...],
   ['key' => 'wallet.settings', ...],

3. en/app.php:
   إضافة مفاتيح wallet* في قسم components.layouts.sidebar

4. DataGrids جديدة:
   - WalletAccountsDataGrid
   - WalletTransactionsDataGrid
   - WalletTopUpsDataGrid
   - WalletWithdrawalRequestsDataGrid

5. system.php settings:
   إضافة قسم sales.wallet:
   - sales.wallet.active
   - sales.wallet.enable_withdrawal
   - sales.wallet.min_topup_amount (إن حُسِم)
   - sales.wallet.min_withdrawal_amount (إن حُسِم)
```

---

## Payment Flow Assessment

### كيفية استقبال Callback / Webhook للـ Top-Up

**من دراسة PayPal:**

```
PayPal يستخدم:
  GET  /paypal/standard/redirect  → redirect للبوابة
  GET  /paypal/standard/success   → success callback
  GET  /paypal/standard/cancel    → cancel callback
  POST /paypal/standard/ipn       → webhook (بدون CSRF)

SmartButton يستخدم:
  GET  /paypal/smart-button/create-order   → إنشاء PayPal Order
  POST /paypal/smart-button/capture-order  → التقاط الدفع + إنشاء Order في النظام
```

**كيف يعمل Top-Up عبر نفس البوابات:**

```
الـ Top-Up Flow مختلف عن الـ Checkout Flow:

1. لا يوجد Cart في Top-Up
2. WalletTopUpController يستقبل مبلغ الإيداع وطريقة الدفع
3. يُنشأ WalletTopUp (status = PENDING) أولاً
4. يُوجَّه العميل للبوابة مع reference = topup_id
5. البوابة تُعيد Callback → WalletTopUpController::callback()
6. يُحدَّث status = PAYMENT_RECEIVED

ملاحظة: Wallet Top-Up لا يمر عبر CartRepository أو OrderRepository
بل عبر Flow مستقل في packages/Webkul/Wallet/
```

---

## Refund Flow Assessment

### الوضع الحالي الكامل (مُثبَت)

```
sales.refund.save.after مُستمَع من:

Admin\Providers\EventServiceProvider:
  'sales.refund.save.after' => [[Refund::class, 'afterCreated']]
     ↓
  afterCreated($refund):
    1. refundOrder($refund)   ← ⚠️ يُعيد لـ PayPal
    2. prepareMail(...)       ← إيميل للإدارة

Shop\Providers\EventServiceProvider:
  'sales.refund.save.after' => [[Refund::class, 'afterCreated']]
     ↓
  Shop\Listeners\Refund::afterCreated($refund):
    1. prepareMail(...)       ← إيميل للعميل
```

### الحل المطلوب

```
Option A (الأبسط): تعطيل refundOrder() بالكامل
  → إزالة استدعاء smartButton->refundOrder() من Admin\Listeners\Refund
  → إضافة Wallet Listener على sales.refund.save.after

Option B (الأكثر دقة): تشريط السلوك
  → if ($order->payment->method !== 'paypal_smart_button') → Wallet Credit
  → if ($order->payment->method === 'paypal_smart_button') → PayPal Refund OR Wallet Credit
  (هذا القرار محدَّد كـ OQ-005 في الـ Specification)

القرار المطلوب: الإجابة على OQ-005
```

---

## Identified Risks

### Risk 1: الدفع الجزئي (Partial Payment) — تعقيد معماري

**المستوى: متوسط**

```
المشكلة:
  cart->payment() → HasOne → CartPayment (method واحدة فقط)
  الدفع الجزئي يحتاج: wallet_amount + gateway_method + gateway_amount

التأثير:
  قد يتطلب تعديل cart_payments و order_payments جداول
  أو استخدام additional JSON field

التخفيف:
  الـ additional field موجود في CartPayment (JSON)
  يمكن تخزين ['wallet_amount' => X, 'remaining_method' => 'paypal'] هناك
```

### Risk 2: Refund PayPal Conflict — تعارض موجود حالياً

**المستوى: عالٍ**

```
المشكلة:
  Admin\Listeners\Refund::refundOrder() يُعيد المال لـ PayPal حالياً
  إذا فعّلنا Wallet Refund بدون تعطيله → قد يحدث Double Refund

التأثير:
  المال يُعاد للعميل مرتين (PayPal + محفظة)

التخفيف:
  يجب تعطيل refundOrder() أو تشريطه قبل تفعيل Wallet Listener
  هذا يُعدّ Required Change قبل التطوير
```

### Risk 3: Listener Execution Order — ترتيب الـ Listeners

**المستوى: منخفض-متوسط**

```
المشكلة:
  sales.refund.save.after له مستمعون متعددون
  Wallet Listener يُضاف كمستمع جديد
  ترتيب التنفيذ غير مضمون بشكل صريح

التأثير:
  إذا فشل Wallet Listener → هل يُلغى الـ Refund؟
  يجب أن تكون Wallet Credit عملية منفصلة عن Refund DB Transaction

التخفيف:
  Wallet Listener يعمل داخل DB Transaction مستقلة خاصة به
  إذا فشل → يُسجَّل خطأ ولا يُلغى الـ Refund الأصلي
```

### Risk 4: Translation Gap — 21 لغة مطلوبة

**المستوى: متوسط**

```
المشكلة:
  ترجمات wallet موجودة في ar/app.php فقط (4 مفاتيح)
  غائبة عن en/app.php وجميع اللغات الأخرى
  CI يفشل إذا وُجد مفتاح في لغة واحدة وغاب عن الأخرى

التأثير:
  فشل translation_tests.yml في CI

التخفيف:
  إضافة مفاتيح wallet لكل اللغات الـ 21 قبل أي commit
```

### Risk 5: Customer Deletion Restriction

**المستوى: منخفض**

```
المشكلة:
  wallet_accounts.customer_id → ON DELETE RESTRICT
  عميل لديه محفظة برصيد لا يمكن حذفه

التأثير:
  Admin سيحاول حذف عميل فيفشل بدون رسالة واضحة

التخفيف:
  إضافة validation قبل حذف العميل: هل لديه محفظة برصيد؟
  أو إضافة soft delete وتجميد المحفظة
```

---

## Required Decisions

| # | القرار | الخيارات | التوصية | المسؤول |
|---|---|---|---|---|
| ~~**D-001**~~ | ~~متى تُنشأ المحفظة؟ (OQ-001)~~ | ~~A) عند التسجيل (Proactive)~~<br/>~~B) عند أول استخدام (Lazy)~~ | **✅ محسوم: Proactive — عند customer.create.after** | — |
| **D-002** | إلغاء طلب مدفوع بالمحفظة (OQ-002) | A) Credit فوري عند sales.order.cancel.after<br/>B) يمر عبر Refund Flow | **A**: أسرع للعميل وأبسط تقنياً | صاحب القرار التجاري |
| ~~**D-003**~~ | ~~PayPal Refund Conflict (OQ-005)~~ | ~~A) تعطيل refundOrder() كلياً~~<br/>~~B) تشريطه~~ | **✅ محسوم: تعطيل كامل لـ refundOrder()** | — |
| **D-004** | الدفع الجزئي — تخزين البيانات | A) additional JSON في CartPayment<br/>B) حقل wallet_amount جديد في cart_payments | **A**: لا يحتاج migration جديداً | المطور المسؤول |
| **D-005** | الحد الأدنى للإيداع والسحب (OQ-006) | A) بدون حد أدنى<br/>B) حد أدنى قابل للضبط من الإعدادات | **B**: أكثر مرونة عبر system.php config | صاحب القرار التجاري |
| **D-006** | Wallet للزوار (OQ-003) | A) مسجَّلون فقط<br/>B) الزوار أيضاً | **A**: الأمر واضح — الزوار لا يملكون محفظة | — (واضح، لا يحتاج نقاشاً) |
| **D-007** | قنوات الإشعارات (OQ-007) | A) Email فقط<br/>B) Email + Database Notification | **B**: الـ Notification Package موجود | صاحب القرار التجاري |

---

## Implementation Readiness Score

### READY WITH CONDITIONS ✅

```
النظام جاهز للتطوير بشرط:

[الشرط الإلزامي — يجب حسمه قبل أول سطر كود]
  ✓ D-001: حسم متى تُنشأ المحفظة (Proactive/Lazy)
  ✓ D-003: حسم سلوك PayPal عند Refund

[شروط تُنفَّذ بداية التطوير]
  ✓ تعطيل / تشريط Admin\Listeners\Refund::refundOrder()
  ✓ إضافة wallet translations لـ 21 لغة (4 مفاتيح على الأقل)
  ✓ إضافة wallet entries في menu.php و acl.php

[قرارات لا تعيق البداية لكن يجب حسمها في Sprint 1]
  ✓ D-002: إلغاء الطلب (Credit فوري أم Refund Flow)
  ✓ D-004: آلية تخزين الدفع الجزئي
  ✓ D-005: الحدود الدنيا
  ✓ D-007: قنوات الإشعارات
```

---

## Recommended Next Steps

### الترتيب الزمني المقترح للبدء

```
[قبل البداية — القرارات الحرجة]
  1. حسم D-001 و D-003
  2. تعطيل سلوك PayPal Refund (Admin\Listeners\Refund::refundOrder)

[Sprint 0 — إعداد البنية التحتية]
  3. إنشاء packages/Webkul/Wallet/ بالهيكل الكامل
  4. تسجيل WalletServiceProvider في bootstrap/providers.php
  5. إضافة wallet translations للـ 21 لغة
  6. إضافة wallet entries في menu.php وacl.php

[Sprint 1 — Database + Core Domain]
  7. Migrations: wallet_accounts, wallet_transactions, wallet_topups, wallet_withdrawal_requests
  8. Contracts + Models + Proxies + Repositories
  9. WalletService (debit, credit, hold, release) مع DB Locking

[Sprint 2 — Integration]
  10. WalletPayment Payment Method
  11. Wallet Event Listeners (Refund Credit, Order Cancel, Order Creation)
  12. customer.create.after → إنشاء المحفظة (إذا D-001 = Proactive)

[Sprint 3 — Admin Panel]
  13. DataGrids (Accounts, Transactions, TopUps, Withdrawals)
  14. Controllers + Views للإدارة
  15. Top-Up Approval Flow
  16. Withdrawal Processing Flow

[Sprint 4 — Shop (Customer Portal)]
  17. Wallet Balance + Transaction History
  18. Top-Up Flow
  19. Withdrawal Request Form
  20. Checkout Integration (Payment Method)

[Sprint 5 — Polish]
  21. Email Notifications
  22. Database Notifications
  23. Tests (Pest + Playwright)
  24. Translations الكاملة لكل اللغات
```

### الملفات التي تحتاج تعديلاً في المشروع الحالي

| الملف | التعديل المطلوب | الأولوية |
|---|---|---|
| `Admin\Listeners\Refund` | تعطيل/تشريط refundOrder() | 🔴 حرج |
| `Admin\Config\menu.php` | إضافة wallet entries | 🟠 عالٍ |
| `Admin\Config\acl.php` | إضافة wallet permissions | 🟠 عالٍ |
| `Admin\Resources\lang\en\app.php` | إضافة 4 مفاتيح wallet | 🟠 عالٍ |
| جميع ملفات اللغات الـ 21 | إضافة 4 مفاتيح wallet | 🟠 عالٍ |
| `Customer\Models\Customer.php` | إضافة wallet() HasOne | 🟡 متوسط |
| `bootstrap\providers.php` | تسجيل WalletServiceProvider | 🟡 متوسط |

---

*نهاية التقرير — HIGEST Wallet Implementation Readiness Audit v1.0*