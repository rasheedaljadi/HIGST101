# WALLET DOMAIN SPECIFICATION
## HIGEST Platform — Bagisto 2.4.x / Laravel 12

---

> **المرجع الأساسي:** HIGEST Wallet Domain Discovery Audit (2026-08-03)
> **نوع الوثيقة:** Technical Specification — مرجع التصميم قبل التنفيذ
> **الإصدار:** 1.0
> **تاريخ الإصدار:** 2026-08-03

---

## Table of Contents

1. [Overview](#1-overview)
2. [Business Rules](#2-business-rules)
3. [Domain Entities](#3-domain-entities)
4. [Database Design](#4-database-design)
5. [Wallet Transaction Types](#5-wallet-transaction-types)
6. [Wallet Lifecycle](#6-wallet-lifecycle)
7. [Integration Points With Bagisto](#7-integration-points-with-bagisto)
8. [Security Considerations](#8-security-considerations)
9. [Open Questions](#9-open-questions)

---

## 1. Overview

### 1.1 الهدف من Wallet Domain

**HIGEST Wallet** هو نظام رصيد رقمي مرتبط بحساب العميل يُتيح:

- **الدفع عبر رصيد المحفظة** بدلاً من بوابة الدفع الخارجية أو بالتوازي معها.
- **استقبال المبالغ المستردة (Refunds)** تلقائياً بدلاً من إعادتها لبوابة الدفع.
- **إيداع الرصيد (Top-Up)** عبر بوابات الدفع المتاحة مع موافقة إدارية.
- **سحب الرصيد (Withdrawal)** لحساب بنكي خارجي بعد موافقة الإدارة وتنفيذها.

الوظيفة الأساسية للمحفظة هي **تسريع دورة الدفع** لدى العميل المتكرر، وتقليل الاعتماد على بوابات الدفع في حالات الاسترداد.

### 1.2 لماذا هو Domain مستقل

اتُّخذ قرار بناء المحفظة كـ **Package مستقل** داخل `packages/Webkul/Wallet/` للأسباب التالية:

| السبب | التفاصيل |
|---|---|
| **الفصل المعماري** | المحفظة كيان مالي مستقل له دورة حياة مختلفة عن دورة الطلب |
| **التوافق مع Bagisto** | كل Package في Bagisto يملك Service Provider + Routes + Models مستقلة |
| **قابلية الإزالة** | يمكن تعطيل المحفظة كاملاً بإزالة تسجيل الـ Provider دون تأثير على النظام الأساسي |
| **الاختبار المعزول** | الـ Tests الخاصة بالمحفظة مستقلة عن اختبارات Sales/Checkout |
| **نتيجة التدقيق** | لا يوجد في النظام الحالي أي Model مالي مرتبط بالعميل مباشرةً |

---

## 2. Business Rules

### BR-001: ربط المحفظة بالعميل

```
لكل عميل محفظة واحدة فقط.
المحفظة مرتبطة بـ Customer وليست بـ Channel.
لا يمكن نقل الرصيد بين محافظ مختلفة.
```

### BR-002: العملة

```
المحفظة تعمل بعملة واحدة رسمية هي base_currency للنظام.
جميع العمليات (إيداع / خصم / استرداد / سحب) تُسجَّل بنفس العملة.
لا يوجد تحويل صرف داخل المحفظة.
```

### BR-003: صلاحية الرصيد

```
الرصيد لا ينتهي (لا يوجد expiry_date).
الرصيد المُستلَم من Refund يُعامَل مثل الرصيد العادي تماماً.
لا فرق بين رصيد Top-Up ورصيد Refund في الاستخدام.
```

### BR-004: الدفع من المحفظة

```
السيناريو A — الرصيد يغطي الطلب كاملاً:
  [رصيد المحفظة] >= [قيمة الطلب]
  → يُخصَم المبلغ كاملاً من المحفظة.
  → لا حاجة لبوابة دفع خارجية.

السيناريو B — الدفع الجزئي:
  [رصيد المحفظة] < [قيمة الطلب]
  → يُحجَز كامل رصيد المحفظة المتاح.
  → المبلغ المتبقي = [قيمة الطلب] - [رصيد المحفظة]
  → يُطلب من العميل دفع المبلغ المتبقي عبر طريقة دفع أخرى.
  → عند نجاح الدفع الخارجي: يُحوَّل الحجز إلى خصم نهائي.
  → عند فشل الدفع الخارجي: يُلغى الحجز ويُعاد الرصيد للمتاح.
```

### BR-005: الطلبات ذات القيمة الصفرية

```
إذا كانت قيمة الطلب = 0 وكانت المحفظة هي طريقة الدفع المختارة:
  → تُسجَّل عملية دفع بقيمة 0.
  → يُعامَل الطلب كمدفوع بالكامل.
  → لا تُنشأ أي transaction فعلية في المحفظة.
```

### BR-006: Refund — الاسترداد للمحفظة دائماً

```
عند إنشاء Refund لأي طلب (بصرف النظر عن طريقة الدفع الأصلية):
  → المبلغ المُسترَد يُضاف للمحفظة مباشرةً.
  → لا يعود المال لبوابة الدفع الأصلية عبر هذا الـ Flow.
  → رصيد Refund متاح للاستخدام الفوري كرصيد عادي.

ملاحظة معمارية: هذا القرار يُلغي السلوك الحالي في Admin\Listeners\Refund::refundOrder()
الذي يُعيد المبلغ لـ PayPal تلقائياً. يجب مراجعة هذا السلوك عند التنفيذ.
```

### BR-007: Top-Up — الإيداع في المحفظة

```
العميل يبدأ طلب Top-Up بتحديد المبلغ وطريقة الدفع.
يُنشأ طلب Top-Up بحالة PENDING.
العميل يُتمّ الدفع عبر بوابة الدفع المختارة.
بعد نجاح الدفع: حالة Top-Up تصبح PAYMENT_RECEIVED.
الإدارة تراجع ثم توافق أو ترفض.
عند الموافقة: يُضاف الرصيد للمحفظة وحالة Top-Up تصبح APPROVED.
عند الرفض: يُعاد المال للعميل خارجياً وحالة Top-Up تصبح REJECTED.
الرصيد لا يُضاف للمحفظة إلا بعد الموافقة الإدارية.
```

### BR-008: Withdrawal — السحب من المحفظة

```
السحب يمكن تعطيله أو تفعيله من لوحة الإدارة.
إذا كان السحب معطَّلاً: لا يظهر خيار السحب للعميل.

عند طلب السحب:
  → يُحقَّق من أن رصيد المحفظة المتاح >= المبلغ المطلوب.
  → يُحجَز المبلغ فوراً.
  → يُنشأ طلب Withdrawal بحالة PENDING.

عند الموافقة والتنفيذ:
  → الإدارة تُسجِّل: رقم العملية البنكية، تاريخ التحويل، ملاحظات التنفيذ.
  → حالة الطلب: COMPLETED.
  → يُحوَّل الحجز إلى خصم نهائي.

عند الرفض:
  → حالة الطلب: REJECTED.
  → يُلغى الحجز ويُعاد المبلغ للرصيد المتاح.
```

### BR-009: قيود عامة

```
الرصيد لا ينتهي.
لا يوجد تحويل بين المحافظ.
لا يُسمح بالرصيد السالب (available_balance >= 0 دائماً).
```

---

## 3. Domain Entities

### 3.1 WalletAccount — حساب المحفظة

**الوصف:** يمثّل المحفظة الرئيسية للعميل. هو الكيان المركزي الذي يحمل الأرصدة.

**المسؤوليات:**
- تتبُّع الرصيد الكلي (total_balance)
- تتبُّع الرصيد المتاح (available_balance) = الكلي - المحجوز
- تتبُّع الرصيد المحجوز (held_balance) = مجموع الحجوزات النشطة
- التحقق من إمكانية الدفع أو السحب

**العلاقات:**
- ينتمي إلى Customer واحد (BelongsTo)
- يملك كثيراً من WalletTransactions (HasMany)
- يملك كثيراً من WalletTopUps (HasMany)
- يملك كثيراً من WalletWithdrawalRequests (HasMany)

**القاعدة الأساسية:**

```
total_balance = available_balance + held_balance
```

### 3.2 WalletTransaction — سجل الحركات المالية

**الوصف:** السجل الكامل لكل حركة مالية على المحفظة. هو مصدر الحقيقة الوحيد للرصيد.

**المسؤوليات:**
- تسجيل كل عملية (credit / debit / hold / release)
- الحفاظ على running_balance بعد كل حركة
- ربط الحركة بمصدرها (طلب / Refund / Top-Up / سحب)

**العلاقات:**
- ينتمي إلى WalletAccount (BelongsTo)
- مرتبط polymorphically بمصدر العملية (MorphTo): Order / Refund / WalletTopUp / WalletWithdrawalRequest

**ملاحظة:** هذا الجدول قراءة فقط بعد الإنشاء — لا يُعدَّل أي سجل بعد حفظه.

### 3.3 WalletTopUp — طلب الإيداع

**الوصف:** يمثّل طلب تعبئة رصيد المحفظة من بوابة دفع خارجية.

**دورة حياة الحالة:**

```
PENDING → PAYMENT_RECEIVED → APPROVED  (رصيد يُضاف)
                           → REJECTED  (مال يُعاد خارجياً)
```

**المسؤوليات:**
- تتبُّع المبلغ المطلوب وطريقة الدفع
- ربط عملية الدفع الخارجية (transaction_id من البوابة)
- الانتظار للموافقة الإدارية قبل إضافة الرصيد

**العلاقات:**
- ينتمي إلى WalletAccount (BelongsTo)
- تُنشأ عند الموافقة: WalletTransaction من النوع credit (HasOne)

### 3.4 WalletWithdrawalRequest — طلب السحب

**الوصف:** يمثّل طلب عميل لسحب رصيد من محفظته لحسابه البنكي.

**دورة حياة الحالة:**

```
PENDING → COMPLETED  (تنفيذ التحويل)
        → REJECTED   (رفض مع إعادة الحجز)
```

**المسؤوليات:**
- تتبُّع المبلغ المطلوب للسحب
- حجز المبلغ فور إنشاء الطلب
- تخزين بيانات الحساب البنكي الهدف
- استقبال بيانات التنفيذ من الإدارة

**العلاقات:**
- ينتمي إلى WalletAccount (BelongsTo)
- تُنشأ WalletTransaction من النوع hold عند الإنشاء
- تُنشأ WalletTransaction من النوع debit عند الإكمال
- تُنشأ WalletTransaction من النوع release عند الرفض

---

## 4. Database Design

### 4.1 جدول `wallet_accounts`

**الغرض:** تخزين بيانات المحفظة لكل عميل.

| العمود | النوع | القيود | الوصف |
|---|---|---|---|
| `id` | bigint unsigned | PK, AUTO_INCREMENT | المعرّف الأساسي |
| `customer_id` | int unsigned | FK, UNIQUE, NOT NULL | مالك المحفظة |
| `total_balance` | decimal(12,4) | NOT NULL, DEFAULT 0.0000 | إجمالي الرصيد |
| `available_balance` | decimal(12,4) | NOT NULL, DEFAULT 0.0000 | الرصيد القابل للاستخدام |
| `held_balance` | decimal(12,4) | NOT NULL, DEFAULT 0.0000 | الرصيد المحجوز مؤقتاً |
| `currency_code` | varchar(3) | NOT NULL | كود العملة (مثل: SAR) |
| `status` | varchar(20) | NOT NULL, DEFAULT 'active' | active / suspended |
| `created_at` | timestamp | NOT NULL | وقت الإنشاء |
| `updated_at` | timestamp | NOT NULL | وقت آخر تعديل |

**الفهارس والقيود:**

```
UNIQUE INDEX:   customer_id
FOREIGN KEY:    customer_id → customers.id  (ON DELETE RESTRICT)
INDEX:          status
CHECK:          available_balance >= 0
CHECK:          held_balance >= 0
CHECK:          total_balance = available_balance + held_balance
```

---

### 4.2 جدول `wallet_transactions`

**الغرض:** سجل ثابت لكل حركة مالية على المحفظة.

| العمود | النوع | القيود | الوصف |
|---|---|---|---|
| `id` | bigint unsigned | PK, AUTO_INCREMENT | المعرّف الأساسي |
| `wallet_id` | bigint unsigned | FK, NOT NULL | المحفظة المرتبطة |
| `type` | varchar(20) | NOT NULL | credit / debit / hold / release |
| `amount` | decimal(12,4) | NOT NULL | قيمة العملية (موجبة دائماً) |
| `running_balance` | decimal(12,4) | NOT NULL | رصيد available بعد هذه العملية |
| `description` | varchar(500) | NULLABLE | وصف العملية |
| `reference_type` | varchar(100) | NULLABLE | نوع المصدر Polymorphic |
| `reference_id` | bigint unsigned | NULLABLE | معرّف المصدر |
| `meta` | json | NULLABLE | بيانات إضافية |
| `created_at` | timestamp | NOT NULL | وقت الإنشاء |
| `updated_at` | timestamp | NOT NULL | (محفوظ للتوافق) |

**الفهارس والقيود:**

```
FOREIGN KEY:    wallet_id → wallet_accounts.id  (ON DELETE RESTRICT)
INDEX:          wallet_id, created_at
INDEX:          reference_type, reference_id
INDEX:          type
```

> **قاعدة أمان:** لا يُسمح بـ UPDATE أو DELETE على هذا الجدول. الأخطاء تُصحَّح بإدخال سجل تصحيح عكسي جديد.

---

### 4.3 جدول `wallet_topups`

**الغرض:** تتبُّع طلبات إيداع الرصيد من البوابات الخارجية.

| العمود | النوع | القيود | الوصف |
|---|---|---|---|
| `id` | bigint unsigned | PK, AUTO_INCREMENT | المعرّف الأساسي |
| `wallet_id` | bigint unsigned | FK, NOT NULL | المحفظة المستفيدة |
| `amount` | decimal(12,4) | NOT NULL | المبلغ المراد إيداعه |
| `currency_code` | varchar(3) | NOT NULL | كود العملة |
| `payment_method` | varchar(100) | NULLABLE | طريقة الدفع المستخدمة |
| `payment_transaction_id` | varchar(255) | NULLABLE, UNIQUE | معرّف العملية في البوابة |
| `status` | varchar(30) | NOT NULL, DEFAULT 'pending' | pending / payment_received / approved / rejected |
| `admin_user_id` | int unsigned | FK → admins.id, NULLABLE | الإداري صاحب القرار |
| `admin_notes` | text | NULLABLE | ملاحظات الإدارة |
| `approved_at` | timestamp | NULLABLE | وقت اتخاذ القرار |
| `meta` | json | NULLABLE | بيانات البوابة الإضافية |
| `created_at` | timestamp | NOT NULL | وقت إنشاء الطلب |
| `updated_at` | timestamp | NOT NULL | وقت آخر تحديث |

**الفهارس والقيود:**

```
FOREIGN KEY:    wallet_id → wallet_accounts.id  (ON DELETE RESTRICT)
FOREIGN KEY:    admin_user_id → admins.id  (ON DELETE SET NULL)
UNIQUE INDEX:   payment_transaction_id  (إن وُجد)
INDEX:          wallet_id, status
INDEX:          status, created_at
```

---

### 4.4 جدول `wallet_withdrawal_requests`

**الغرض:** تتبُّع طلبات سحب الرصيد من المحفظة لحسابات خارجية.

| العمود | النوع | القيود | الوصف |
|---|---|---|---|
| `id` | bigint unsigned | PK, AUTO_INCREMENT | المعرّف الأساسي |
| `wallet_id` | bigint unsigned | FK, NOT NULL | المحفظة المصدر |
| `amount` | decimal(12,4) | NOT NULL | المبلغ المطلوب سحبه |
| `currency_code` | varchar(3) | NOT NULL | كود العملة |
| `status` | varchar(30) | NOT NULL, DEFAULT 'pending' | pending / completed / rejected |
| `bank_details` | json (encrypted) | NOT NULL | بيانات الحساب البنكي |
| `admin_user_id` | int unsigned | FK → admins.id, NULLABLE | الإداري المُنفِّذ |
| `bank_transaction_reference` | varchar(255) | NULLABLE | رقم العملية البنكية |
| `transferred_at` | timestamp | NULLABLE | تاريخ التحويل الفعلي |
| `admin_notes` | text | NULLABLE | ملاحظات التنفيذ |
| `rejected_at` | timestamp | NULLABLE | وقت الرفض |
| `rejection_reason` | varchar(500) | NULLABLE | سبب الرفض |
| `created_at` | timestamp | NOT NULL | وقت إنشاء الطلب |
| `updated_at` | timestamp | NOT NULL | وقت آخر تحديث |

**الفهارس والقيود:**

```
FOREIGN KEY:    wallet_id → wallet_accounts.id  (ON DELETE RESTRICT)
FOREIGN KEY:    admin_user_id → admins.id  (ON DELETE SET NULL)
INDEX:          wallet_id, status
INDEX:          status, created_at
INDEX:          created_at
```

---

### 4.5 خريطة العلاقات

```
customers (1) ─────────── (1) wallet_accounts
                                     │
                   ┌─────────────────┼──────────────────┐
                   │                 │                  │
          wallet_transactions   wallet_topups   wallet_withdrawal_requests
                   │
          (polymorphic reference)
                   │
       ┌───────────┴──────────────────────┐
       │                                  │
     orders                            refunds
  (debit / hold / release)            (credit)
```

---

## 5. Wallet Transaction Types

### 5.1 تعريف الأنواع

| النوع | التأثير على available_balance | التأثير على held_balance | متى يُستخدَم |
|---|---|---|---|
| `credit` | زيادة (+) | لا تغيير | إضافة رصيد — Top-Up / Refund |
| `debit` | نقصان (-) | لا تغيير | خصم رصيد — دفع طلب مكتمل |
| `hold` | نقصان (-) | زيادة (+) | حجز مبلغ — Partial Payment / Withdrawal |
| `release` | زيادة (+) | نقصان (-) | إلغاء حجز — فشل الدفع / رفض السحب |

### 5.2 مثال credit — Refund يصل للمحفظة

```
قبل العملية:  available = 50.00 | held = 0.00 | total = 50.00
العملية:      credit +75.00  (مصدر: Refund #1042)
بعد العملية: available = 125.00 | held = 0.00 | total = 125.00

سجل الـ Transaction:
  type            = credit
  amount          = 75.0000
  running_balance = 125.0000
  reference_type  = Webkul\Sales\Models\Refund
  reference_id    = 1042
  description     = Refund for Order #ORD-2026-0891
```

### 5.3 مثال debit — دفع طلب كاملاً بالمحفظة

```
قبل العملية:  available = 200.00 | held = 0.00 | total = 200.00
العملية:      debit -150.00  (مصدر: Order #1105)
بعد العملية: available = 50.00 | held = 0.00 | total = 50.00

سجل الـ Transaction:
  type            = debit
  amount          = 150.0000
  running_balance = 50.0000
  reference_type  = Webkul\Sales\Models\Order
  reference_id    = 1105
  description     = Payment for Order #ORD-2026-1105
```

### 5.4 مثال hold — دفع جزئي (المحفظة + بطاقة)

```
قبل العملية:  available = 80.00 | held = 0.00 | total = 80.00
الطلب:        قيمته 200.00
العملية:      hold -80.00  (كامل الرصيد المتاح)
بعد العملية: available = 0.00 | held = 80.00 | total = 80.00
              العميل يُكمل 120.00 بالبطاقة

سجل الـ Transaction:
  type            = hold
  amount          = 80.0000
  running_balance = 0.0000
  reference_type  = Webkul\Sales\Models\Order
  reference_id    = 1110
  description     = Hold for partial payment — Order #ORD-2026-1110
```

### 5.5 مثال release — إلغاء حجز بعد فشل الدفع الخارجي

```
قبل العملية:  available = 0.00 | held = 80.00 | total = 80.00
(فشل دفع البطاقة للـ 120.00 المتبقية)
العملية:      release +80.00
بعد العملية: available = 80.00 | held = 0.00 | total = 80.00

سجل الـ Transaction:
  type            = release
  amount          = 80.0000
  running_balance = 80.0000
  reference_type  = Webkul\Sales\Models\Order
  reference_id    = 1110
  description     = Release hold — external payment failed for Order #ORD-2026-1110
```

### 5.6 مثال hold + debit — طلب سحب

```
[عند إنشاء الطلب]
قبل:  available = 300.00 | held = 0.00  | total = 300.00
hold: available = 100.00 | held = 200.00 | total = 300.00

[عند إكمال السحب]
debit: available = 100.00 | held = 0.00 | total = 100.00
```

---

## 6. Wallet Lifecycle

### 6.1 إنشاء المحفظة

```
الحالة الافتراضية عند الإنشاء:
  status            = active
  total_balance     = 0.0000
  available_balance = 0.0000
  held_balance      = 0.0000
  currency_code     = base_currency للنظام

راجع OQ-001 لتحديد متى تُنشأ المحفظة (proactive أم lazy).
```

### 6.2 Top-Up Lifecycle

```
[1] العميل يختار مبلغ الإيداع وطريقة الدفع
         |
         ↓
[2] إنشاء WalletTopUp  (status = PENDING)
         |
         ↓
[3] توجيه العميل لبوابة الدفع
         |
         ├──→ [فشل الدفع] → WalletTopUp يبقى PENDING أو يُلغى
         |
         ↓
[4] بوابة الدفع تُؤكد (Webhook / Callback)
    WalletTopUp  (status = PAYMENT_RECEIVED)
    يُسجَّل payment_transaction_id
         |
         ↓
[5] الإدارة تراجع الطلب
         |
         ├──→ [REJECTED]:
         |      WalletTopUp  (status = REJECTED)
         |      الإدارة تُعيد المال خارجياً
         |
         ↓
[6] [APPROVED]:
    WalletTopUp  (status = APPROVED)
    WalletTransaction  (type = credit)
    available_balance += amount
    total_balance     += amount
```

### 6.3 Payment Lifecycle

```
السيناريو A — الرصيد يكفي:

[1] العميل يختار المحفظة كطريقة دفع
         |
[2] التحقق: available_balance >= order_grand_total → نعم
         |
[3] عند تأكيد الطلب (checkout.order.save.after):
    WalletTransaction  (type = debit)
    available_balance -= order_grand_total
    total_balance     -= order_grand_total
         |
[4] الطلب يكمل مساره الطبيعي

────────────────────────────────────────────

السيناريو B — الدفع الجزئي:

[1] العميل يختار المحفظة + طريقة دفع أخرى
         |
[2] التحقق: available_balance < order_grand_total
    المبلغ المُحجوز  = available_balance (كاملاً)
    المبلغ المتبقي   = order_grand_total - available_balance
         |
[3] WalletTransaction  (type = hold)
    available_balance = 0
    held_balance     += wallet_portion
         |
[4] العميل يدفع المبلغ المتبقي عبر بوابة أخرى
         |
         ├──→ [فشل الدفع الخارجي]:
         |      WalletTransaction  (type = release)
         |      الحجز يُلغى، الرصيد يعود متاحاً
         |
         ↓
[5] [نجاح الدفع الخارجي]:
    WalletTransaction  (type = debit)
    held_balance  = 0
    total_balance -= wallet_portion
    الطلب يكمل مساره الطبيعي

────────────────────────────────────────────

السيناريو C — طلب بقيمة صفر:

[1] available_balance >= 0  (الشرط دائماً محقق)
[2] لا يُنشأ WalletTransaction
[3] الطلب يُنشأ ويُعامَل كمدفوع
```

### 6.4 Refund Lifecycle

```
[1] الإدارة تُنشئ Refund لطلب ما
         |
[2] sales.refund.save.after يُطلَق
         |
[3] Wallet Listener يستقبل الحدث
         |
[4] الحصول على WalletAccount للعميل
    (إنشاؤها إن لم تكن موجودة — راجع OQ-001)
         |
[5] WalletTransaction  (type = credit)
    amount          = refund.grand_total
    reference_type  = Webkul\Sales\Models\Refund
    reference_id    = refund.id
         |
[6] available_balance += refund.grand_total
    total_balance     += refund.grand_total
         |
[7] إشعار العميل
```

### 6.5 Withdrawal Lifecycle

```
[مسبقاً] التحقق من إعداد النظام: هل السحب مُفعَّل؟
  → لا: الخيار مخفي عن العميل

[1] العميل يُدخل المبلغ وبيانات الحساب البنكي
         |
[2] التحقق: available_balance >= المبلغ المطلوب؟
         |
         ├──→ [لا] → رسالة خطأ
         |
         ↓
[3] [نعم]:
    WalletTransaction  (type = hold)
    available_balance -= amount
    held_balance      += amount
    WalletWithdrawalRequest  (status = PENDING)
         |
[4] الإدارة تراجع الطلب
         |
         ├──→ [REJECTED]:
         |      WalletTransaction  (type = release)
         |      held_balance      -= amount
         |      available_balance += amount
         |      WalletWithdrawalRequest  (status = REJECTED)
         |      يُحفظ: rejection_reason, rejected_at
         |
         ↓
[5] الإدارة تُنفِّذ التحويل البنكي خارج النظام
    ثم تُسجِّل في النظام:
      bank_transaction_reference
      transferred_at
      admin_notes
         |
[6] WalletTransaction  (type = debit)
    held_balance  -= amount
    total_balance -= amount
    WalletWithdrawalRequest  (status = COMPLETED)
         |
[7] إشعار العميل
```

---

## 7. Integration Points With Bagisto

### 7.1 ربط Customer

| نقطة الربط | التفاصيل |
|---|---|
| **الجدول** | customers.id هو المفتاح الأجنبي في wallet_accounts.customer_id |
| **حذف العميل** | ON DELETE RESTRICT — لا يمكن حذف عميل لديه محفظة برصيد |
| **Customer Model** | إضافة wallet() HasOne relationship |
| **حدث التسجيل** | customer.create.after — لإنشاء المحفظة (راجع OQ-001) |

### 7.2 ربط Order

| نقطة الربط | التفاصيل |
|---|---|
| **Checkout Flow** | إضافة Wallet كـ Payment Method عبر payment_methods config |
| **حدث إنشاء الطلب** | checkout.order.save.after — تنفيذ الـ debit أو تحويل hold إلى debit |
| **حدث إلغاء الطلب** | sales.order.cancel.after — إضافة رصيد إذا كان الطلب مدفوعاً بالمحفظة |
| **الطلب بقيمة صفر** | يُعامَل كمدفوع بالمحفظة دون إنشاء transaction فعلية |
| **OrderPayment** | يُحفظ method = wallet أو method = wallet_partial |

### 7.3 ربط Refund

| نقطة الربط | التفاصيل |
|---|---|
| **الحدث الرئيسي** | sales.refund.save.after — إضافة رصيد المحفظة |
| **السلوك المتعارض** | Admin\Listeners\Refund::refundOrder() يُعيد المال لـ PayPal — يجب مراجعته |
| **المبلغ المُضاف** | refund.grand_total بالـ base_currency |

### 7.4 ربط Payment

| نقطة الربط | التفاصيل |
|---|---|
| **الفئة الأساسية** | WalletPayment ترث من Webkul\Payment\Payment\Payment |
| **التسجيل** | إضافة في payment_methods config عبر mergeConfigFrom |
| **isAvailable()** | يتحقق من: المحفظة مُفعَّلة + العميل مُسجَّل دخول + رصيد > 0 |
| **getRedirectUrl()** | يُعيد null — الدفع فوري لا يحتاج Redirect |
| **Top-Up** | Flow مستقل — لا يمر عبر Cart/Checkout الأصلي |

### 7.5 الأحداث

**الاستماع (Listen):**

| الحدث | الاستجابة |
|---|---|
| customer.create.after | إنشاء المحفظة (حسب OQ-001) |
| checkout.order.save.after | تنفيذ debit أو hold |
| sales.order.cancel.after | إعادة رصيد إذا كان الطلب مدفوعاً بالمحفظة |
| sales.refund.save.after | إضافة مبلغ الـ Refund للمحفظة |

**الإطلاق (Dispatch):**

| الحدث | متى |
|---|---|
| wallet.topup.approved | بعد موافقة الإدارة على الإيداع |
| wallet.withdrawal.completed | بعد إكمال السحب |
| wallet.withdrawal.rejected | بعد رفض السحب |
| wallet.credited | بعد كل عملية credit |
| wallet.debited | بعد كل عملية debit |

### 7.6 ربط Admin Panel

| المكوّن | التفاصيل |
|---|---|
| **Menu** | إلغاء تعليق قسم Wallet في menu.php |
| **ACL** | إضافة: wallet / wallet.topup.manage / wallet.withdrawal.manage |
| **Routes** | توسيع wallet-routes.php الحالي |
| **DataGrids** | Customer Wallets / Transactions / TopUps / Withdrawals |
| **Settings** | إضافة sales.wallet.enable_withdrawal في system.php |
| **Translations** | إضافة مفاتيح en/app.php (الإنجليزية غائبة حالياً) |

---

## 8. Security Considerations

### 8.1 Race Conditions — التزامن المتزامن

**المشكلة:** عميل يفتح نافذتين ويحاول الدفع في نفس الوقت → خصم مزدوج أو رصيد سالب.

**الحل المطلوب:**

```
كل عملية تعديل رصيد يجب أن:
  1. تبدأ بـ DB Transaction
  2. تُقفل wallet_accounts باستخدام SELECT ... FOR UPDATE
  3. تتحقق من الرصيد داخل نفس الـ DB Transaction المُقفَّلة
  4. تُنشئ wallet_transactions داخل نفس الـ DB Transaction
  5. تُكمل أو تُلغي الـ DB Transaction atomically

لا تقرأ available_balance خارج DB Transaction مُقفَّلة قبل عملية خصم.
```

### 8.2 Database Locking — استراتيجية القفل

```
عمليات الكتابة (debit / hold):
  SELECT wallet_accounts WHERE id = ? FOR UPDATE
  التحقق من الرصيد داخل الـ Transaction
  الكتابة ثم COMMIT

عمليات القراءة (عرض الرصيد للعميل):
  SELECT عادي — لا قفل مطلوب

Deadlock Prevention:
  دائماً اقفل wallet_accounts أولاً قبل أي جدول آخر في نفس الـ Transaction
  استخدم Retry Logic عند حدوث Deadlock
```

### 8.3 Audit Trail — سجل التدقيق

```
مبدأ Immutability لـ wallet_transactions:
  لا يُحذف أي سجل.
  لا يُعدَّل أي سجل بعد إنشائه.
  الأخطاء تُصحَّح بإدخال سجل تصحيح عكسي (reversal entry).

سجل المدقق للعمليات الإدارية:
  كل تغيير حالة في wallet_topups يُسجَّل مع:
    - admin_user_id (من قام بالتغيير)
    - approved_at / rejected_at (وقت القرار)
    - admin_notes (سبب القرار)

  كل تغيير حالة في wallet_withdrawal_requests يُسجَّل مع:
    - admin_user_id
    - bank_transaction_reference
    - transferred_at
    - admin_notes
```

### 8.4 Permission Control — التحكم في الصلاحيات

**صلاحيات العميل (Shop):**

```
wallet.view       → عرض رصيده وحركاته
wallet.topup      → إنشاء طلب إيداع
wallet.withdraw   → إنشاء طلب سحب (إذا كان السحب مُفعَّلاً)
wallet.pay        → استخدام المحفظة في الدفع
```

**صلاحيات الإدارة (Admin ACL):**

```
wallet                    → عرض قائمة المحافظ
wallet.topup.view         → عرض طلبات الإيداع
wallet.topup.approve      → الموافقة على طلبات الإيداع
wallet.topup.reject       → رفض طلبات الإيداع
wallet.withdrawal.view    → عرض طلبات السحب
wallet.withdrawal.process → تنفيذ السحب وإدخال بيانات التحويل
wallet.withdrawal.reject  → رفض السحب
```

**قاعدة حرجة:**

```
أي تعديل على wallet_accounts.available_balance
يجب أن يمر عبر WalletService فقط.
لا يجوز تعديل الرصيد مباشرةً من Controller أو Repository.
WalletService هو الوحيد المخوَّل بإنشاء wallet_transactions.
```

### 8.5 بيانات السحب البنكية

```
bank_details يُخزَّن كـ JSON مع encrypted cast في قاعدة البيانات.
لا تُعرَض البيانات كاملةً إلا للإدارة المعنية.
في واجهة العميل: يُعرَض IBAN مُقنَّع (مثال: SA**********1234).
بيانات bank_details لا تُسجَّل في application logs.
```

---

## 9. Open Questions

الأسئلة التالية تحتاج قراراً قبل بدء التنفيذ:

---

### ~~OQ-001~~: متى تُنشأ المحفظة؟ — **محسوم ✅**

```
القرار المتخذ: PROACTIVE — عند تسجيل العميل

التنفيذ:
  الاستماع على حدث customer.create.after
  → إنشاء WalletAccount فوراً لكل عميل جديد
  → status = active, balance = 0.0000
  → يشمل: تسجيل Shop + إنشاء من Admin
```

---

### OQ-002: إلغاء الطلب المدفوع بالمحفظة

```
الطلب مدفوع كاملاً بالمحفظة ثم يُلغى قبل الإنجاز.
هل يُضاف المبلغ للمحفظة فوراً عند حدث sales.order.cancel.after؟
أم يُتبَع Flow الـ Refund الرسمي عبر الإدارة؟
```

---

### OQ-003: الدفع بالمحفظة للزوار

```
الزوار ليس لديهم حساب ولا محفظة.
المرجَّح: المحفظة خيار دفع للمسجَّلين فقط.
هل يلزم تأكيد رسمي على هذا القرار؟
```

---

### OQ-004: حدود السحب

```
هل هناك حد أقصى لمبلغ طلب السحب الواحد؟
هل هناك حد أقصى لعدد طلبات السحب في الفترة الزمنية؟
```

---

### OQ-005: سلوك الـ Refund عند تعارضه مع PayPal

```
القرار المتخذ: جميع Refunds تذهب للمحفظة.
التعارض الموجود: Admin\Listeners\Refund::refundOrder() يُعيد المال لـ PayPal.
السؤال: هل يُعطَّل سلوك PayPal كلياً؟
        أم يُحتفظ به لحالات معينة خارج نطاق المحفظة؟
```

---

### OQ-006: الحدود الدنيا للإيداع والسحب

```
هل هناك مبلغ أدنى لطلب Top-Up؟
هل هناك مبلغ أدنى لطلب السحب؟
```

---

### OQ-007: قنوات الإشعارات

```
ما القنوات المطلوبة؟
  - البريد الإلكتروني فقط
  - إشعارات داخل الموقع
  - كلاهما

ما الأحداث التي تستوجب إشعاراً للعميل؟
  - إضافة رصيد (credit)
  - خصم رصيد (debit)
  - موافقة / رفض Top-Up
  - إكمال / رفض السحب
```

---

*نهاية الوثيقة — الإصدار 1.0 — HIGEST Wallet Domain Specification*