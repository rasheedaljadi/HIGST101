# HIGEST Wallet — Sprint 0.5: Financial Domain Finalization

> **الحالة:** محسوم — جاهز للتنفيذ
> **الهدف:** تثبيت كل القرارات المعمارية المالية قبل أي Migration أو Model

---

## الرد على الملاحظات الثماني

---

### ملاحظة 1 — تعطيل Refund.php: هل التغيير صحيح؟

**✅ التغيير الذي تم صحيح بالكامل.**

من تحليل `RefundRepository::create()` (السطور 42–171):

```
DB::beginTransaction()
  Event::dispatch('sales.refund.save.before')
  ↓
  [إنشاء سجل Refund]
  [إنشاء RefundItems]
  ↓
  returnQtyToProductInventory()   ← إعادة المخزون
  orderItemRepository::collectTotals() ← تحديث الكميات
  orderRepository::collectTotals()  ← تحديث الطلب
  orderRepository::updateOrderStatus() ← تحديث حالة الطلب
  ↓
  Event::dispatch('sales.refund.save.after', $refund)  ← السطر 162
DB::commit()  ← السطر 164
```

`refundOrder()` في `Admin\Listeners\Refund` ليست جزءاً من الـ Refund Domain —
هي مجرد **API Call لـ PayPal** لإعادة الأموال للبطاقة. لا علاقة لها بالمخزون أو الكميات.

الفصل كان قائماً أصلاً:

```
RefundRepository::create()     ← Domain Logic (لم يُمَسّ)
        ↕  Event: sales.refund.save.after
Admin\Listeners\Refund         ← كانت فقط PayPal API (تم تعطيلها)
Wallet\Listeners\...           ← Wallet Credit (سيُضاف)
```

**النتيجة:** التغيير المُطبَّق صحيح 100%. Domain Logic سليمة.

---

### ملاحظة 2 — WalletTransaction كـ Aggregate Root

**✅ مثبَّت كقاعدة Domain.**

```
الحقيقة المالية = wallet_transactions (Ledger)
wallet_accounts.balance = Projection/Cache للأداء فقط

القاعدة الإلزامية:
  لا يُعدَّل wallet_accounts.available_balance مباشرةً أبداً
  إلا عبر WalletService التي تنشئ Transaction أولاً ثم تُحدِّث الـ Cache

مخطط التدفق:
  Action
    ↓
  WalletService::credit() / debit() / hold() / release()
    ↓
  DB::transaction() + lockForUpdate()
    ↓
  WalletTransaction::create([...])
    ↓
  WalletAccount->increment/decrement('available_balance', $amount)
    ↓
  COMMIT
```

**لا يوجد مسار لتعديل الرصيد خارج WalletService.**

---

### ملاحظة 3 — Ledger Integrity: reference_transaction_id

**✅ مُعتمَد — يُضاف إلى جدول wallet_transactions.**

```
wallet_transactions:
  + reference_transaction_id → FK → wallet_transactions.id (NULLABLE)

الاستخدام:
  ADJUSTMENT لتصحيح CREDIT_REFUND خاطئ:
    WalletTransaction::create([
        type                     => ADJUSTMENT,
        amount                   => 100.00,
        direction                => 'debit',
        reference_transaction_id => $refundTransaction->id,
        description              => 'Correction for erroneous CREDIT_REFUND #321',
    ])

القاعدة:
  السجل الأصلي لا يُحذَف ولا يُعدَّل.
  التصحيح يكون دائماً بإدخال سجل ADJUSTMENT جديد.
  reference_transaction_id يربط التصحيح بالسجل الأصلي للتدقيق.
```

---

### ملاحظة 4 — Wallet Lifecycle Matrix الكامل

**المصفوفة النهائية المعتمدة:**

| الحدث | Transaction Type | التأثير على available | التأثير على held | من يُنشئها |
|---|---|---|---|---|
| Refund معتمد | `CREDIT_REFUND` | +amount | — | Wallet Listener (sales.refund.save.after) |
| Order Cancel (مدفوع بالمحفظة) | `CREDIT_CANCEL` | +amount | — | Wallet Listener (sales.order.cancel.after) |
| Payment Failed بعد خصم | `RELEASE_PAYMENT` | +amount | — | WalletService (عند rollback) |
| Return Approved (RMA → Refund) | `CREDIT_REFUND` | +amount | — | نفس مسار Refund (RMA ينشئ Refund) |
| Admin Adjustment (إيجابي) | `ADJUSTMENT` | +amount | — | Admin only |
| Admin Adjustment (سالب) | `ADJUSTMENT` | -amount | — | Admin only |
| Top-Up Approved | `CREDIT_TOPUP` | +amount | — | System (بعد Admin Approval) |
| دفع طلب بالمحفظة | `DEBIT_PAYMENT` | -amount | — | Wallet Listener (checkout.order.save.after) |
| Withdrawal Request إنشاء | `HOLD_WITHDRAWAL` | -amount | +amount | System (WalletService::hold) |
| Withdrawal Completed | `DEBIT_WITHDRAWAL` | — | -amount | System (Admin Action) |
| Withdrawal Rejected | `RELEASE_HOLD` | +amount | -amount | System (Admin Action) |
| Wallet Suspended | `SUSPENSION_FREEZE` | -amount | +amount | Admin only |
| Wallet Reactivated | `SUSPENSION_RELEASE` | +amount | -amount | Admin only |

**ملاحظة:** CREDIT_RETURN ليس نوعاً منفصلاً — RMA في HIGEST ينشئ Refund عند الموافقة، والـ Refund يُطلق `sales.refund.save.after` → يُعالَج كـ CREDIT_REFUND.

---

### ملاحظة 5 — مكان Wallet Listener للدفع

**✅ `checkout.order.save.after` هو الـ Hook الصحيح — مثبَّت.**

من تحليل `OrderRepository::createOrderIfNotThenRetry()` (السطور 49–115):

```php
DB::beginTransaction();    // ← السطر 49

try {
    Event::dispatch('checkout.order.save.before');
    $order = Order::create(...);
    $order->payment()->create($data['payment']);  // ← يُسجَّل payment.method = 'wallet'
    // إنشاء items + inventory management
    Event::dispatch('checkout.order.save.after', $order);  // ← السطر 86
} catch (\Exception $e) {
    DB::rollBack();   // ← إذا فشل Wallet Listener → Rollback الكل
    // ...
} finally {
    DB::commit();     // ← السطر 114
}
```

**المميزات:**
1. `checkout.order.save.after` يُطلَق **داخل** نفس `DB::beginTransaction()`
2. إذا رمى `DebitWalletOnOrderCreated` استثناءً (رصيد غير كافٍ) → `catch(\Exception $e)` يمسكه → `DB::rollBack()` يُلغي الطلب والمحفظة معاً
3. `order->payment->method` متاح عند هذه النقطة (تم إنشاؤه السطر 58)

**ملاحظة مهمة:** الـ finally block يُنفِّذ `DB::commit()` حتى لو كان هناك rollback. لذلك `DebitWalletOnOrderCreated` يجب أن يرمي Exception بدلاً من إعادة false لضمان الـ Rollback.

---

### ملاحظة 6 — حالات Top-Up المُعدَّلة

**✅ State Machine المعتمد لـ V1:**

```
PENDING_PAYMENT
    │
    │ (Customer redirected to gateway — completes payment)
    ▼
PAYMENT_RECEIVED
    │
    │ (Admin reviews — may request manual verification)
    ├──→ UNDER_REVIEW
    │         │
    │         └──→ COMPLETED ✅ (wallet credited)
    │         └──→ FAILED    ❌ (manual external refund by admin)
    │
    ├──→ COMPLETED ✅ (Admin approves directly)
    └──→ FAILED    ❌ (Admin rejects)

PENDING_PAYMENT ──→ CANCELLED  (Customer cancels before payment)
PENDING_PAYMENT ──→ EXPIRED    (No payment after X hours — cron job)
```

**الحالات النهائية:**

| الحالة | الوصف | يُضيف للمحفظة؟ |
|---|---|---|
| `pending_payment` | ينتظر إتمام الدفع | لا |
| `payment_received` | الدفع وصل | لا |
| `under_review` | قيد مراجعة الإدارة | لا |
| `completed` | معتمد + رصيد مُضاف | ✅ نعم |
| `failed` | مرفوض — إعادة خارجية | لا |
| `cancelled` | ألغاه العميل | لا |
| `expired` | انتهت المهلة | لا |

---

### ملاحظة 7 — Currency Strategy

**✅ القرار المعتمد لـ V1:**

```
محفظة واحدة = عملة واحدة = عملة المتجر الأساسية (base_currency_code)

التطبيق:
  WalletAccount.currency_code = config('app.currency') عند الإنشاء
  
  WalletPayment::isAvailable():
    if (core()->getBaseCurrencyCode() !== $wallet->currency_code): return false

  أي Top-Up أو Withdrawal يكون بنفس العملة الأساسية فقط.

V2 فقط: دعم متعدد العملات.
```

**السبب:** محفظة متعددة العملات تحتاج FX Rate Engine وتحويل تلقائي — خارج نطاق V1.

---

### ملاحظة 8 — Wallet Payment Priority في Checkout

**✅ القرار المعتمد: Option A (Regular Payment Method)**

```
الواجهة:
  [ ] Cash on Delivery
  [ ] Bank Transfer
  [*] HIGEST Wallet  ← يظهر كخيار عادي
      الرصيد المتاح: SAR 250.00

لماذا Option A:
  1. تتوافق مع Bagisto Payment Method Architecture تماماً
  2. Payment::getPaymentMethods() يعرضها تلقائياً
  3. isAvailable() يتحقق من الرصيد — إذا كان الرصيد أقل لا تظهر أصلاً
  4. لا تحتاج تعديل Checkout Views في Core

عرض الرصيد:
  WalletPayment::getDescription() → 'Your balance: SAR 250.00'
  يُعرَض في Blade View عبر $paymentMethod->getDescription()
```

---

## التغييرات على الـ Schema بناءً على Sprint 0.5

### wallet_transactions — إضافة حقل واحد

```
+ reference_transaction_id BIGINT UNSIGNED NULL FK wallet_transactions.id
+ direction ENUM('credit', 'debit') NOT NULL  ← لـ ADJUSTMENT type
```

### wallet_topups — تحديث قيم status ENUM

```
status ENUM(
  'pending_payment',
  'payment_received',
  'under_review',
  'completed',
  'failed',
  'cancelled',
  'expired'
) DEFAULT 'pending_payment'
```

---

## Transaction Types الكاملة (نهائية)

```
CREDIT_TOPUP          ← إيداع معتمد من الإدارة
CREDIT_REFUND         ← استرداد طلب (بما فيه Return/RMA)
CREDIT_CANCEL         ← إلغاء طلب مدفوع بالمحفظة
RELEASE_PAYMENT       ← إفراج عن خصم فشل (rollback scenario)
DEBIT_PAYMENT         ← خصم عند إنشاء الطلب
HOLD_WITHDRAWAL       ← حجز عند طلب السحب
DEBIT_WITHDRAWAL      ← خصم نهائي عند تنفيذ السحب
RELEASE_HOLD          ← إفراج عند رفض السحب
ADJUSTMENT            ← تعديل يدوي من الإدارة (credit/debit)
SUSPENSION_FREEZE     ← تجميد عند إيقاف المحفظة
SUSPENSION_RELEASE    ← إفراج عند إعادة تفعيل المحفظة
```

---

## الحالة بعد Sprint 0.5

| النقطة | القرار |
|---|---|
| Refund.php تغيير | ✅ صحيح — Domain Logic سليمة |
| WalletTransaction كـ Aggregate Root | ✅ مثبَّت |
| reference_transaction_id | ✅ مُضاف للـ Schema |
| Lifecycle Matrix | ✅ كامل — 13 نوع حدث |
| Checkout Event Hook | ✅ `checkout.order.save.after` داخل DB Transaction |
| Top-Up States | ✅ 7 حالات معتمدة |
| Currency V1 | ✅ عملة واحدة = base_currency_code |
| Payment UI | ✅ Option A — Regular Payment Method |

---

**النتيجة: كل القرارات محسومة — التنفيذ يستأنف من Sprint 1.**

*نهاية Sprint 0.5 — Financial Domain Finalization*