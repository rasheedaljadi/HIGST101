# HIGEST Wallet Frontend Implementation Specification v1.0
## Executive Technical Execution Document for Frontend & Blade/Vue Components

---

> **المرجع الأساسي:** HIGEST Wallet UI/UX Specification V1.0 (معتمدة 2026-08-03)
> **الوثائق المساندة:** `WALLET_DOMAIN_SPECIFICATION.md` | `WALLET_TECHNICAL_IMPLEMENTATION_SPEC.md`
> **إطار العمل:** Bagisto 2.4.x / Laravel 12 (Blade + Alpine.js + Vue 3)
> **موقع الحزمة:** `packages/Webkul/Wallet/src/Resources/views/`
> **نوع الوثيقة:** Frontend Implementation Specification — وثيقة التنفيذ المباشر للمطورين
> **تاريخ الإصدار:** 2026-08-03

---

## 1. Overview & Architectural Standards

هذه الوثيقة تُمثّل **مخطط التنفيذ التفصيلي (Execution Blueprint)** لواجهات نظام **HIGEST Wallet**. تحتوي الوثيقة على المواصفات البرمجية والتعاقدات الخاصة بكل شاشة ومكون (Blade/Vue Component) لكل من واجهة الإدارة (**Admin Financial Operations**) وواجهة العميل (**Customer Wallet Experience**).

### 1.1 معايير التصميم البرمجي والمسارات

| العنصر | المواصفة / المسار |
|---|---|
| **مكان المكونات (Admin)** | `packages/Webkul/Wallet/src/Resources/views/admin/` |
| **مكان المكونات (Shop)** | `packages/Webkul/Wallet/src/Resources/views/shop/` |
| **مسارات CSS/Assets** | `packages/Webkul/Wallet/src/Resources/assets/` |
| **محرك الواجهات** | Blade Templates + Alpine.js (للتفاعلات الخفيفة) + Vue 3 (للمكونات المعقدة كـ Timeline والـ Modals) |
| **التوافق اللغوي** | دعم كامل لـ 21 لغة ببرمجة مفاتيح الترجمة عبر `trans('wallet::app...')` |
| **أنماط الاستجابة** | Fully Responsive (Mobile-First للعميل، Desktop-First مع دعم Tablet للإدارة) |

---

## 2. Admin Financial Operations Interface Specifications

---

### 2.1 Wallet Dashboard

- **الهدف**: الشاشة الرئيسة للإدارة المالية للرصيد والمخاطر التشغيلية.
- **View Name**: `wallet::admin.dashboard.index`
- **Route**: `GET /admin/wallet/dashboard` (`admin.wallet.dashboard.index`)
- **Controller Data Required**:
  ```php
  [
      'totalLiability'          => float, // مجموع الأرصدة المتاحة للعملاء
      'availableBalance'        => float, // إجمالي السيولة الجاهزة للاستخدام
      'heldBalance'             => float, // المبالغ المحجوزة في طلبات السحب
      'pendingWithdrawalsCount' => int,   // عدد طلبات السحب المعلقة
      'failedRefundCreditsCount'=> int,   // عدد عمليات الاسترداد الفاشلة
      'pendingTopUpsCount'      => int,   // عدد عمليات الإيداع بانتظار التحقق
      'failedWebhooksCount'     => int,   // عدد الـ Webhooks الفاشلة
      'recentActivity'          => Collection, // آخر 10 معاملات في النظام
  ]
  ```
- **Permissions**: `admin.wallet.dashboard.view`

#### المكونات النموذجية (Blade Components)

1. **`x-wallet-kpi-card`**:
   - يُستخدم لعرض المؤشرات الرئيسية (Liability, Available, Held, Pending Withdrawals).
   - Props: `title`, `amount`, `currency`, `icon`, `trend`, `colorScheme`.
2. **`x-wallet-failed-ops-widget`** *(إضافة مراجعة V1.0)*:
   - **الوصف**: كارت مخصص للتنبيه الفوري بالعمليات المالية الفاشلة التي تتطلب تدخل فريق التشغيل.
   - **العناصر البصرية**:
     ```html
     ┌────────────────────────────────────────────────────────┐
     │ ⚠️ Failed Financial Operations                         │
     ├────────────────────────────────────────────────────────┤
     │ Failed Refund Credits : 3   [View Details]             │
     │ Pending TopUps        : 5   [Review List]              │
     │ Failed Webhooks       : 1   [Retry Webhooks]           │
     └────────────────────────────────────────────────────────┘
     ```
3. **`x-wallet-recent-transactions-table`**:
   - عرض المعاملات الأخيرة مع شارات ملونة للحالة والنوع.

#### حالات UX
- **Loading State**: Shimmer Skeletons لبطاقات الـ KPIs والـ Failed Operations Widget.
- **Empty State**: في حالة عدم وجود معاملات، ظهور `x-wallet-empty-state` مع نص "لا توجد نشاطات مالية مؤخراً".

---

### 2.2 Customer Wallet Details

- **الهدف**: مرجع الدعم الفني والمحاسبة لمتابعة حساب عميل محدد.
- **View Name**: `wallet::admin.customers.show`
- **Route**: `GET /admin/wallet/customers/{customer_id}` (`admin.wallet.customers.show`)
- **Controller Data Required**:
  ```php
  [
      'customer'            => CustomerContract,
      'wallet'              => WalletAccountContract,
      'heldBalanceDetails'  => Collection, // التفاصيل المسببة للحجز
      'timelineEvents'      => LengthAwarePaginator, // سجل المعاملات كرواية زمنية
  ]
  ```
- **Permissions**: `admin.wallet.customers.view`

#### المكونات النموذجية (Blade / Alpine Components)

1. **`x-wallet-customer-summary`**:
   - إحصائيات الحساب: الرصيد الكلي، الرصيد المتاح، الرصيد المحجوز، تجميد الحساب/تفعيله.
2. **`x-wallet-timeline`** *(إضافة مراجعة V1.0 - بديل السجل التقليدي)*:
   - **الوصف**: مكوّن زمني رأسي (Vertical Narrative Timeline) يوضح تسلسل الحركات المالية للحساب لم مساعدة الدعم الفني على فهم قصة الحساب بدلاً من قراءة جدول جامد.
   - **الهيكل البصري**:
     ```text
     03 Aug 2026
     │
     ├── 🟢 Refund +$100.00
     │   Order #1001 • System Auto-Refund
     │   Ref: TXN-882193 • 14:32
     │
     ├── 🔴 Purchase -$50.00
     │   Order #1005 • Wallet Payment
     │   Ref: TXN-882201 • 16:15
     │
     └── 🟣 Cashback +$5.00
         Campaign #PROMO-SUMMER • Auto-Credit
         Ref: TXN-882245 • 18:00
     ```
   - **Props**: `events` (Array of objects: `date`, `type`, `amount`, `formatted_amount`, `reference_type`, `reference_id`, `description`, `icon`, `color`).

---

### 2.3 Withdrawal Management

- **الهدف**: إدارة ومراجعة طلبات سحب الرصيد من قبل الإدارة المالية.
- **View Name**: `wallet::admin.withdrawals.index` & `wallet::admin.withdrawals.show`
- **Route**: `GET /admin/wallet/withdrawals` (`admin.wallet.withdrawals.index`)
- **Controller Data Required**: `DataGrid` instance derived from `Webkul\Wallet\DataGrids\Admin\WithdrawalRequestDataGrid`.
- **Permissions**: `admin.wallet.withdrawals.manage`

#### المكونات النموذجية

1. **`x-withdrawal-risk-indicator`** *(إضافة مراجعة V1.0)*:
   - **الوصف**: مؤشر تقييم المخاطر يظهر قبل الموافقة على طلب السحب لتنبيه مسؤول الصرف.
   - **الهيكل البصري**:
     ```html
     ┌────────────────────────────────────────────────────────┐
     │ 🛡️ Withdrawal Risk Indicator                           │
     ├────────────────────────────────────────────────────────┤
     │ Risk Level: [ 🟡 MEDIUM RISK ]                         │
     │                                                        │
     │ Evaluated Risk Factors:                                │
     │ ⚠️ New Account (Account created 3 days ago)            │
     │ ⚠️ Large Withdrawal Request ($300.00 >= Threshold)      │
     │ ✓ Recent TopUp Verified ($300.00 via Bank Transfer)    │
     └────────────────────────────────────────────────────────┘
     ```
   - **Props**: `riskLevel` ('low', 'medium', 'high'), `reasons` (array of string checks).

2. **Withdrawal Action Modals**:
   - **`CompleteWithdrawalModal`**: يتطلب ادخال رقم المرجع البنكي / الإيصال البنكي `bank_transfer_ref`.
   - **`RejectWithdrawalModal`**: يتطلب ادخال سبب الرفض المباشر للعميل `rejection_reason`.

---

### 2.4 Adjustment Interface (شاشة التعديل اليدوي)

- **الهدف**: إضافة أو خصم رصيد يدوياً من قبل الأدمن لحالات التعويض أو التصحيح.
- **View Name**: `wallet::admin.adjustments.create` (أو مكوّن Modal شاشة تفاصيل العميل).
- **Route**: `POST /admin/wallet/adjustments` (`admin.wallet.adjustments.store`)
- **Permissions**: `admin.wallet.adjustments.create`

#### 2-Step Confirmation Flow *(إضافة مراجعة V1.0 الصارمة)*

نظراً لأن الخطأ مالي وليس تجميلي، يتطلب النموذج المرور بخطوتين قبل الإرسال:

1. **Step 1: Input Form**:
   - اختيار نوع التعديل (`Credit` / `Debit`).
   - ادخال المبلغ `amount`.
   - اختيار سبب التعديل `reason` (dropdown + نص تفصيلي).

2. **Step 2: Confirmation Modal (الصارم)**:
   - عند الضغط على "Submit", يتم فتح نموذج مراجعة غير قابل للتعديل يمنع الأخطاء البشرية:
   ```text
   ┌───────────────────────────────────────────────────────────┐
   │ ⚠️ CONFIRM FINANCIAL ADJUSTMENT                           │
   ├───────────────────────────────────────────────────────────┤
   │ Action:           ADD +$100.00 (CREDIT)                   │
   │ Target Wallet:    Ahmed Mohammed (ID: #4502)             │
   │ Current Balance:  $150.00                                 │
   │ New Balance:      $250.00                                 │
   │ Reason:           Customer Compensation (Ticket #991)     │
   ├───────────────────────────────────────────────────────────┤
   │ [ Cancel ]                       [ CONFIRM ADJUSTMENT ]  │
   └───────────────────────────────────────────────────────────┘
   ```

---

## 3. Customer Wallet Experience Specifications

---

### 3.1 Wallet Overview

- **الهدف**: واجهة العميل الرئيسية لعرض الأرصدة والإجراءات السريعة.
- **View Name**: `wallet::shop.customers.account.wallet.index`
- **Route**: `GET /customer/account/wallet` (`shop.customers.account.wallet.index`)
- **Controller Data Required**:
  ```php
  [
      'availableBalance'   => float, // قابل للشراء وسحب البنك
      'promotionalBalance' => float, // رصيد ترويجي/ترجيحي (غير قابل للسحب)
      'heldBalance'        => float, // رصيد قيد طلب سحب
      'currency'           => string,
      'recentTransactions' => Collection,
  ]
  ```

#### الفصل البصري للأرصدة *(إضافة مراجعة V1.0)*

يجب عدم خلط الرصيد الترويجي بالرصيد المتاح لمنع العميل من الانخداع بقابليتها للسحب البنكي.

```html
┌───────────────────────────────────────┐ ┌───────────────────────────────────────┐
│ Available Balance                     │ │ Promotional Balance                   │
│ $250.00                               │ │ $20.00                                │
│ 🟢 Usable for Purchase & Withdrawal   │ │ 🎁 Store Credit Only (Non-Withdrawable)│
└───────────────────────────────────────┘ └───────────────────────────────────────┘
```

#### الإجراءات السريعة (`x-quick-action-bar`)
- زر **"Top Up Wallet"** (إيداع رصيد).
- زر **"Request Withdrawal"** (سحب رصيد).
- زر **"View Statement"** (كشف حساب).

---

### 3.2 Transaction History

- **الهدف**: عرض السجل التجاري والتنقيب في المعاملات مع دعم البحث.
- **View Name**: `wallet::shop.customers.account.wallet.transactions`
- **Route**: `GET /customer/account/wallet/transactions`
- **Controller Data Required**: `transactions` (Paginator).

#### شريط البحث والتصفية *(إضافة مراجعة V1.0)*
- **Search Input**: يدعم البحث برقم الطلب (Order #1001), نوع المعاملة (Refund, TopUp, Purchase), أو مرجع العملية.
- **Type Filter Dropdown**: الكل / إيداعات / سحوبات / استرداد / مشتريات.
- **Date Range Picker**: تصفية حسب التاريخ.

#### تنزيل الإيصال (`Download Receipt`) *(إضافة مراجعة V1.0)*
- لكل معاملة مفردة (خاصة بالاستردادات والإيداعات الكبيرة), تظهر أيقونة/زر: `Download Receipt` (`x-receipt-download-button`).
- المسار: `GET /customer/account/wallet/transactions/{id}/receipt`.

---

### 3.3 Top-Up Flow

- **الهدف**: شاشة إيداع الرصيد عبر بوابة الدفع.
- **View Name**: `wallet::shop.customers.account.wallet.topup`
- **Route**: `GET /customer/account/wallet/topup`, `POST /customer/account/wallet/topup/process`

#### حالات التدفق وحالة التحقق من الدفع *(إضافة مراجعة V1.0)*
1. **اختيار المبلغ**: مبالغ مسبقة الاختيار ($20, $50, $100) + مدخل حر.
2. **اختيار طريقة الدفع**: بوابات الدفع المتاحة.
3. **حالة التحقق المعالجة (`Payment Processing State`)**:
   - للبوابات التي تحتاج تأكيداً asynchronously (مثل تحويل بنكي/سداد/Webhooks):
   ```html
   ┌────────────────────────────────────────────────────────┐
   │ ⏳ Payment Verification In Progress                    │
   ├────────────────────────────────────────────────────────┤
   │ Your payment is currently being verified.              │
   │ Transaction Reference: TX-123456                        │
   │ Status: Pending Confirmation                           │
   │ Estimated Time: 5 - 15 Minutes                         │
   └────────────────────────────────────────────────────────┘
   ```

---

### 3.4 Withdrawal Flow

- **الهدف**: تقديم طلب سحب رصيد للحساب البنكي.
- **View Name**: `wallet::shop.customers.account.wallet.withdraw`
- **Route**: `GET /customer/account/wallet/withdraw`, `POST /customer/account/wallet/withdraw/store`

#### السجل المدمج داخل نفس الصفحة (`Embedded Withdrawal History`) *(إضافة مراجعة V1.0)*
تضمين سجل الطلبات السابقة داخل نفس الصفحة تحت نموذج الطلب لمنع العميل من التنقل للبحث عن حالة طلباته:

```html
┌────────────────────────────────────────────────────────┐
│ Request New Withdrawal                                 │
│ [ Amount Input ] [ Bank Details ] [ Submit Request ]   │
├────────────────────────────────────────────────────────┤
│ Previous Requests                                      │
│ • $50.00  - Completed (Ref: BANK-9921)                 │
│ • $100.00 - Rejected (Reason: Invalid IBAN Name)       │
└────────────────────────────────────────────────────────┘
```

---

### 3.5 Statement & Export Receipts

- **الهدف**: استخراج كشف حساب رسمي وتنزيل إيصالات عمليات مفردة للمصداقية.
- **View Name**: `wallet::shop.customers.account.wallet.statement`
- **Route**: `GET /customer/account/wallet/statement`
- **المخرجات**:
  - كشف حساب بالفترة (PDF / Print View).
  - إيصال رسمي مستقل للمعاملة (Transaction Receipt Template): يحتوي على شعار منصة HIGEST، رقم المرجع، التاريخ، الحساب المتأثر، والتوقيع الرقمي للمنصة.

---

## 4. Shared UX & Error Handling States

---

### 4.1 Permission State (`403 Forbidden State`)

تُعرض للأدمن في حالة محاولة القيام بعملية لا يملك صلاحية لها (مثلاً أدمن الدعم حاول اعتماد سحب مالي دون صلاحية `admin.wallet.withdrawals.manage`):

```html
┌────────────────────────────────────────────────────────┐
│ 🛑 Permission Denied                                  │
├────────────────────────────────────────────────────────┤
│ You don't have permission to perform this financial    │
│ action.                                                │
│ Required Permission: admin.wallet.withdrawals.manage   │
└────────────────────────────────────────────────────────┘
```

---

### 4.2 System Error State

تُعرض عند حدوث خطأ غير متوقع في معالجة العمليات المالية لتخفيف توتر العميل وتسهيل مهمة الدعم الفني:

```html
┌────────────────────────────────────────────────────────┐
│ ⚠️ System Error                                       │
├────────────────────────────────────────────────────────┤
│ Unable to process withdrawal request at this time.     │
│ Reference ID: ERR-98231                                │
│ Please quote this reference ID to Customer Care.       │
└────────────────────────────────────────────────────────┘
```

---

## 5. Frontend-Backend API & Form Request Contracts Matrix

| الشاشة | Form Request / Validation Class | Endpoint / Action | Method |
|---|---|---|---|
| **Admin Adjustment** | `Webkul\Wallet\Http\Requests\Admin\AdjustmentRequest` | `admin.wallet.adjustments.store` | `POST` |
| **Admin Approve Withdrawal** | `Webkul\Wallet\Http\Requests\Admin\ApproveWithdrawalRequest` | `admin.wallet.withdrawals.approve` | `POST` |
| **Admin Reject Withdrawal** | `Webkul\Wallet\Http\Requests\Admin\RejectWithdrawalRequest` | `admin.wallet.withdrawals.reject` | `POST` |
| **Customer TopUp** | `Webkul\Wallet\Http\Requests\Shop\TopUpRequest` | `shop.customers.account.wallet.topup.process` | `POST` |
| **Customer Withdrawal** | `Webkul\Wallet\Http\Requests\Shop\WithdrawalRequest` | `shop.customers.account.wallet.withdraw.store` | `POST` |

---

## 6. Verification & Definition of Done (DoD)

قبل اعتماد تطوير المكونات (Blade/Vue)، يلتزم فريق Frontend بالفحص التالي:

1. ✅ تطابق المكونات مع الهيكل البصري والمميزات الواردة في المراجعة V1.0.
2. ✅ دعم الترجمة الكاملة لـ 21 ملف لغة لكل المفاتيح النصية الواجهة.
3. ✅ وجود اختبارات E2E Playwright للتدفقات الرئيسية (TopUp flow, Withdrawal flow, Adjustment 2-step confirmation).
4. ✅ اجتياز فحص الصياغة `vendor/bin/pint --dirty`.
