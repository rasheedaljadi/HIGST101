# مصفوفة تحولات الحالات وأثر Webhook AliExpress في Procurement V2

**تاريخ الاعتماد:** 2026-08-22 23:44:00 +03:00  
**النطاق:** توثيق الأثر الشامل لكل حدث Webhook وارد من منصة AliExpress Open Platform وربطه بالاستعلام الموثق والانتقال الآمن.

---

## مصفوفة تحولات الحالات الشاملة (Event to Domain State Transition Matrix)

| Event Type (Webhook) | Official Pull Call | AliExpress Status | Platform Order Status | Supplier PO State | Allocation Effect | Owned Inventory (`hayest_dropship_sa`) | Finance & Accounting | Store Order Lifecycle |
| :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: |
| **`53`** (Status Update) | `getOrder($id)` | `WAIT_BUYER_PAY` | `wait_buyer_pay` | `awaiting_manual_payment` | محجوزة ومخصصة (`allocated`) | **غير مملوك (Unowned)** — صفر حركة | لا قيود مالية | لا تسليم ولا Handoff |
| **`53`** (Status Update) | `getOrder($id)` | `WAIT_SELLER_SEND_GOODS` / `PROCESSING` | `processing` | `supplier_processing` | محجوزة ومخصصة (`allocated`) | **غير مملوك (Unowned)** — صفر حركة | فحص التكلفة وتسجيل Snapshot تدقيقي (`actual_cost`) | قيد التجهيز لدى المورد الخارجي |
| **`53`** (Status Update) | `getOrder($id)` | `SELLER_SEND_GOODS` / `SHIPPED` | `shipped` | `supplier_shipped` | محجوزة ومخصصة (`allocated`) | **غير مملوك (Unowned)** — صفر زيادة في الرياض قبل الاستلام | لا قيود مالية جديدة | شحنت من الصين (Transit) |
| **`53`** (Status Update) | `getOrder($id)` | `CANCELLED` / `CLOSED` | `cancelled` | `cancelled` | **تحرير التخصيصات (`cancelled`)** | **صفر أثر** (لا خصم ولا إضافة لمخزون مملوك) | **صفر قيود مصطنعة** | إلغاء / استثناء آمن |
| **`51`** (Payment Update) | `getOrder($id)` | `PROCESSING` / `PAID` | `processing` | `supplier_processing` (`paid_externally`) | محجوزة ومخصصة (`allocated`) | **غير مملوك (Unowned)** — صفر حركة | تسجيل تدقيقي `paid_externally` + Snapshot | تحديث تدقيق الدفع فقط |
| **`18`** (Logistics / Tracking) | `getOrder($id)` | `SELLER_SEND_GOODS` | `shipped` | `supplier_shipped` | محجوزة ومخصصة (`allocated`) | **غير مملوك (Unowned)** — صفر زيادة | لا قيود مالية | تسجيل رقم التتبع والناقل للطلب |
| **`65`** (OAuth Expiration) | *لا استدعاء طلب* | `N/A` | *لا تغيير* | *لا تغيير* | *لا تغيير* | *لا تغيير* | *لا تغيير* | تسجيل تنبيه تدقيقي لنظام التشغيل |
| **`56 / 57 / 60 / Other`** (Choice/JIT/Unknown) | *لا استدعاء* | `N/A` | *لا تغيير* (Ignored) | *لا تغيير* (Ignored) | *لا تغيير* | *لا تغيير* | *لا تغيير* | عزل تام وتجاهل آمن |

---

## القواعد الحتمية لحماية المخزون والمالية (Invariant Safeguards)

1. **المخزون غير المملوك (Unowned Principle):**
   أي منتج قيد الطلب الخارجي أو الشحن من الصين يظل غير مملوك لشركة هايست (`unowned`)، ويُمنع منعاً باتاً زيادة رصيد مستودع الرياض (`hayest_dropship_sa`) أو تسليم المنتج للعميل قبل وصوله الفعلي للفرز.

2. **قاعدة الرتب الأحادية الصارمة (Monotonic Invariant):**
   تطبيق مصفوفة الرتب البرمجية:
   `wait_buyer_pay (10) → processing (20) → shipped (30) → completed (40) → cancelled (50)`.
   يُحظر الرجوع من حالة متقدمة أو ملغاة إلى حالة سابقة نتيجة وصول حدث متأخر أو مكرر.

3. **ازدواجية الإشعار والاستعلام الموثق (Webhook-Pull Pairing):**
   رسالة الـ Webhook هي إشارة تنبيهية (Advisory Trigger)؛ القرار النهائي وتحديث الحالات يعتمد حصراً على الاستعلام المباشر عبر `AliExpressOrderGateway::getOrder($tradeOrderId)` بالمعرف الرقمي الرسمي.
